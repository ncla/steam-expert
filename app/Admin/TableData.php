<?php

namespace app\Admin;

use Cache;

abstract class TableData
{

	protected static $types = [];
	protected static $select = [];

	/**
	 * @return array
	 */
	public static function getList()
	{
		return static::$types;
	}

	/**
	 * @return array
	 */
	public static function getCols()
	{
		return array_keys(static::$select);
	}

	/**
	 * @param $type
	 *
	 * @return array
	 */
	public static function getItems($type)
	{
		$key = debug_backtrace()[1]['function'] . $type;
		$cached = Cache::get($key);

		if ($cached) {
			return $cached;
		}

		$data = static::readyItems($type);

		if (app()->environment('production')) {
			Cache::add($key, $data, 10);
		}

		return $data;
	}

	/**
	 * @param $type
	 *
	 * @return array
	 */
	public static function readyItems($type)
	{
		return [];
	}
}