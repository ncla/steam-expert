<?php

namespace App\Scraper\Steam;

use App\Helpers\Log;
use App\SteamApp;
use Curl\Curl;

class SteamApps
{

	protected $url = 'http://steamcommunity.com/market';

	public function update()
	{
		Log::msg('Fetching Steam Community Market homepage..')->echo();

		$response = $this->fetch($this->url);

		if ($response !== null) {
			$apps = $this->parse($response);

			Log::msg('Found ' . count($apps) . ' apps on the market, saving..')->echo();

			$this->save($apps);
		}
	}

	protected function fetch($url)
	{
		$curl = new Curl();
		$curl->get($url);

		if ($curl->error) {
			Log::msg('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage)->error()->echo();
		} else {
			return $curl->response;
		}

		return null;
	}

	protected function fetchApi()
	{
		$url = 'https://api.steampowered.com/ISteamApps/GetAppList/v2/?format=json&key=' . env('STEAM_API_KEY');
		$response = $this->fetch($url);

		if (!$response) {
			return null;
		}
	}

	protected function parse($response)
	{
		$apps = [];

		$doc = new \DOMDocument;

		$doc->loadHTML($response);

		$xpath = new \DOMXPath($doc);

		$browseItems = $xpath->query('//div[@id=\'browseItems\']');

		if (isset($browseItems[0])) {
			$games = $xpath->query('.//a[@class=\'game_button\']', $browseItems[0]);

			foreach ($games as $game) {
				preg_match('/\:\/\/steamcommunity\.com\/market\/search\?appid=(\d+)/', $game->getAttribute('href'), $appIDmatch);

				if (isset($appIDmatch[1])) {
					$appId = intval($appIDmatch[1]);
					$nameNode = $xpath->query('.//span[@class=\'game_button_game_name\']', $game);

					if (isset($nameNode[0])) {
						$appName = $nameNode[0]->nodeValue;
						$apps[ $appId ] = trim($appName);
					}
				}
			}
		}

		return $apps;
	}

	protected function save($apps)
	{
		foreach ($apps as $appID => $appName) {
			SteamApp::updateOrCreate(
				[
					'appid'      => $appID
				],
				[
					'appid'      => $appID,
					'name'       => $appName,
					'marketable' => 1
				]
			);
		}
	}

}