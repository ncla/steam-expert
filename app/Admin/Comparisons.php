<?php

namespace App\Admin;

use App\GameItem;
use DB;

class Comparisons extends TableData
{

	protected static $types = [
		'SteamAnalyst'
	];

	protected static $select = [
		'Name' => 'gameitems.name'
	];

	public static function readyItems($type)
	{
		switch ($type) {
			case 'SteamAnalyst':
				self::$select += [
					'AVG Median' => 'gameitems.avg_median_7',
					'Median'     => 'gameitems.median_7',
				];

				$customSelect = [
					'SA Price'    => 'steamanalyst.average_price as sap',
					'avg_diff'    => 'TRUNCATE(gameitems.avg_median_7 - steamanalyst.average_price, 2) as avg_diff',
					'median_diff' => 'TRUNCATE(gameitems.median_7 - steamanalyst.average_price, 2) as median_diff'
				];
				$fullSelect = implode(',', self::$select + $customSelect);

				/** @var  GameItem[] $gameitems */
				$gameitems = DB::table('gameitems')
					->selectRaw($fullSelect)
					->join('steamanalyst', 'gameitems.name', '=', 'steamanalyst.name')
					->where('steamanalyst.price', '>', '0')
					->get();

				self::$select += [
					'SA AVG Price'    => 'sap',
					'Median diff'     => 'median_diff',
					'AVG Median diff' => 'avg_diff',
				];

				return $gameitems;
		}
	}
}