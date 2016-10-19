<?php

namespace App\Scraper\Steam;

use App\Helpers\Log;
use App\Scraper\Steam\Base\SteamScraper;
use App\SupplyHistory;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MultiRequest\Request;
use waylaidwanderer\SteamCommunity\Enum\LoginResult;
use waylaidwanderer\SteamCommunity\SteamCommunity;

class MarketSearch extends SteamScraper
{
	protected $MAX_RESPONSE_AMOUNT = 1;

	public $scraperName = 'MarketSearch';

	protected $loggedIn = false;

	protected $domDocumentErrors = 0;
	protected $jsonParseError = 0;
	protected $appIdNotMatch = 0;
	protected $missingNodes = 0;

	protected $startTimestamp;

	public function __construct()
	{
		parent::__construct();

		$this->startTimestamp = Carbon::now()->toDateTimeString();
	}

	protected function isResponseOk(Request $request)
	{
		// TODO: Json optimization here as well?
		if ($request->getCode() === 200 && is_object(json_decode($request->getContent()))) {
			return true;
		}

		return false;
	}

	protected function successCallback(Request $request)
	{
		parent::successCallback($request);

		if ($request->details['init'] === true) {
			// Independently parse response (check total count and add more requests)
			$json = json_decode($request->getContent());

			$totalCount = $json->total_count;

			$this->generateLinksAdditional($request->details['appID'], $totalCount);
		}
	}

	protected function generateLinksAdditional($appID, $total)
	{
		if ($total <= 100) {
			return;
		}

		// We start with $i being 100 because we have already parsed first page
		for ($i = 100; $i < $total; $i += 100) {
			$this->addLinkInQueue(
				[
					'url'     => 'http://steamcommunity.com/market/search/render/?query=&start=' . $i . '&count=100&search_descriptions=0&sort_column=name&sort_dir=asc&appid=' . $appID . '&currency=1',
					'details' => [
						'appID' => $appID,
						'init'  => false
					]
				], true
			);
		}
	}

	protected function generateLinks()
	{
		// http://stackoverflow.com/a/27329567/757587
		list($appsToFetch) = func_get_args();

		if (empty($appsToFetch) === true) {
			$appsToFetch = \DB::table('applications')->select('appid')->where('marketable', '=', '1')->get();
		}

		if (count($appsToFetch) === 0) {
			throw new \Exception('No games set to fetch');
		}

		foreach ($appsToFetch as $game => $gameProperties) {
			$this->addLinkInQueue(
				[
					'url'     => 'http://steamcommunity.com/market/search/render/?query=&start=0&count=100&search_descriptions=0&sort_column=name&sort_dir=asc&appid=' . $gameProperties->appid . '&currency=1',
					'details' => [
						'appID' => $gameProperties->appid,
						'init'  => true
					]
				]
			);
		}
	}

