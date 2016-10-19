<?php

namespace App\Scraper\Steam;

use App\Helpers\ArrayHelpers;
use App\Helpers\Log;
use App\Helpers\SteamHelpers;
use App\ItemData;
use App\SalesHistory;
use App\Scraper\Steam\Base\SteamScraper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MultiRequest\Request;

class MarketListing extends SteamScraper
{

	protected $MAX_RESPONSE_AMOUNT = 1;
	protected $MAX_FAILURE_COUNT_PER_URL = 30;

	public $scraperName = 'MarketListing';

	protected $failedPriceHistoryParsing = 0;
	protected $noListingsMessage = 0;
	protected $noHistoryMessage = 0;
	protected $inspectLinksSaving = 0;
	protected $testHistoryFirst = 0;
	protected $testHistorySecond = 0;
	protected $descriptionParseTime = 0;
	protected $descriptionSavingTime = 0;
	protected $descriptionParseFail = 0;
	protected $descriptionParseNoDesc = 0;
	protected $descriptionParseSuccess = 0;
	protected $individualDescriptorFail = 0;

	protected $marketListingStartTime;

	protected $updatedIds = [];
	protected $itemDescriptions = [];
	protected $inspectLinks = [];

	protected $calculateOnFly = false;

	public function __construct($calculateOnFly = false)
	{
		parent::__construct();
		$this->cURLoptions[ CURLOPT_TIMEOUT ] = 120;
		$this->cURLoptions[ CURLOPT_CONNECTTIMEOUT ] = 30;

		$this->marketListingStartTime = Carbon::now()->toDateTimeString();
		$this->calculateOnFly = $calculateOnFly;
	}

	protected function generateLinks($apps = [])
	{
		// http://stackoverflow.com/a/27329567/757587
		list($apps) = func_get_args();

		$items = DB::table('gameitems AS items')->select(['id', 'appid', 'subappid', 'name', 'url_encode']);

		$appList = [];

		foreach ($apps as $app) {
			$appList[] = $app->appid;
		}

		if ($apps) {
			$items = $items->whereIn('items.appid', $appList);
		}

		$items = $items->get();

		foreach ($items as $item) {
			$this->addLinkInQueue(
				[
					'url'     => SteamHelpers::constructListingUrl($item->name, $item->appid, $item->subappid, $item->url_encode),
					'details' => [
						'id'    => $item->id,
						'appid' => $item->appid
					]
				]
			);
		}
	}

	public function isResponseOk(Request $request)
	{
		if ($request->getCode() === 200) {
			$testTime1 = microtime(true);
			$testResult = $this->testMatchHistory($request);
			$this->testHistoryFirst = $this->testHistoryFirst + (microtime(true) - $testTime1);

			if (is_array($testResult)) {
				return true;
			}
		}

		return false;
	}

	public function isResponseBroken(Request $request)
	{
		if (str_contains($request->getContent(), '<TITLE>Access Denied</TITLE>') ||
			str_contains($request->getContent(), '<title>404 Not Found</title>')
		) {

			return true;
		}

		return false;
	}

	protected function testMatchHistory(Request $request)
	{
		$t = microtime(true);
		$response = $request->getContent();

		// Even if Steam market says there are no listings currently, the price history is still in source code despite the graph not showing up
		preg_match('/<div class="market_listing_table_message">\s*There are no listings for this item\.\s*<\/div>/', $response, $noListingMatches);

		// Item exists, there is descriptions etc. but no graph to display
		preg_match('/<div class="pricehistory_notavailable_info">\s*There is no price history available for this item yet.\s*<\/div>/', $response, $noPhAvailable);

		if (isset($noListingMatches[0])) {
			$this->noListingsMessage++;
		}

		if (isset($noPhAvailable[0])) {
			$this->noHistoryMessage++;

			// Returning empty array because when checking if response is OK, it checks if an array got returned
			return [];
		}

		preg_match("/var line1\s?=\s?(.*?);/", $response, $matches);

		if (empty($matches[1])) {
			return false;
		}

		$json = json_decode($matches[1], true);

		$request->historyJsonDecoded = $json;

		return $json;
	}

