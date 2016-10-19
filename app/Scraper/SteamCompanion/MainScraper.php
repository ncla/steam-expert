<?php

namespace App\Scraper\SteamCompanion;

use App\SupplyHistory;
use Carbon\Carbon;
use Curl\MultiCurl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MultiRequest\Handler;
use MultiRequest\Request;
use MultiRequest\Session;

class MainScraper
{
	protected $marketData = [];

	protected $curlOptions = [
		CURLINFO_HEADER_OUT    => true,
		CURLOPT_VERBOSE        => true,
		CURLOPT_HEADER         => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 120,
		CURLOPT_CONNECTTIMEOUT => 30
	];

	protected $scrapperStats = [
		'noData'              => 0,
		'appIDfail'           => 0,
		'supplyPriceMismatch' => 0,
		'notInDb'             => 0
	];

	public function scrape()
	{
		$cachedCompanionData = Cache::get('companion_data');

		if ($cachedCompanionData !== null) {
			$this->marketData = $cachedCompanionData;
		} else {
			$this->scrapeSearchPages();
			$this->scrapeListingPages();
		}

		Cache::put('companion_data', $this->marketData, (60 * 24 * 7));

		$this->scrapeHistory();

		var_dump($this->scrapperStats);
	}

	protected function scrapeSearchPages()
	{
		$searchMaxPage = 2492;
		$step = 25;

		for ($i = 1; $i <= $searchMaxPage; $i += 50) {
			$multi_curl = new MultiCurl();

			$multi_curl->success(function ($instance) {
				echo 'call to "' . $instance->url . '" was successful.' . "\n";
				$this->parseSearchResponse($instance->response);
			});
			$multi_curl->error(function ($instance) {
				echo 'call to "' . $instance->url . '" was unsuccessful.' . "\n";
				echo 'error code: ' . $instance->errorCode . "\n";
				echo 'error message: ' . $instance->errorMessage . "\n";
			});
			$multi_curl->complete(function ($instance) {
				echo 'call completed' . "\n";
			});

			for ($aI = $i; $aI < ($i + $step); $aI++) {
				$multi_curl->addGet('https://steamcompanion.com/market.php?page=' . $aI);
			}

			$multi_curl->start();
		}
	}

	protected function scrapeListingPages()
	{
		$requestHandler = new Handler();
		$sessionHandler = new Session($requestHandler, '/tmp');

		$requestHandler->requestsDefaults()->addCurlOptions($this->curlOptions);

		$_this = $this;

		$requestHandler->onRequestComplete(function (Request $request, Handler $handler) use (&$_this) {
			echo $request->referenceId . ' || ' . $request->getUrl() . PHP_EOL;

			$encode = $_this->parseItemResponse($request);

			if ($encode === false) {
				throw new \ErrorException('Missing market URL in the response');
			}

			$_this->marketData[ $request->referenceId ]['encode'] = $encode;
		});

		foreach ($this->marketData as $id => $data) {
			$req = new Request();
			$req->setUrl('https://steamcompanion.com/' . $data['path']);
			$req->referenceId = $id;

			$requestHandler->pushRequestToQueue($req);
		}

		$requestHandler->setConnectionsLimit(20);

		$sessionHandler->start();
		$requestHandler->start();
	}

	protected function scrapeHistory()
	{
		$requestHandler = new Handler();
		$sessionHandler = new Session($requestHandler, '/tmp');

		$requestHandler->requestsDefaults()->addCurlOptions($this->curlOptions);

		$_this = $this;

		$requestHandler->onRequestComplete(function (Request $request, Handler $handler) use (&$_this) {
			$_this->parseHistoryResponse($request);
		});

		foreach ($this->marketData as $id => $data) {
			$req = new Request();
			$req->setUrl('https://steamcompanion.com/steamcompanion.php');

			$req->setPostData(
				http_build_query(
					[
						'id[]'   => $id,
						'option' => 'listing',
						'rates'  => '1',
						'script' => 'chart'
					]
				)
			);

			$req->addHeaders(
				[
					'Accept: application/json, text/javascript, */*; q=0.01',
					'Origin: https://steamcompanion.com',
					'X-Requested-With: XMLHttpRequest',
					'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/49.0.2623.108 Chrome/49.0.2623.108 Safari/537.36',
					'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
					'Referer: https://steamcompanion.com/' . $data['path'],
					'Accept-Language: en-US,en;q=0.8,lv;q=0.6'
				]
			);
			$req->referenceId = $id;


			$requestHandler->pushRequestToQueue($req);
		}

		$requestHandler->setConnectionsLimit(10);

		$sessionHandler->start();
		$requestHandler->start();
	}

