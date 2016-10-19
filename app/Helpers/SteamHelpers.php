<?php

namespace App\Helpers;

class SteamHelpers
{

	public static function constructListingUrl($name, $appID, $subAppId, $urlEncode = null)
	{
		$baseListingUrl = 'http://steamcommunity.com/market/listings';
		// Item name is encoded like that because Steam treats forward slashes differently? "-" can be apparently treated as "/"

		$urlEncodedName = str_replace('/', '-', rawurlencode($name));

		// If manual URL encode does not match with the one we scraped, we use the scraped one so that
		// we can fetch correct MarketListing page
		if ($urlEncode !== null && $urlEncodedName !== $urlEncode) {
			$urlEncodacion = $urlEncode;
		} else {
			$urlEncodacion = $urlEncodedName;
		}

		$namePath = ($subAppId === null ? $urlEncodacion : $subAppId . '-' . $urlEncodacion);

		return $baseListingUrl . '/' . $appID . '/' . $namePath;
	}

}