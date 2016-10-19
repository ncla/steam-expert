<?php
/**
 * An helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App
{
	/**
	 * App\Proxy
	 * @property integer $id
	 * @property string $ip
	 * @property integer $port
	 * @property float $response_time_ms
	 * @property boolean $healthy
	 * @property \Carbon\Carbon $created_at
	 * @property \Carbon\Carbon $updated_at
	 * @property-read mixed $ip_port
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy whereIp($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy wherePort($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy whereResponseTimeMs($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy whereHealthy($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy whereCreatedAt($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Proxy whereUpdatedAt($value)
	 */
	class Proxy extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\PriceHistory
	 * @property integer $id
	 * @property string $date
	 * @property float $soldfor
	 * @property integer $amountsold
	 * @property \Carbon\Carbon $created_at
	 * @property \Carbon\Carbon $updated_at
	 * @property-read \App\GameItem $gameItem
	 * @method static \Illuminate\Database\Query\Builder|\App\PriceHistory whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PriceHistory whereDate($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PriceHistory whereSoldfor($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PriceHistory whereAmountsold($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PriceHistory whereCreatedAt($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PriceHistory whereUpdatedAt($value)
	 */
	class PriceHistory extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\SteamApp
	 * @property integer $id
	 * @property integer $appid
	 * @property boolean $tofetch
	 * @property string $name
	 * @method static \Illuminate\Database\Query\Builder|\App\SteamApp whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\SteamApp whereAppid($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\SteamApp whereTofetch($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\SteamApp whereName($value)
	 */
	class SteamApp extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\PerformanceLog
	 * @property string $stat
	 * @property string $added
	 * @property mixed $data
	 * @method static \Illuminate\Database\Query\Builder|\App\PerformanceLog whereStat($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PerformanceLog whereAdded($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\PerformanceLog whereData($value)
	 */
	class PerformanceLog extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\CsglItem
	 * @property integer $id
	 * @property float $value
	 * @property \Carbon\Carbon $created_at
	 * @property \Carbon\Carbon $updated_at
	 * @property-read \App\GameItem $gameItem
	 * @method static \Illuminate\Database\Query\Builder|\App\CsglItem whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\CsglItem whereValue($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\CsglItem whereCreatedAt($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\CsglItem whereUpdatedAt($value)
	 */
	class CsglItem extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\Currencies
	 * @property integer $id
	 * @property string $abbreviation
	 * @property float $rate
	 * @method static \Illuminate\Database\Query\Builder|\App\Currencies whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Currencies whereAbbreviation($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\Currencies whereRate($value)
	 */
	class Currencies extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\User

	 */
	class User extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\ApiKey
	 * @property integer $id
	 * @property string $key
	 * @property string $description
	 * @method static \Illuminate\Database\Query\Builder|\App\ApiKey whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\ApiKey whereKey($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\ApiKey whereDescription($value)
	 */
	class ApiKey extends \Eloquent
	{
	}
}

namespace App
{
	/**
	 * App\GameItem
	 * @property integer $id
	 * @property string $name
	 * @property integer $appid
	 * @property integer $subappid
	 * @property integer $quantity
	 * @property float $price
	 * @property \Carbon\Carbon $created_at
	 * @property \Carbon\Carbon $updated_at
	 * @property string $image_url
	 * @property string $inspect_url
	 * @property float $avg_median_7
	 * @property float $avg_median_30
	 * @property float $avg_median_90
	 * @property float $trend_7
	 * @property float $trend_30
	 * @property float $trend_90
	 * @property integer $total_sold_7
	 * @property integer $total_sold_30
	 * @property integer $total_sold_90
	 * @property string $calculations_updated_at
	 * @property-read \App\CsglItem $csgolounge
	 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PriceHistory[] $pricehistory
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereId($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereName($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereAppid($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereSubappid($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereQuantity($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem wherePrice($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereCreatedAt($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereUpdatedAt($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereImageUrl($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereInspectUrl($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereAvgMedian7($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereAvgMedian30($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereAvgMedian90($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereTrend7($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereTrend30($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereTrend90($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereTotalSold7($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereTotalSold30($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereTotalSold90($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem whereCalculationsUpdatedAt($value)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem appID($appID)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem subAppID($subAppID)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem minPrice($minPrice)
	 * @method static \Illuminate\Database\Query\Builder|\App\GameItem maxPrice($maxPrice)
	 */
	class GameItem extends \Eloquent
	{
	}
}