	protected function parsePricehistory(Request $request)
	{
		$testTime1 = microtime(true);

		$json = (isset($request->historyJsonDecoded) ? $request->historyJsonDecoded : $this->testMatchHistory($request));

		$this->testHistorySecond = $this->testHistorySecond + (microtime(true) - $testTime1);

		if (is_array($json)) {
			$sortedResult = [];

			// Taking exceptions because in rare cases the date string has malfunctioned, possibly due to invalid proxy responses
			// https://github.com/ncla/steamexpert/issues/75
			try {
				foreach ($json as $phDetails) {
					// Failed to parse time string (Nov 10 2014 01:+0:00) at position 12 (0): Unexpected character'
					// $timeStr = str_replace(': +0', '', $phDetails[0]) . ':00';
					$timeStr = str_split($phDetails[0], 11)[0];
					$date = Carbon::parse($timeStr)->toDateString();

					if (isset($sortedResult[ $date ]['amount'])) {
						$sortedResult[ $date ]['amount'] = $sortedResult[ $date ]['amount'] + $phDetails[2];
					} else {
						$sortedResult[ $date ]['amount'] = $phDetails[2];
					}

					$sortedResult[ $date ]['median'][] = $phDetails[1];
				}

				foreach ($sortedResult as $dateKey => $dateValue) {
					$sortedResult[ $dateKey ]['median'] = ArrayHelpers::calculateMedian($sortedResult[ $dateKey ]['median']);
				}
			} catch (\Exception $exception) {
				$this->failedPriceHistoryParsing++;
				Log::msg('[parsePricehistory failed]' . $exception->__toString())->warning()
					->msg('Failed price history parsing at ' . $request->getUrl())->debug();

				return false;
			}

			return $sortedResult;
		}

		return false;
	}

	protected function parseForInspectLink(Request $request)
	{
		$response = $request->getContent();

		// Example: steam:\/\/rungame\/730\/76561202255233023\/+csgo_econ_action_preview%20M428186654126070375A%assetid%D16348078385169738101
		preg_match('#steam:\\\/\\\/rungame\\\/730\\\/76561202255233023\\\/\+csgo_econ_action_preview(.*?)\"\,#', $response, $urlMatches);

		preg_match('#"id":"(.*?)","classid":"#', $response, $assetIdMatches);

		if (isset($urlMatches[1]) && isset($assetIdMatches[1])) {
			return str_replace('%assetid%', $assetIdMatches[1], $urlMatches[1]);
		}

		return false;
	}

	/**
	 * @param Request $request
	 *
	 * @return bool|null|string null = no description found, false = could not determine description
	 */
	protected function parseDescription(Request $request)
	{
		$response = $request->getContent();

		preg_match("/var g_rgAssets\s?=\s?(.*);/", $response, $matches);

		if (isset($matches[1]) === false) {
			return false;
		}

		$assetsVariable = json_decode($matches[1]);

		// Sometimes JSON fails in some cases
		if ($assetsVariable === null) {
			return false;
		}

		$assetsVariableAppId = reset($assetsVariable);

		// Happens with empty array
		if ($assetsVariableAppId === false) {
			return false;
		}

		$assetsVariableAppIdContext = reset($assetsVariableAppId);
		$assetsVariableAppIdContextFirstItem = reset($assetsVariableAppIdContext);

		// Not all MarketListing items have a description
		if (isset($assetsVariableAppIdContextFirstItem->descriptions) === false) {
			return null;
		}


		$descriptionString = '';
		// Written to be the same as or close to the one Steam market has, PopulateDescriptions function
		// economy.js#L2679-L2732

		foreach ($assetsVariableAppIdContextFirstItem->descriptions as $desc) {
			// We are not interested in labels, images, empty descriptors
			if (isset($desc->value) === false || isset($desc->label) || (isset($desc->type) && $desc->type === 'image')) {
				continue;
			}

			$description = trim($desc->value);

			// TODO: Look into more strings that we do not need in description
			if (strpos($description, 'Tradable After:') === 0) {
				continue;
			}

			// In Steam marketplace, they take the epoch time in seconds, multiple by 1000 to fit for JavaScripts Date object,
			// which takes the value in down to miliseconds. We don't need to do that obviously.

			$description = preg_replace_callback('/\[date\](\d*)\[\/date\]/', function ($matches) {
				return Carbon::createFromTimestamp($matches[1])->toDayDateTimeString();
			}, $description);

			// We really just want raw description
			try {
				$doc = new \DOMDocument();

				$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . preg_replace('/[\x00-\x1F\x7F]/', '', $description));

				$xpath = new \DOMXPath($doc);

				foreach ($xpath->query('//*[contains(@style,\'border:\')]') as $node) {
					$node->parentNode->removeChild($node);
				}

				$description = $doc->textContent;
			} catch (\ErrorException $e) {
				$this->individualDescriptorFail++;
			}

			if (strlen($description) === 0) {
				continue;
			}

			$descriptionString .= $description . "\n";
		}

		$descriptionString = trim($descriptionString);

		if (strlen($descriptionString) === 0) {
			return null;
		}

