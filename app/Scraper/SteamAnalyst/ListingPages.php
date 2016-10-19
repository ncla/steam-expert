<?php

namespace App\Scraper\SteamAnalyst;

use App\Scraper\Steam\Base\SteamScraper;
use App\SupplyHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MultiRequest\Request;

class ListingPages extends SteamScraper
{
	public $noSupplyHistory = [];

	protected $MAX_FAILURE_COUNT_PER_URL = 50;
	public $scraperName = 'SteamAnalyst Listings';

	public function __construct()
	{
		parent::__construct();
	}

	protected function isResponseOk(Request $request)
	{
		if ($request->getCode() !== 200) {
			return false;
		}

		preg_match("/var supply\s?=\s?(.*);/", $request->getContent(), $supplyMatches);

		if (isset($supplyMatches[1])) {
			return true;
		}

		return false;
	}

	protected function parseResponses()
	{
		foreach ($this->responses as $request) {
			if ($request->details['id'] === null) {
				continue;
			}

			preg_match("/var supply\s?=\s?(.*);/", $request->getContent(), $supplyMatches);

			if (isset($supplyMatches[1])) {
				$jsonDecode = json_decode($supplyMatches[1]);

				if ($jsonDecode === null) {
					continue;
				}

				$forDbSupplyHistory = [];

				foreach ($jsonDecode as $historyEntry) {
					$forDbSupplyHistory[] = [
						'item_id'       => $request->details['id'],
						'listing_price' => null,
						'recorded_at'   => Carbon::createFromTimestamp($historyEntry[0] / 1000)->toDateTimeString(),
						'units'         => intval($historyEntry[1])
					];
				}

				SupplyHistory::insertIgnore($forDbSupplyHistory);

			}
		}

		$this->responses = [];
	}

	protected function giveMakeUpToRequest(Request $request, $details = null)
	{
		if (isset($details['details'])) {
			$request->details = $details['details'];
		}

		$request->addHeader('Accept: */*');
		$request->addHeader('Accept-Char: utf-8');
		$request->addHeader('Accept-Language: en-US,en;q=0.8,lv;q=0.6');
		$request->addHeader('Connection: keep-alive');
		$request->addHeader('Host: csgo.steamanalyst.com');
		$request->addHeader('Referer: http://csgo.steamanalyst.com/');
		$request->addHeader('User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/49.0.2623.108 Chrome/49.0.2623.108 Safari/537.36');

		return $request;
	}

	protected function generateLinks()
	{
		$SAitems = DB::table('steamanalyst')
			->join('gameitems', 'steamanalyst.name', '=', 'gameitems.name')
			->select(['item_id as SAid', 'steamanalyst.name as SAname', 'gameitems.name AS name', 'gameitems.id'])
			->get();

		foreach ($SAitems as $SAitem) {
			$this->addLinkInQueue(
				[
					'url'     => 'http://csgo.steamanalyst.com/id/' . $SAitem->SAid . '/180/',
					'details' => [
						'id'     => $SAitem->id,
						'SAid'   => $SAitem->SAid,
						'name'   => $SAitem->name,
						'SAname' => $SAitem->SAname
					]
				]
			);
		}
	}
}