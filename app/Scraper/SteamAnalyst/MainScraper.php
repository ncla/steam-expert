<?php

namespace App\Scraper\SteamAnalyst;

use App\GameItem;
use App\Helpers\Python;
use App\SteamAnalyst;
use Curl\Curl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// TODO : remove vardumps
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

	public function scrape($useDbList = false, $insertGameitems = false, $scrapeSupply = true)
	{
		if ($useDbList) {
			$dbListObjects = DB::table('steamanalyst')
				->select(['item_id', 'name', 'price', 'average_price'])
				->get();

			$dbListArray = [];

			foreach ($dbListObjects as $item) {
				$dbListArray[] = (array)$item;
			}

			$this->marketData = $dbListArray;
		} else {
			$this->scrapeList();
			SteamAnalyst::insertOnDuplicateKey($this->marketData);
		}

		var_dump($this->marketData);

		if ($insertGameitems) {
			foreach ($this->marketData as $item) {
				$doesExist = (bool)\DB::table('gameitems')
					->where('name', '=', $item['name'])
					->where('appid', '=', 730)
					->count();

				if ($doesExist === false) {
					DB::table('gameitems')
						->insert([
									 'name'  => $item['name'],
									 'appid' => 730
								 ]);
				}
			}
		}

		if ($scrapeSupply) {
			$this->scrapeSupplyHistory();
		}
	}

	protected function scrapeList()
	{
		$page = 0;
		$lastPage = false;

		$cachedBrowserData = Cache::get('SA_browser_data');

		if ($cachedBrowserData !== null) {
			$browserData = $cachedBrowserData;
		} else {
			echo 'Logging in to get cookie logged_in from SteamAnalyst.com.' . PHP_EOL;

			$browserData = Python::run(Python::SALogin);

			if (!isset($browserData['cookies']) || !isset($browserData['userAgent'])) {
				throw new \ErrorException('Muh cookies for SteamAnalyst.com!');
			}

			Cache::put('SA_browser_data', $browserData, 60);
		}

		while ($lastPage === false) {
			// 35th page is the honeypot one, just skip it
			if ($page === 35) {
				$page++;
				continue;
			}

			$curl = new Curl();

			// Checks x-requested-with, referer, accept-char. Cookie needs a logged_in value
			$curl->setHeader('Accept', '*/*');
			$curl->setHeader('Accept-Char', 'utf-8');
			//$curl->setHeader('Accept-Encoding', 'gzip, deflate, sdch');
			$curl->setHeader('Accept-Language', 'en-US,en;q=0.8,lv;q=0.6');
			$curl->setHeader('Connection', 'keep-alive');
			$curl->setHeader('Host', 'csgo.steamanalyst.com');
			$curl->setHeader('Referer', 'http://csgo.steamanalyst.com/list');
			$curl->setHeader('User-Agent', $browserData['userAgent']);
			$curl->setHeader('X-Requested-With', 'XMLHttpRequest');

			foreach ($browserData['cookies'] as $cookie) {
				$curl->setCookie($cookie['name'], $cookie['value']);
			}

			$curl->get('http://csgo.steamanalyst.com/list-ajax.php?order%5B%5D=az&p=' . $page);

			echo 'URL: ' . $curl->url . ' - ' . $curl->httpStatusCode . PHP_EOL;

			if ($curl->httpStatusCode !== 200) {
				throw new \ErrorException('Unexpected HTTP status code ' . $curl->httpStatusCode);
			}

			if ($curl->httpStatusCode === 200 && strlen($curl->response) === 0) {
				$lastPage = true;
			} else {
				$this->parseList($curl->response);
				$page++;
				usleep(600);
			}
		}
	}

	protected function scrapeSupplyHistory()
	{
		$proxiedScraperAnalDestroy3000 = new ListingPages();
		$proxiedScraperAnalDestroy3000->update();
	}

	protected function parseList($response)
	{
		$doc = new \DOMDocument;

		libxml_use_internal_errors(true);

		$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $response);

		$xpath = new \DOMXPath($doc);

		$rows = $xpath->query('//tr');

		/* @var $rows \DOMElement[] */
		foreach ($rows as $row) {
			$url = $row->getAttribute('data-href');

			preg_match('/\d+/', $url, $idMatch);

			if (isset($idMatch[0])) {
				$itemId = intval($idMatch[0]);
			} else {
				// Skip parsing this row if we can't extract ID
				continue;
			}

			$columns = $xpath->query('.//td', $row);

			// Hopefully there is no stupid items with whitespaces at the end of item name and we can use trim() safely
			$name = trim($columns[0]->nodeValue);
			$price = floatval(str_replace(',', '', $columns[1]->nodeValue));
			$avg = floatval(str_replace(',', '', $columns[2]->nodeValue));

			$this->marketData[] = [
				'item_id'       => $itemId,
				'name'          => $name,
				'price'         => $price,
				'average_price' => $avg
			];
		}
	}
}