	protected function parseSearchResponse($response)
	{
		$doc = new \DOMDocument;

		libxml_use_internal_errors(true);

		$doc->loadHTML($response);

		$xpath = new \DOMXPath($doc);

		$rows = $xpath->query('//section[@id=\'market-container\']/table/tbody/tr');

		/* @var $rows \DOMElement[] */
		foreach ($rows as $row) {
			$rowClass = $row->getAttribute('class');

			preg_match('/\d+/', $rowClass, $idMatch);

			if (isset($idMatch[0])) {
				$itemId = intval($idMatch[0]);
			} else {
				// Skip parsing this row if we can't extract Steamcompanion.com item ID
				continue;
			}

			$links = $xpath->query('.//a[@class=\'item-name\']', $row);

			if (!isset($links[0])) {
				continue;
			}

			// Without any checking we assume that this is URL path
			$path = $links[0]->getAttribute('href');

			$this->marketData[ $itemId ] = [
				'path' => $path
			];
		}

	}

	protected function parseItemResponse(Request $response)
	{
		$doc = new \DOMDocument;

		libxml_use_internal_errors(true);

		$doc->loadHTML($response->getContent());

		$xpath = new \DOMXPath($doc);

		$url = $xpath->query('//a[@class=\'view_store\']');

		if (isset($url[0])) {
			$parsedUrl = parse_url($url[0]->getAttribute('href'));

			$urlEncodeWithoutSubAppId = preg_match('/\/market\/listings\/\d+\/(.*)/', $parsedUrl['path'], $urlEncodeWithoutSubAppIdResults);

			if (isset($urlEncodeWithoutSubAppIdResults[1])) {
				return $urlEncodeWithoutSubAppIdResults[1];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	protected function parseHistoryResponse(Request $response)
	{
		$decoded = json_decode($response->getContent());
		if (isset($decoded->supply[0]->data) === false || isset($decoded->price[0]->data) === false) {
			$this->scrapperStats['noData']++;
			var_dump('No data:' . $this->marketData[ $response->referenceId ]['encode'] . '  ' . $this->marketData[ $response->referenceId ]['path']);

			return;
		}

		$supplyHistory = $decoded->supply[0]->data;
		$priceHistory = $decoded->price[0]->data;

		$appID = null;

		$path = $this->marketData[ $response->referenceId ]['path'];

		if (strpos($path, 'csgo/') === 0) {
			$appID = 730;
		}

		if (strpos($path, 'dota2/') === 0) {
			$appID = 570;
		}

		if (strpos($path, 'tf2/') === 0) {
			$appID = 440;
		}

		if ($appID === null) {
			var_dump('AppID fail:' . $this->marketData[ $response->referenceId ]['encode'] . '  ' . $this->marketData[ $response->referenceId ]['path']);
			$this->scrapperStats['appIDfail']++;

			return;
		}

		$doesExist = DB::table('gameitems')
			->select('id', 'name')
			->where('url_encode', '=', $this->marketData[ $response->referenceId ]['encode'])
			->where('appid', '=', $appID)
			->get();

		if (count($doesExist) === 0) {
			var_dump('Not in db:' . $this->marketData[ $response->referenceId ]['encode'] . '  ' . $this->marketData[ $response->referenceId ]['path']);
			$this->scrapperStats['notInDb']++;

			return;
		}

		$forDb = [];

		for ($i = 0; $i < count($supplyHistory); $i++) {
			$rowInsert = [
				'item_id'       => $doesExist[0]->id,
				'listing_price' => null,
				'units'         => $supplyHistory[ $i ][1],
				'recorded_at'   => Carbon::createFromTimestamp($supplyHistory[ $i ][0] / 1000)->toDateTimeString()
			];

			// Just to check we are dealing with the same timestamp
			if ($priceHistory[ $i ][0] == $supplyHistory[ $i ][0]) {
				$rowInsert['listing_price'] = $priceHistory[ $i ][0];
			} else {
				$this->scrapperStats['supplyPriceMismatch']++;
			}

			$forDb[] = $rowInsert;
		}

		SupplyHistory::insertIgnore($forDb);
	}

}