		return $descriptionString;
	}

	protected function parseResponses()
	{
		$historyList = [];

		$time1 = microtime(true);

		foreach ($this->responses as $request) {
			$pricehistory = $this->parsePricehistory($request);

			if (is_array($pricehistory) && count($pricehistory) !== 0) {
				$historyList[] = [
					'history'   => $pricehistory,
					'details'   => $request->details,
					'timestamp' => $request->requestFinishedTimestamp
				];
			}

			if ($request->details['appid'] === 730) {
				$inspectUrl = $this->parseForInspectLink($request);

				if ($inspectUrl !== false) {
					$this->inspectLinks[] = [
						'url'     => $inspectUrl,
						'details' => $request->details
					];
				}
			}

			$descriptionParseTime = microtime(true);

			$description = $this->parseDescription($request);

			if ($description === false) {
				$this->descriptionParseFail++;
			}

			if ($description === null) {
				$this->descriptionParseNoDesc++;
			}

			if (gettype($description) === 'string') {
				$this->descriptionParseSuccess++;
				$this->itemDescriptions[ $request->details['id'] ] = $description;
			}

			$this->descriptionParseTime = $this->descriptionParseTime + (microtime(true) - $descriptionParseTime);

		}

		$this->parsingTime = $this->parsingTime + (microtime(true) - $time1);

		$time1 = microtime(true);

		if ($this->noSave === false) {
			$this->saveHistory($historyList);

			if ($this->calculateOnFly === true) {
				$this->calculateItemDataAndSave($historyList);
			}
		}

		$this->savingTime = $this->savingTime + (microtime(true) - $time1);

		$this->responses = [];
	}

	protected function saveHistory($pricehistories)
	{
		if (count($pricehistories) === 0) {
			return false;
		}

		$data = [];

		foreach ($pricehistories as $historyData) {
			foreach ($historyData['history'] as $dateKey => $dateValue) {
				$data[] = [
					'item_id' => $historyData['details']['id'],
					'date' => $dateKey,
					'median_price' => $dateValue['median'],
					'amount_sold' => $dateValue['amount'],
					'created_at' => $historyData['timestamp'],
					'updated_at' => $historyData['timestamp']
				];
			}
		}

		SalesHistory::insertOnDuplicateKey($data, ['amount_sold', 'median_price', 'updated_at']);
	}

	protected function calculateItemDataAndSave($pricehistories)
	{
		$results = [];

		$formatted_date = Carbon::now()->subDays(90)->toDateString();

		foreach ($pricehistories as $phData) {
			$pricehistory = DB::table('saleshistory')
				->where('date', '>=', $formatted_date)
				->where('item_id', '=', $phData['details']['id'])
				->get();

			$itemData = ItemData::process90DaySalesHistory($pricehistory);

			$results[ $phData['details']['id'] ] = $itemData;

			$this->updatedIds[] = $phData['details']['id'];
		}

		ItemData::insertItemCalculations($results);
	}

	protected function saveInspectLinks($links)
	{
		foreach ($links as $inspectData) {
			DB::table('gameitems')
				->where('id', $inspectData['details']['id'])
				->update(['inspect_url' => $inspectData['url']]);
		}
	}

	protected function saveDescriptions($descriptions)
	{
		foreach ($descriptions as $id => $description) {
			DB::table('gameitems')
				->where('id', $id)
				->update(['description' => $description]);
		}
	}

	protected function afterUpdate()
	{
		Log::msg('Processing afterUpdate shenanigans..')->echo();

		if ($this->calculateOnFly === true) {
			$priceHistoryClass = new ItemData();
			$priceHistoryClass->calculate(90, $this->marketListingStartTime, $this->updatedIds);
		}

		$inspectSavingTime = microtime(true);

		$this->saveInspectLinks($this->inspectLinks);

		$this->inspectLinksSaving = $this->inspectLinksSaving + (microtime(true) - $inspectSavingTime);

		$descriptionSavingTime = microtime(true);

		$this->saveDescriptions($this->itemDescriptions);

		$this->descriptionSavingTime = $this->descriptionSavingTime + (microtime(true) - $descriptionSavingTime);

		$data = [
			'Inspect link saving' => $this->inspectLinksSaving,
			'Description saving'  => $this->descriptionSavingTime
		];

		Log::array($data)->info()->slack();
	}
	
	protected function getPerformanceIndicators()
	{
		return array_merge(parent::getPerformanceIndicators(), [
			'failed_ph_parsing'  => $this->failedPriceHistoryParsing, 'no_listings' => $this->noListingsMessage, 'no_history' => $this->noHistoryMessage,
			'test_history_first' => $this->testHistoryFirst, 'test_history_second' => $this->testHistorySecond, 'description_parse' => $this->descriptionParseTime,
			'inspect_save' => $this->inspectLinksSaving, 'desc_saving' => $this->descriptionSavingTime, 'desc_success' => $this->descriptionParseSuccess,
			'desc_null' => $this->descriptionParseNoDesc, 'desc_fail' => $this->descriptionParseFail, 'desc_individual_fail' => $this->individualDescriptorFail
		]);
	}
}