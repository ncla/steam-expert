<?php

/*
 * Taken from
 * https://github.com/laravel/framework/blob/5.2/src/Illuminate/Auth/Console/stubs/make/controllers/HomeController.stub
 */

namespace App\Http\Controllers;

use App\Admin\Comparisons;
use App\Admin\OddItems;
use App\Http\Requests;
use App\PerformanceLog;
use Carbon\Carbon;

/**
 * Class AdminController
 * @package App\Http\Controllers
 */
class AdminController extends Controller
{
	/**
	 * Create a new controller instance.

	 */
	public function __construct()
	{
		$this->middleware('auth');
	}

	/**
	 * Show the application dashboard.
	 * @return \Response
	 */
	public function index()
	{
		if (\Auth::user()->isAdmin()) {
			return view('admin.home');
		} else {
			return response()->view('errors.404');
		}
	}

	/**
	 * Generate performance overview for stat
	 *
	 * @param $statName
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function perflog($statName)
	{

		if (!$statName) {
			return response()->view('errors.404');
		}

		$datas = PerformanceLog::selectAll($statName)->get();
		if (!$datas) {
			return response()->view('errors.404');
		}

		$cols = [];
		$rows = [];

		foreach ($datas as $i => $data) {
			$datas[ $i ]->stats = $stats = json_decode($data->data);
			foreach ($stats as $stat => $val) {
				if ($stat != 'finish_time') {
					$cols[ $stat ] = $stat;
				} else {
					$cols['total_time'] = 'total_time';
				}
			}
			unset($datas[ $i ]->data);
		}

		foreach ($datas as $i => $data) {
			$row = [];
			foreach ($cols as $stat) {
				if ($stat == 'total_time' && isset($data->stats->finish_time, $data->stats->start_time)) {
					$row[ $stat ] = Carbon::createFromFormat('Y-m-d H:i:s', $data->stats->finish_time)
						->diff(Carbon::createFromFormat('Y-m-d H:i:s', $data->stats->start_time))->format('%H:%I:%S');
					continue;
				}
				$row[ $stat ] = isset($data->stats->$stat) ? $data->stats->$stat : null;
			}
			$rows[] = $row;
		}

		return self::table($cols, $rows, $statName, 'perflogs');
	}

	/**
	 * @param array $cols
	 * @param array $rows
	 * @param string $table_title
	 * @param string $type
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	private static function table(Array $cols, Array $rows, String $table_title, string $type)
	{
		return view('admin.table', ['cols' => $cols, 'rows' => $rows, 'table_title' => $table_title, 'type' => $type]);
	}

	/**
	 * @param $type
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
	 */
	public function oddItems($type)
	{
		$rows = OddItems::getItems($type);

		if (is_null($rows)) {
			return response()->view('errors.404');
		}

		return self::table(OddItems::getCols(), $rows, $type, 'odditems');
	}

	/**
	 * @param $type
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
	 */
	public function comparisons($type)
	{
		$rows = Comparisons::getItems($type);

		if (is_null($rows)) {
			return response()->view('errors.404');
		}

		return self::table(Comparisons::getCols(), $rows, $type, 'comparisons');
	}
}