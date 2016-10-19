<?php

namespace App\Helpers;

use App\ComponentState;
use App\PerformanceLog;
use Mockery\Exception;
use Monolog\Formatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler;
use Monolog\Logger;

/**
 * @class Log
 * @method Log critical()
 * @method Log error()
 * @method Log alert()
 * @method Log emergency()
 * @method Log notice()
 * @method Log info()
 * @method Log debug()
 */
final class Log
{
	private static $loggers = [];
	private static $shouldEcho;
	private $message;
	private $perflogs;
	private $isProd;
	private $activeLogger;
	private $callerNS;
	private $callerClass;
	private $callStack;

	private function __construct()
	{
		$this->callerNS = $this->getCallerNameSpace();
		$this->callerClass = $this->getCallerClassName();
		$this->isProd = app()->environment('production');

		$this->callStack = '';
		if ($this->callerNS) {
			$this->callStack .= '[ ' . $this->callerNS . ' ]';
		}
		if ($this->callerClass) {
			$this->callStack .= '[ ' . $this->callerClass . ' ]';
		}

		$this->setLogger();

		return $this;
	}

	/**
	 * @param int $nsLevel - namespace level
	 * @param int $bkLevel - debug_backtrace level
	 *
	 * @return string|''
	 */
	private static function getCallerNameSpace($nsLevel = 1, $bkLevel = 3)
	{
		try {
			$callerClass = self::getCallerClassOrObject($bkLevel + 1);
			try {
				$callerNS = (new \ReflectionClass($callerClass))->getNamespaceName();
			} catch (\ReflectionException $e) {
				return 'App';
			}

			$namespaces = explode('\\', $callerNS);

			for ($i = $nsLevel + 1; $i >= 0; --$i) {
				try {
					return $namespaces[ $i ];
				} catch (\Exception $e) {

				}
			}

		} catch (Exception $e) {
			Slacker::log('Log::getCallerName failed' . '\n' . (!isset($callerClass) ?: var_export($callerClass, true)) . '\n' . var_export($e, true), '@ilmars');
		}

		return '';
	}

	private static function getCallerClassOrObject($bkLevel)
	{
		$backTrace = debug_backtrace();
		if (isset($backTrace[ $bkLevel ]['object'])) {
			$callerClass = get_class($backTrace[ $bkLevel ]['object']);
		} else {
			$callerClass = isset($backTrace[ $bkLevel ]['class']) ? $backTrace[ $bkLevel ]['class'] : '';
		}

		return $callerClass;
	}

	/**
	 * @param int $bkLevel - debug_backtrace level
	 *
	 * @return string|''
	 */
	private static function getCallerClassName($bkLevel = 3)
	{
		try {
			$callerClass = self::getCallerClassOrObject($bkLevel + 1);
			$levels = explode('\\', $callerClass);

			return end($levels);
		} catch (\Exception $e) {
			Slacker::log('Log::getCallerName failed' . '\n' . (!isset($callerClass) ?: var_export($callerClass, true)) . '\n' . var_export($e, true), '@ilmars');

			return '';
		}
	}

	/**
	 * Sets $this->logger to $name or Caller class' namespace after App/
	 * @return $this
	 */
	private function setLogger()
	{
		$loggerName = $this->callerClass ? $this->callerClass : $this->callerNS;
		$loggerName = $loggerName ? $loggerName : 'App';

		if ($loggerName && isset(self::$loggers[ $loggerName ])) {
			$this->activeLogger = self::$loggers[ $loggerName ];
		} else {
			$handler = new Handler\RotatingFileHandler(base_path('storage/logs/') . $loggerName . '.log', 3, Logger::DEBUG, false);
			$handler->setFormatter(new LineFormatter(null, null, true, true));

			$this->activeLogger = new Logger($loggerName, [$handler]);
			self::$loggers[ $loggerName ] = $this->activeLogger;
		}

		return $this;
	}

	/**
	 * disables $this->echo() globally
	 * Used for debugging
	 */
	public static function disableEcho()
	{
		self::$shouldEcho = false;
	}

	/**
	 * @param $message
	 * returns null when $message is not a string or empty
	 *
	 * @return Log|null
	 */
	public static function msg($message)
	{
		if (!empty($message) && is_string($message)) {
			$instance = new self();

			return $instance->setMessage($message);
		} else {
			return null;
		}
	}

	/**
	 * @param mixed $message
	 *
	 * @return $this
	 */
	public function setMessage($message)
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * @param array $data - assoc array
	 *
	 * @example
	 *        $data = [
	 *            'Inspect link saving' => 1600,
	 *            'Description saving'  => true
	 *        ];
	 *        Log::array($data)->info()->slack()->perflog()->echo;
	 * @comment
	 * This will log 'Inspect link saving: {$inspectLinksSaving} Description saving: {$descriptionSavingTime}'
	 * returns null when $data is not an array or is empty
	 * @return $this|null
	 */
	public static function array($data = [])
	{
		if (is_array($data) && !empty($data)) {
			$msg = '';
			foreach ($data as $kw => $val) {
				if (is_float($val)) {
					$val = round($val);
				} elseif (is_string($val)) {
					$val = trim($val);
				}

				$msg .= trim($kw) . ': ' . $val . ' ';
			}

			$instance = new self();

			return $instance->setMessage($msg)
				->setPerflogs($data);

		} else {
			return null;
		}
	}

	/**
	 * @param mixed $perflogs
	 *
	 * @return $this
	 */
	public function setPerflogs($perflogs)
	{
		$this->perflogs = $perflogs;

		return $this;
	}

	public function cs($component, $state)
	{
		ComponentState::set($component, $state);

		return $this;
	}

	/**
	 * @param string $prefix
	 *
	 * @return $this
	 */
	public function slack($prefix = '')
	{
		if (!Slacker::log($this->callStack . ' ' . $this->message, $prefix)) {
			$this->warning('Failed to push to slack: ' . $prefix . $this->callStack . $this->message);
		}

		return $this;
	}

	protected function warning($msg = '')
	{
		\Log::getMonolog()->warning($msg);

		return $this;
	}

	/**
	 * @param boolean $inProd - executes echo if project is in production
	 *
	 * @return $this
	 */
	public function echo ($inProd = false)
	{
		if (($inProd || !$this->isProd) && self::$shouldEcho !== false) {
			echo $this->message, PHP_EOL;
		}

		return $this;
	}

	/**
	 * @param $kw - perflog keyword override
	 * @param array $data - perflog array override
	 *
	 * @return $this
	 */
	public function perflog($kw = '', $data = [])
	{
		$this->perflogs = $data ? $data : $this->perflogs;

		if (!$this->perflogs) {
			Slacker::log('LOG : No perflogs to log ' . var_export($this, true), '@ilmars');

			return $this;
		}

		if (!$kw) {
			$kw = '';
			if ($this->callerNS) {
				$kw .= $this->callerNS;
			}
			if ($this->callerClass) {
				!$kw ?: $kw .= '_';
				$kw .= $this->callerClass;
			}
		}

		PerformanceLog::add($kw, $this->perflogs);

		return $this;
	}

	/**
	 * @param $name
	 * @param $arguments
	 * Calls $this->logger->{$name}()
	 *
	 * @return $this
	 */
	public function __call($name, $arguments)
	{
		if (!isset($this->activeLogger)) {
			$this->activeLogger = \Log::getMonolog();
		}

		if (method_exists($this->activeLogger, $name)) {
			call_user_func_array([$this->activeLogger, $name], [$this->message]);
		}

		return $this;
	}

}