<?php

namespace App\Helpers;

/**
 * Class CustomArrayHelpers
 * For any custom array helper methods that are not available in vanilla PHP
 */

class ArrayHelpers
{
	public static function calculateMedian($array)
	{
		$iCount = count($array);

		if ($iCount == 0) {
			return 0;
		}

		$middle_index = floor($iCount / 2);
		sort($array, SORT_NUMERIC);

		$median = $array[ $middle_index ]; // assume an odd # of items
		// Handle the even case by averaging the middle 2 items
		if ($iCount % 2 == 0) {
			$median = ($median + $array[ $middle_index - 1 ]) / 2;
		}

		return $median;
	}

	public static function calculateAverage($array)
	{
		if (count($array) == 0) {
			return 0;
		}

		return array_sum($array) / count($array);
	}

	public static function splitArrayInHalf($array)
	{
		$len = count($array);

		$firsthalf = array_slice($array, 0, $len / 2);
		$secondhalf = array_slice($array, $len / 2);

		return [$firsthalf, $secondhalf];
	}
}