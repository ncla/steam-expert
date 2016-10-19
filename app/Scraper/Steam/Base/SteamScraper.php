<?php

namespace App\Scraper\Steam\Base;

use App\Helpers\Log;
use App\Helpers\MemoryHelpers;
use App\Helpers\Slacker;
use App\PerformanceLog;
use App\Proxy;
use App\ProxyList;
use Carbon\Carbon;
use MultiRequest\Handler;
use MultiRequest\Request;
use MultiRequest\Session;

abstract class SteamScraper
{
	protected $MAX_FAILURE_COUNT_PER_URL = 20;

	public $scraperName = 'Base';
	protected $MAX_RESPONSE_AMOUNT = 1;

	protected $proxyHandler;
	protected $requestHandler;

	protected $proxyAmount = 0;

	protected $noSave = false;
	protected $brokenResponses = 0;
	protected $failedRequests = 0;
	protected $requestsLeft = 0;
	protected $totalRequests = 0;
	protected $parsingTime = 0;
	protected $savingTime = 0;
	private $notifiedAboutFailure = false;

	private $connectionLimit;
	private $failedRequestsSinceSuccess = 0;
	private $minFailingRequestCount = 0;
	private $lastSuccessTimestamp = 0;
	const MAX_CONS_FAILED_REQUESTS = 2500;
	const MAX_CONS_FAILED_REQUEST_TIMEOUT = 60;

	protected $links = [];
	protected $responses = [];

	protected $unprocessablePages = [];

	protected $constructedTimestamp;
	protected $scraperFinishedTimestamp;

	protected $cURLoptions = [
		CURLINFO_HEADER_OUT    => true,
		CURLOPT_VERBOSE        => true,
		CURLOPT_HEADER         => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 120,
		CURLOPT_CONNECTTIMEOUT => 30
	];

	public function __construct()
	{
		$now = Carbon::now();
		$this->lastSuccessTimestamp = $now->timestamp;
		$this->constructedTimestamp = $now->toDateTimeString();

		$proxies = $this->getProxies();

		$this->proxyAmount = count($proxies);

		$this->proxyHandler = new ProxyList($proxies);
		$this->requestHandler = new Handler();
		$this->sessionHandler = new Session($this->requestHandler, '/tmp');

		$this->setMaximumConcurrentRequestAmount(count($proxies) / 2);
	}

	/**
	 * @param array $appsToFetch
	 * @param int $connectionLimit Maximum concurrent requests amount
	 * @param int $requestLimit Amount of requests
	 * @param bool $noSave Control saving scraped results to database
	 *
	 * @throws \MultiRequest\Exception
	 */
	public function update($appsToFetch = [], $connectionLimit = -1, $requestLimit = -1, $noSave = false)
	{
		$this->noSave = $noSave;

		$this->generateLinks($appsToFetch);

		if ($requestLimit !== -1) {
			$this->links = array_slice($this->links, 0, $requestLimit, true);
		}

		$this->requestsLeft = $this->totalRequests = count($this->links);
		$this->minFailingRequestCount = ($this->requestsLeft) * .1;

		if ($connectionLimit !== -1) {
			$this->setMaximumConcurrentRequestAmount($connectionLimit);
		}

		$_this = $this;

		$this->requestHandler->requestsDefaults()->addCurlOptions($this->cURLoptions);

		$this->requestHandler->onRequestComplete(function (Request $request) use (&$_this) {
			$successResponse = $_this->isResponseOk($request);

			if ($successResponse) {
				$_this->successCallback($request);

				if (count($_this->responses) >= $this->MAX_RESPONSE_AMOUNT) {
					$_this->parseResponses();
				}
			} else if ($_this->isResponseBroken($request)) {
				$this->brokenResponses++;
				$_this->proxyHandler->handleProxy($request, 1);
			} else {
				$_this->failureCallback($request);
			}

			printf('%d/%d, Success: %s, HTTP Code: %s, Time: %f, Proxies: %d, Memory: %s, Active req: %d, Queue req: %d' . PHP_EOL, $_this->requestsLeft, $_this->totalRequests, ($successResponse === true ? 'YES' : 'NO'),
				($request->getCode() ? $request->getCode() : 200), $request->getTime(), $_this->proxyHandler->getProxiesLeft(), MemoryHelpers::convert(memory_get_usage()),
				   $_this->requestHandler->getActiveRequestsCount(), $_this->requestHandler->getRequestsInQueueCount());
			echo $request->getUrl() . PHP_EOL;
		});

		foreach ($this->getInitialLinks() as $link) {
			$this->requestHandler->pushRequestToQueue($this->constructRequest($link));
		}

		$this->sessionHandler->start();
		$this->requestHandler->start();

		$this->parseResponses();

		$this->scraperFinishedTimestamp = Carbon::now()->toDateTimeString();
		
		Log::array($this->getPerformanceIndicators())->echo()->slack()->perflog()->info();

		$this->afterUpdate();
	}

