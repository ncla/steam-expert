<?php namespace App\Console\Commands;

use App\Proxy;

class TestProxies extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'testproxies';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Test all the proxies from the database';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Testing proxies' . PHP_EOL);

		$proxy = new Proxy();
		$proxy->testAllProxies();

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
