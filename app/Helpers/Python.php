<?php

namespace App\Helpers;

final class Python
{
	const ProxyScraper = 'proxies.py';
	const SALogin = 'login_to_sa.py';

	/**
	 * @param $path - path to python script relative to app/Python folder
	 * @param string $params
	 *
	 * @return \mixed[]|null
	 */
	public static final function run($path, $params = '')
	{
		$handle = popen('python ' . app_path() . '/Python/' . $path . ' ' . $params, "r");
		$output = fread($handle, 8192);

		$data = json_decode($output, true);

		if ($data == null && json_last_error() !== JSON_ERROR_NONE) {
			Log::msg('Failed to get python output for ' . self::getConstName($path) . ': ' . json_last_error_msg())
				->slack('@ncla')->error()->echo();
		}

		return $data;
	}

	public static final function getConstName($cValue)
	{
		$oClass = new \ReflectionClass(__CLASS__);
		$constants = $oClass->getConstants();
		$constants = array_flip($constants);

		return isset($constants[ $cValue ]) ? $constants[ $cValue ] : $cValue;
	}
}