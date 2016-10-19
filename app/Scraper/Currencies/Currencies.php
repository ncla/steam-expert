<?php

namespace app\Scraper\Currencies;

use App\Currencies as CurrenciesModel;

class Currencies
{

	public $API_URL = 'https://openexchangerates.org/api/latest.json?app_id=';

	public function scrape()
	{
		if (strlen(env('APP_OPENEXCHANGERATES_KEY')) == 0) {
			throw new \ErrorException('API key for OpenExchangeRates not provided');
		}

		$response = $this->createRequest();

		$parsedResponse = json_decode($response);

		if ($parsedResponse != null) {
			foreach ($parsedResponse->rates as $currAbbr => $currRate) {
				CurrenciesModel::updateOrCreate(
					['abbreviation' => $currAbbr],
					['abbreviation' => $currAbbr, 'rate' => $currRate]
				);
			}
		}

	}

	public function createRequest()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->API_URL . env('APP_OPENEXCHANGERATES_KEY'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($httpCode != 200) {
			throw new \ErrorException('API did not response with HTTP 200, got HTTP ' . $httpCode . ' instead');
		}

		return $response;
	}

}
