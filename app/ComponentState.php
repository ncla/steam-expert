<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComponentState extends Model
{

	protected $table = 'component_states';

	protected $fillable = ['name', 'state', 'updated_at'];

	/** List of components */
	const GOOD_PROXY_COUNT = 'Good proxy count';
	const SYSTEM_UPTIME = 'System uptime';
	const SYSTEM_TIME = 'System time';
	const CURRENTLY_RUNNING_COMMAND = 'Currently running command';
	const LAST_MARKET_LISTING_UPDATE_TIME = 'Last marketlisting update time';

	/** Default icon is calendar  */
	private static $icons = [
		self::GOOD_PROXY_COUNT                => 'thumbs-o-up',
		self::SYSTEM_UPTIME                   => 'hand-o-up',
		self::SYSTEM_TIME                     => 'clock-o',
		self::CURRENTLY_RUNNING_COMMAND       => 'gears',
		self::LAST_MARKET_LISTING_UPDATE_TIME => 'clock-o'
	];

	/**
	 * @param String $name - component name/keyword
	 * @param String $state - new component state
	 *
	 * @return bool
	 */
	public static function set($name, $state)
	{
		return DB::statement('REPLACE INTO `component_states` SET `name` = ?, `state` = ?', [$name, $state]);
	}

	/**
	 * @param array $options
	 *
	 * @return bool
	 */
	public function save(array $options = [])
	{
		if (isset($options['name'], $options['state'])) {
			return $this->set($options['name'], $options['state']);
		}

		return false;
	}

	/**
	 * @return ComponentState[]
	 */
	public static function getAll()
	{
		$data = DB::table('component_states')->select(['name', 'state', 'updated_at'])->get();
		$data = array_merge($data, self::getStaticComponentStates());
		foreach ($data as $d) {
			if (isset(self::$icons[ $d->name ])) {
				$d->icon = self::$icons[ $d->name ];
			}
		}

		return $data;
	}

	/**
	 * @return ComponentState[]
	 */
	private static function getStaticComponentStates()
	{
		$updated_at = Carbon::now()->toDateTimeString();
		preg_match("/([0-9]+:[0-9]+:[0-9]+ )(?:up )(.+)(?=,  [0-9]+ user)/", exec('uptime'), $uptime);

		$components = [
			['System uptime', $uptime[2]],
			['System time', Carbon::now()->toTimeString()]
		];

		$staticStates = [];
		foreach ($components as $c) {
			$staticStates[] = new self(['name' => $c[0], 'state' => $c[1], 'updated_at' => $updated_at]);
		}

		return $staticStates;
	}
}