	public static function determineAmountOfItems($appID, $loggedIn = false)
	{
		echo 'Fetching total item amount for ' . $appID . PHP_EOL;

		$curl = new Curl();

		if ($loggedIn) {
			$cookieStoragePath = storage_path() . DIRECTORY_SEPARATOR . 'cookiefiles' . DIRECTORY_SEPARATOR . env('STEAM_USERNAME') . '.cookiefile';
			$curl->setCookieFile($cookieStoragePath);
			$curl->setCookieJar($cookieStoragePath);
		}

		$curl->get('http://steamcommunity.com/market/search/render/?query=&start=0&count=10&search_descriptions=0&sort_column=popular&sort_dir=desc&appid=' . $appID);

		if ($curl->error) {
			throw new \ErrorException('cURL Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
		}

		$response = $curl->rawResponse;

		$json = json_decode($response);

		echo 'Total item amount for ' . $appID . ': ' . $json->total_count . PHP_EOL;

		return $json->total_count;
	}

	protected function parseResponses()
	{
		$items = [];

		$time1 = microtime(true);

		foreach ($this->responses as $request) {
			$response = $request->getContent();

			$response = json_decode($response);

			if ($response === null && json_last_error() !== JSON_ERROR_NONE) {
				$warning = 'Error loading HTML string into DOMDocument';
				Log::msg($warning)->warning()->echo();
				$this->jsonParseError++;
				continue;
			}

			$html = $response->results_html;

			$doc = new \DOMDocument();

			// There is an annoying character in one of the Dota 2 items that triggers an error. http://steamcommunity.com/market/search?q=Autograph
			// "Invalid char in CDATA 0x1F in Entity". Removing the characters manually fixes the problem.
			// http://stackoverflow.com/questions/1497885/remove-control-characters-from-php-string

			$html = preg_replace('/[\x00-\x1F\x7F]/', '', $html);

			// To surpress warnings like this: DOMDocument::loadHTML(): htmlParseEntityRef: no name in Entity, line: 1
			// DOMDocument::loadHTML(): htmlParseEntityRef: expecting ';' in Entity, line: 1
			// This does not seem like an ideal solution. Seems to happen due to '&' symbols not being escaped in some places.

			libxml_use_internal_errors(true);

			try {
				$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
			} catch (\Exception $e) {
				$warning = 'Error loading HTML string into DOMDocument: ' . $e->getMessage() . ' ' . $request->getUrl();
				Log::msg($warning)->warning()->echo();
				$this->domDocumentErrors++;
				continue;
			}


			$xpath = new \DOMXPath($doc);

			preg_match('/:\/\/steamcommunity\.com\/market\/listings\/(\d+)\//', $response->results_html, $appIDmatch);

			$appID = $request->details['appID'];

			$marketListings = $xpath->query('//a[@class=\'market_listing_row_link\']');

			foreach ($marketListings as $listing) {
				preg_match('/\:\/\/steamcommunity\.com\/market\/listings\/(\d+)\/.*/', $listing->getAttribute('href'), $appIdResponse);

				if (isset($appIdResponse[1]) && intval($appIdResponse[1]) !== $appID) {
					Log::msg('appID does not match, response appid differs from specified one')->warning();
					$this->appIdNotMatch++;
					continue;
				}

				$itemNameNode = $xpath->query('.//*[@class=\'market_listing_item_name\']', $listing);
				$itemPriceNode = $xpath->query('.//*[contains(@class,\'market_listing_their_price\')]/span/span[@class=\'normal_price\']', $listing);
				$itemQuantityNode = $xpath->query('.//*[@class=\'market_listing_num_listings_qty\']', $listing);
				$itemImageNode = $xpath->query('.//*[@class=\'market_listing_item_img\']', $listing);

				if (isset($itemNameNode[0]) && isset($itemPriceNode[0]) && isset($itemQuantityNode[0])) {
					// We specifically do not trim the item name because some items have one white space at the end
					// If for example we url encode the item name, some items may not resolve then if coming from Steam listing page

					$itemName = $itemNameNode[0]->nodeValue;
					$itemPrice = floatval(trim(preg_replace('/[^0-9.]+/', '', html_entity_decode($itemPriceNode[0]->nodeValue))));
					$itemQuantity = intval(str_replace(',', '', trim($itemQuantityNode[0]->nodeValue)));

					// There are some rare items that for some reason do not have sub appID
					$subappID = null;

					$listingUrl = $listing->getAttribute('href');
					$parsedUrl = parse_url($listingUrl);

					$urlEncode = null;

					if (isset($parsedUrl['path'])) {
						$urlEncodeSubAppId = preg_match('/\/market\/listings\/\d+\/(\d+)-(.*)/', $parsedUrl['path'], $urlEncodeSubAppIdResults);

						if (isset($urlEncodeSubAppIdResults[2]) && $appID === 753) {
							$urlEncode = $urlEncodeSubAppIdResults[2];
						} else {
							$urlEncodeWithoutSubAppId = preg_match('/\/market\/listings\/\d+\/(.*)/', $parsedUrl['path'], $urlEncodeWithoutSubAppIdResults);

							if (isset($urlEncodeWithoutSubAppIdResults[1])) {
								$urlEncode = $urlEncodeWithoutSubAppIdResults[1];
							}
						}
					}

					// Gathering sub appID from URL
					if ($appID === 753) {
						preg_match('/\:\/\/steamcommunity\.com\/market\/listings\/753\/(\d+)-.*/', $listingUrl, $subappIDmatch);

						if (isset($subappIDmatch[1])) {
							$subappID = intval($subappIDmatch[1]);
						}
					}

					if (isset($itemImageNode[0])) {
						$itemImage = preg_replace('#\/\d+fx\d+f\Z#', '', trim($itemImageNode[0]->getAttribute('src')));
						$itemImage = str_replace('http://steamcommunity-a.akamaihd.net/economy/image/', '', $itemImage);
					} else {
						$itemImage = null;
					}

					// Correct data type for DB
					if ($itemPrice == 0) {
						$itemPrice = null;
					}

					$items[] = [
						'appid'      => $appID,
						'subappid'   => $subappID,
						'name'       => $itemName,
						'price'      => $itemPrice,
						'quantity'   => $itemQuantity,
						'url_encode' => $urlEncode,
						'image_url'  => $itemImage,
						'created_at' => $request->requestFinishedTimestamp,
						'updated_at' => $request->requestFinishedTimestamp
					];
				} else {
					Log::msg('Crucial market search market listing info is missing')->warning();
					$this->missingNodes++;
				}

			}
		}

		$this->parsingTime = $this->parsingTime + (microtime(true) - $time1);

		$this->responses = [];
		$this->save($items);
	}

	protected function save($items)
	{
		if ($this->noSave === true) {
			return false;
		}

		$time1 = microtime(true);

		foreach ($items as $item) {
			$doesExist = (bool)\DB::table('gameitems')
				->where('name', '=', $item['name'])
				->where('appid', '=', $item['appid'])
				->where('subappid', '=', $item['subappid'])
				->count();

			if ($doesExist) {
				\DB::table('gameitems')
					->where('name', '=', $item['name'])
					->where('appid', '=', $item['appid'])
					->where('subappid', '=', $item['subappid'])
					->update([
								 'quantity'   => $item['quantity'],
								 'price'      => $item['price'],
								 'image_url'  => $item['image_url'],
								 'url_encode' => $item['url_encode'],
								 'updated_at' => $item['updated_at']
							 ]);
			} else {
				\DB::table('gameitems')
					->insert($item);
			}
		}

		$this->savingTime = $this->savingTime + (microtime(true) - $time1);

		//var_dump('Time spent to update database rows: ' . (microtime(true) - $t));
	}

	protected function logOnSteamCommunity()
	{
		if (!$this->loggedIn && !Cache::has('appTotals')) {
			if (env('STEAM_USERNAME', 'null') !== 'null' && env('STEAM_PASSWORD', 'null')) {
				$steamCommunity = new SteamCommunity(['username' => env('STEAM_USERNAME'), 'password' => env('STEAM_PASSWORD')], storage_path());

				echo 'Attempting to log on SteamCommunity' . PHP_EOL;

				$loginStatus = $steamCommunity->doLogin(false);

				echo 'SteamCommunity log on status: ' . $loginStatus . PHP_EOL;

				if ($loginStatus === LoginResult::LoginOkay) {
					$this->loggedIn = true;

					return true;
				}
			} else {
				echo 'Steam username and/or password is not set in .env file!' . PHP_EOL;
			}
		}

		return false;
	}

	protected function afterUpdate()
	{
		$supplySavingTime = microtime(true);

		$endTimestamp = Carbon::now()->toDateTimeString();

		$updatedItems = DB::table('gameitems')
			->select(['id', 'price', 'quantity', 'updated_at'])
			->where('updated_at', '>=', $this->startTimestamp)
			->where('updated_at', '<=', $endTimestamp)
			->chunk(10000, function ($updatedItems) {
				$forMassInsert = [];

				foreach ($updatedItems as $item) {
					$forMassInsert[] = [
						'item_id'       => $item->id,
						'listing_price' => $item->price,
						'units'         => $item->quantity,
						'recorded_at'   => $item->updated_at
					];
				}

				SupplyHistory::insertIgnore($forMassInsert);
			});

		$totalSupplySave = (microtime(true) - $supplySavingTime);

		Log::msg('Saving supply and listing price history: ' . $totalSupplySave)->info()->slack();
	}

	protected function getPerformanceIndicators()
	{
		return array_merge(parent::getPerformanceIndicators(), [
			'json_errors'      => $this->jsonParseError,
			'dom_parse_errors' => $this->domDocumentErrors, 'appid_mismatches' => $this->appIdNotMatch, 'missing_nodes' => $this->missingNodes
		]);
	}
}