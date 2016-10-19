<?php namespace App;

use App\Helpers\Slacker;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

class PerformanceLog extends Model
{

	protected $table = 'perflogs';

	protected $fillable = ['stat', 'data'];

	/**
	 * Logs performance for stat with name $stat with data $data<br><br>
	 * All values in data are inserted as dynamic columns in perflogs.data.<br>
	 * All floats will be formatted to 3 decimal places.<br><br>
	 * PerformanceLog::add('market_listing_update', ['parsing_time' => 55.23523, 'saving_time' => 98345.2342,
	 *      'end_datetime' => Carbon::now()->toDateTimeString(), 'whatever' => 'this is swag']);
	 *
	 * @param $stat - keyword to group by
	 * @param $data - associative array
	 *
	 * @return boolean
	 */
	public static function add($stat, array $data)
	{
		if (!$stat || !$data) {
			return false;
		}

		$vals = [];
		$q = 'INSERT INTO `perflogs`(stat, added, data) VALUES(:stat_name, :added_datetime, COLUMN_CREATE(';

		$i = 0;
		foreach ($data as $key => $value) {
			$type = self::getSQLType($value);
			if (!$type) {
				return false;
			}
			$q .= ($i != 0 ? ',' : '');
			$vals[ $k = ':key' . ++$i ] = $key;
			$vals[ $v = ':val' . $i ] = $value;
			$q .= $k . ',' . $v . ' AS ' . $type;
		}
		$q .= '))';

		$vals[':stat_name'] = $stat;
		$vals[':added_datetime'] = Carbon::now()->toDateTimeString();

		return DB::statement($q, $vals);
	}

	/**
	 * @param $val
	 *
	 * @return null|string
	 */
	private static function getSQLType($val)
	{
		if (is_int($val)) {
			return 'INTEGER';
		}

		if (is_float($val)) {
			return 'DECIMAL(9,3)';
		}

		try {
			if (Carbon::createFromFormat('Y-m-d H:i:s', $val)) {
				return 'DATETIME';
			}
		} catch (\Exception $e) {
		}

		if (is_string($val) || $val === null) {
			return 'CHAR';
		}

		Slacker::log('Cannot find sql data type ' . var_export($val, true), '@ilmars');

		return null;
	}

	/**
	 * 'data' will be in JSON form
	 *
	 * @param string $stat - stat name
	 *
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public static function selectAll($stat)
	{
		return DB::table('perflogs')->selectRaw('stat, added, COLUMN_JSON(data) AS data')->where('stat', $stat);
	}

	/**
	 * @param $col - dynamic column name
	 *
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public static function selectCol($col)
	{
		return DB::table('perflogs')->selectRaw('stat, added, COLUMN_GET(data, ?) AS ?', [$col, $col]);
	}

	/**
	 * Returns an array of all stat keywords
	 * @return String[]
	 */
	public static function getStats()
	{
		return DB::table('perflogs')->distinct()->pluck('stat');
	}

}
