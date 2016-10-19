<?php

namespace App\Admin;

use App\GameItem;
use App\PerformanceLog;
use Carbon\Carbon;

class OddItems extends TableData
{

	const MAX_ROWS_PER_QUERY = 100000;
	protected static $types = [
		'no_price',
		'no_quantity',
		'no_price_history',
		'753_no_subappid',
		'no_calculations',
		'not_updated_recently',
		'multiple_appids',
		'above_400_usd',
		'no_image_url',
		'over_90_char_name',
		'weird_chars_in_name',
		'no_avg_totalsold_trend',
		'url_encoded_doesnt_match',
		'url_encoded_doesnt_match_not_null',
		'sa_items_not_in_gameitems'
	];
	protected static $select = [
		'ID'       => 'gameitems.id',
		'Name'     => 'gameitems.name',
		'AppID'    => 'gameitems.appid',
		'SubAppID' => 'gameitems.subappid',
		'Created'  => 'gameitems.created_at',
		'Updated'  => 'gameitems.updated_at'
	];

	/**
	 * @param $type
	 *
	 * @return array|null|static[]
	 */
	public final static function readyItems($type)
	{
		if (!in_array($type, self::$types)) {
			return null;
		}

		$q = \DB::table('gameitems')->select(self::$select);

		switch ($type) {
			case 'no_price':
				$q = $q->whereNull('price')
					->orWhere('price', 0);
				break;

			case 'no_quantity':
				$q = $q->where('quantity', 0);
				break;

			case '753_no_subappid':
				$q = $q->where('appid', 753)
					->whereNull('subappid')
					->orWhere('subappid', 0);
				break;

			case 'no_calculations':
				$q = $q->where('calculations_updated_at', '0000-00-00 00:00:00');
				break;

			case 'not_updated_recently':
				$q = $q->where('updated_at', '<', Carbon::now()->subHours(6)->toDateTimeString());
				break;

			case 'over_90_char_name':
				$q = $q->whereRaw('char_length(name) > ?', [90]);
				break;

			case 'no_image_url':
				$q = $q->whereNull('image_url');
				break;

			case 'above_400_usd':
				$q = $q->where('price', '>', 400);
				break;

			case 'weird_chars_in_name':
				$q = $q->whereRaw('length(name) <> char_length(name)')
					->whereRaw('name not like \'%StatTrak%\'')
					->whereRaw('name not like \'%★%\'');
				break;

			case 'no_price_history':
				$q = $q->whereNull('price')
					->whereRaw('id not in (select item_id from `saleshistory` group by item_id)');
				break;

			case 'multiple_appids':
				$q = $q->groupBy('name')
					->havingRaw('count(*) > 1');
				break;

			case 'no_avg_totalsold_trend':
				$q = $q->join('saleshistory', 'gameitems.id', '=', 'saleshistory.item_id')
					->whereRaw('saleshistory.item_id in (select id from gameitems where avg_median_90 is null 
                                and avg_median_7 is null and avg_median_30 is null and trend_7 is null 
                                and trend_30 is null and trend_90 is null and total_sold_7 is null 
                                and total_sold_30 is null and total_sold_90 is null)')
					->groupBy('saleshistory.item_id');
				break;

			case 'url_encoded_doesnt_match_not_null':
				/** @var GameItem[] $datas */
				$datas = $q->select(self::$select + ['url_encode'])
					->whereNotNull('url_encode')->get();
				$items = [];
				foreach ($datas as $data) {
					$encoded_name = str_replace('/', '-', rawurlencode($data->name));
					if ($encoded_name != $data->url_encode) {
						$data->php_rawurlencode = $encoded_name;
						$items[] = $data;
					}
				}
				self::$select['URL encode (DB)'] = 'url_encode';
				self::$select['PHP rawurlencode'] = 'php_rawurlencode';

				return $items;

			case 'url_encoded_doesnt_match':
				/** @var GameItem[] $datas */
				$datas = $q->select(self::$select + ['url_encode'])->get();
				$items = [];
				foreach ($datas as $data) {
					$encoded_name = str_replace('/', '-', rawurlencode($data->name));
					if ($encoded_name != $data->url_encode) {
						$data->php_rawurlencode = $encoded_name;
						$items[] = $data;
					}
				}
				self::$select['URL encode (DB)'] = 'url_encode';
				self::$select['PHP rawurlencode'] = 'php_rawurlencode';

				return $items;

			case 'sa_items_not_in_gameitems':
				self::$select = [
					'Name' => 'steamanalyst.name'
				];

				$q = $q->select(self::$select)
					->rightJoin('steamanalyst', 'steamanalyst.name', '=', 'gameitems.name')
					->whereNull('gameitems.name');
				break;

			default:
				return null;

		}

		return self::getRows($q);
	}

	private static function getRows($q)
	{
		$c = $q->count();
		if ($c > self::MAX_ROWS_PER_QUERY) {
			echo '<script>alert("Found too many results to display: ' . $c . ', showing first ' . self::MAX_ROWS_PER_QUERY . ' rows");</script>';

			return $q->take(self::MAX_ROWS_PER_QUERY)->get();
		}

		return $q->get();
	}


}