	protected function addLinkInQueue($linkDetails, $pushToRequestHandler = false)
	{
		$this->totalRequests++;
		$this->requestsLeft++;

		$this->minFailingRequestCount = ($this->requestsLeft) * .1;

		// This is to avoid limitation with freely assignable proxies
		if ($pushToRequestHandler === true && ($this->requestHandler->getRequestsInQueueCount() + $this->requestHandler->getActiveRequestsCount()) < $this->connectionLimit) {
			$request = $this->constructRequest($linkDetails);

			$this->requestHandler->pushRequestToQueue($request);
		} else {
			$this->links[] = $linkDetails;
		}
	}

	protected function getProxies()
	{
		$proxies = Proxy::where('healthy', 1)
			->get();

		$proxies = $proxies->keyBy('ipport');

		if (count($proxies) === 0) {
			throw new \ErrorException('No proxies in database');
		}

		return $proxies;
	}

	protected function successCallback(Request $request)
	{
		$this->requestsLeft--;
		$this->failedRequestsSinceSuccess = 0;

		$this->proxyHandler->handleProxy($request, 1);

		$now = Carbon::now();
		$this->lastSuccessTimestamp = $now->timestamp;
		$request->requestFinishedTimestamp = $now->toDateTimeString();
		$this->responses[] = $request;

		$newRequestDetails = array_pop($this->links);

		if ($newRequestDetails !== null) {
			$newRequest = $this->constructRequest($newRequestDetails);

			$this->requestHandler->pushRequestToQueue($newRequest);
		}

		if (isset($this->unprocessablePages[ $request->getUrl() ])) {
			unset($this->unprocessablePages[ $request->getUrl() ]);
		}
	}

	protected function failureCallback(Request $request)
	{
		$this->failedRequests++;
		$this->failedRequestsSinceSuccess++;

		$this->proxyHandler->handleProxy($request, 0);

		// Handle broken pages that are stuck in queue
		if (isset($this->unprocessablePages[ $request->getUrl() ])) {
			$this->unprocessablePages[ $request->getUrl() ]++;
		} else {
			$this->unprocessablePages[ $request->getUrl() ] = 0;
		}

		if (!$this->notifiedAboutFailure && $this->failedRequestsSinceSuccess > self::MAX_CONS_FAILED_REQUESTS &&
			($this->requestsLeft > $this->minFailingRequestCount || $this->failedRequestsSinceSuccess > self::MAX_CONS_FAILED_REQUESTS * 2) &&
			(time() - $this->lastSuccessTimestamp) > self::MAX_CONS_FAILED_REQUEST_TIMEOUT
		) {
			$this->notifiedAboutFailure = Slacker::log('Steamcommunity is down?');
		}

		// Do not add request back in the queue if it has had it's time in swimming pool
		if ($this->unprocessablePages[ $request->getUrl() ] > $this->MAX_FAILURE_COUNT_PER_URL) {
			$this->requestsLeft--;

			// Pull up a new request from the swimming pool
			$newRequestDetails = array_pop($this->links);

			if ($newRequestDetails !== null) {
				$newRequest = $this->constructRequest($newRequestDetails);

				$this->requestHandler->pushRequestToQueue($newRequest);
			}
		} else {
			$request = $this->addProxyToRequest($request);

			$this->requestHandler->pushRequestToQueue($request);
		}
	}

	protected function getInitialLinks()
	{
		$initLinks = array_slice($this->links, 0, $this->connectionLimit, true);

		// TODO: Does this even work?
		foreach ($initLinks as $key => $value) {
			unset($this->links[ $key ]);
		}

		return $initLinks;
	}

	protected function constructRequest($details)
	{
		$request = new Request($details['url']);

		$request = $this->addProxyToRequest($request);

		$request = $this->giveMakeUpToRequest($request, $details);

		return $request;
	}

	protected function addProxyToRequest(Request $request)
	{
		$proxy = $this->proxyHandler->getProxy();

		$request->addCurlOptions([CURLOPT_PROXY => ($proxy->ipport)]);

		return $request;
	}

	protected function giveMakeUpToRequest(Request $request, $details = null)
	{
		if ($details === null) {
			return $request;
		}

		if (isset($details['details'])) {
			$request->details = $details['details'];
		}

		return $request;
	}

	protected function isResponseBroken(Request $request)
	{
		return false;
	}

	protected function setMaximumConcurrentRequestAmount($amount)
	{
		$this->connectionLimit = $amount;
		$this->requestHandler->setConnectionsLimit($amount);
	}

	protected function echoExtraLog()
	{
		return '';
	}

	protected function getPerformanceIndicators()
	{
		return [
			'start_time'          => $this->constructedTimestamp, 'finish_time' => $this->scraperFinishedTimestamp,
			'parsing_time'        => $this->parsingTime, 'saving_time' => $this->savingTime, 'failed_requests' => $this->failedRequests,
			'unprocessable_pages' => count($this->unprocessablePages), 'broken_responses' => $this->brokenResponses,
			'proxies_used' => $this->proxyAmount
		];
	}

	protected function afterUpdate()
	{
	}

	abstract protected function isResponseOk(Request $request);

	abstract protected function parseResponses();

	protected function generateLinks()
	{
		return [];
	}
}