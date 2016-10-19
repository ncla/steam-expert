<?php

use App\Helpers\Log;
use App\PerformanceLog;
use Carbon\Carbon;

class HelperTest extends \TestCase
{
	public function testLog()
	{
		$msgs = [];
		for ($i = 0; $i < 10; $i++) {
			$msgs[] = 'test' . $i;
		}

		// <editor-fold desc="perflogs">
		DB::table('perflogs')->whereIn('stat', $msgs)->orWhere('stat', 'HelperTest')->delete();

		$perflog = [
			'horse' => 'arse',
			'dick'  => 'butt'
		];

		// with ::array
		Log::array($perflog)->perflog($msgs[2]);
		$data = json_decode(PerformanceLog::selectAll($msgs[2])->get()[0]->data, true);
		$this->assertArraySubset($data, $perflog, false);

		// with ->setPerflogs
		Log::msg('swag')->setPerflogs($perflog)->perflog($msgs[3]);
		$data = json_decode(PerformanceLog::selectAll($msgs[3])->get()[0]->data, true);
		$this->assertArraySubset($data, $perflog, false);

		// purely with ->perflog
		Log::msg('swag')->perflog($msgs[4], $perflog);
		$data = json_decode(PerformanceLog::selectAll($msgs[4])->get()[0]->data, true);
		$this->assertArraySubset($data, $perflog, false);

		// without kw override
		Log::array($perflog)->perflog();
		$data = json_decode(PerformanceLog::selectAll('HelperTest')->get()[0]->data, true);
		$this->assertArraySubset($data, $perflog, false);

		DB::table('perflogs')->where('stat', 'HelperTest')->delete();
		Log::msg('swag')->setPerflogs($perflog)->perflog();
		$data = json_decode(PerformanceLog::selectAll('HelperTest')->get()[0]->data, true);
		$this->assertArraySubset($data, $perflog, false);

		DB::table('perflogs')->where('stat', 'HelperTest')->delete();
		Log::msg('swag')->perflog(null, $perflog);
		$data = json_decode(PerformanceLog::selectAll($msgs[4])->get()[0]->data, true);
		$this->assertArraySubset($data, $perflog, false);
		// </editor-fold desc="perflogs">

		// <editor-fold desc="slack and logger">
		$now = Carbon::now()->format('Y-m-d');
		$logFile = storage_path() . '/logs/' . 'HelperTest-' . $now . '.log';
		if (file_exists($logFile)) {
			unlink($logFile);
		}

		ob_start();
		Log::msg($msgs[0])->echo()
			->setMessage($msgs[1])
			->slack()
			->info()
			->critical()
			->alert()
			->debug()
			->error()
			->emergency()
			->warning()
			->notice();
		$out = ob_get_clean();

		$this->assertContains($msgs[0], $out);
		$this->assertContains('Intercepted Slack message: [ HelperTest ] ' . $msgs[1], $out);

		$this->assertFileExists(storage_path() . '/logs/' . 'HelperTest-' . $now . '.log');
		$log = file_get_contents($logFile);

		$this->assertContains('HelperTest.INFO: ' . $msgs[1], $log);
		$this->assertContains('HelperTest.CRITICAL: ' . $msgs[1], $log);
		$this->assertContains('HelperTest.ALERT: ' . $msgs[1], $log);
		$this->assertContains('HelperTest.DEBUG: ' . $msgs[1], $log);
		$this->assertContains('HelperTest.NOTICE: ' . $msgs[1], $log);
		$this->assertContains('HelperTest.EMERGENCY: ' . $msgs[1], $log);
		$this->assertContains('HelperTest.WARNING: ' . $msgs[1], $log);
		// </editor-fold desc="slack and log">

		DB::table('perflogs')->whereIn('stat', $msgs)->orWhere('stat', 'HelperTest')->delete();
		unlink($logFile);
	}
}