<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class GameItem extends Model
{

	protected $table = 'gameitems';

	protected $fillable = ['name', 'quantity', 'appid', 'price'];

	protected $guardable = ['id', 'created_at'];

	protected $hidden = ['created_at'];

	public function scopeAppID($query, $appID)
	{
		$query->where('appid', '=', $appID);
	}

	public function scopeSubAppID($query, $subAppID)
	{
		$query->where('subappid', '=', $subAppID);
	}

	public function scopeMinPrice($query, $minPrice)
	{
		$query->where('price', '>', $minPrice);
	}

	public function scopeMaxPrice($query, $maxPrice)
	{
		$query->where('price', '<', $maxPrice);
	}

	public function csgolounge()
	{
		return $this->hasOne('App\CsglItem', 'id', 'id');
	}

	public function saleshistory()
	{
		return $this->hasMany('App\SalesHistory', 'item_id', 'id');
	}

	public static function constructInspectUrl($appid, $previewValue)
	{
		if ($appid && $previewValue) {
			return 'steam://rungame/' . $appid . '/76561202255233023/+csgo_econ_action_preview' . $previewValue;
		} else {
			return null;
		}
	}

	public static function constructItemMarketImageUrl($value)
	{
		if ($value) {
			return (Request::secure() ? 'https' : 'http') . '://steamcommunity-a.akamaihd.net/economy/image/' . $value;
		}

		return null;
	}

	public function getInspectUrlAttribute($value)
	{
		return self::constructInspectUrl($this->appid, $value);
	}

	public function getImageUrlAttribute($value)
	{
		return self::constructItemMarketImageUrl($value);
	}

	public function getItems($userFilters = [])
	{
		$defaultFilters = [
			'appID'      => null,
			'amount'     => '100',
			'minPrice'   => null,
			'maxPrice'   => null,
			'days'       => 7,
			'minSold'    => 0,
			'maxSold'    => null,
			'minTrend'   => null,
			'maxTrend'   => null,
			'minAverage' => null,
			'maxAverage' => null,
			'name'       => null,
			'age'        => null
		];

		$filters = array_merge($defaultFilters, $userFilters);

		$intDays = (int)($filters['days']);
		$days = in_array($intDays, [7, 30, 90]) ? $intDays : 7;
		$filters['days'] = $days;

		$items = \DB::table('gameitems AS items')->select('items.id', 'items.name', 'appid', 'quantity', 'price',
														  'items.updated_at', 'avg' . $filters['days'] . ' AS avg', 'trend' . $filters['days'] . ' AS trend', 'totalsold' . $filters['days'] . ' AS totalsold')
			->leftJoin('item_calculations', 'items.id', '=', 'item_calculations.id')
			->groupBy('items.id')
			->take($filters['amount']);

		if ($filters['appID']) {
			$items = $items->where('items.appid', '=', $filters['appID']);
		}

		// Optional item age filter
		if ($filters['age']) {
			$filters['age'] = Carbon::now()->subDays((int)$filters['age'])->toDateString();
			$items = $items->addSelect('pricehistory.date AS firstsold')
				->leftJoin('pricehistory', 'items.id', '=', 'pricehistory.id')
				->orderBy('pricehistory.date', 'desc')
				->where('pricehistory.date', '<', $filters['age']);
		}

		if ($filters['minPrice']) {
			$items = $items->where('items.price', '>', $filters['minPrice']);
		}

		if ($filters['maxPrice']) {
			$items = $items->where('items.price', '<', $filters['maxPrice']);
		}

		if ($filters['minSold']) {
			$items = $items->where('totalsold' . $filters['days'], '>', $filters['minSold']);
		}

		if ($filters['maxSold']) {
			$items = $items->where('totalsold' . $filters['days'], '<', $filters['maxSold']);
		}

		// If "0" provided, ignores this query, user must provide 0.00 then
		if ($filters['minTrend']) {
			$items = $items->where('trend' . $filters['days'], '>', $filters['minTrend']);
		}

		if ($filters['maxTrend']) {
			$items = $items->where('trend' . $filters['days'], '<', $filters['maxTrend']);
		}

		if ($filters['minAverage']) {
			$items = $items->where('avg' . $filters['days'], '>', $filters['minAverage']);
		}

		if ($filters['maxAverage']) {
			$items = $items->where('avg' . $filters['days'], '<', $filters['maxAverage']);
		}

		if ($filters['name']) {
			$items = $items->where('name', 'like', '%' . $filters['name'] . '%');
		}

		$itemlist = $items->get();

		return $itemlist;
	}

}
