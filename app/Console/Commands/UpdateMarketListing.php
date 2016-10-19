<?php namespace App\Console\Commands;

class UpdateMarketListing extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:marketlisting';

	protected $signature = 'update:marketlisting {app?} {con-limit?} {req-limit?} {--no-save} {--calculate}';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Update market listings sales history from Steam market';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Fetching market listings' . PHP_EOL);

		$arguments = $this->argument();

		$app = $arguments['app'];

		if ($app !== null) {
			$dummyClass = new \stdClass();
			$dummyClass->appid = intval(str_replace('app=', '', $app));

			$app = [$dummyClass];
		} else {
			$app = [];
		}

		$concurrentLimit = ($arguments['con-limit'] !== null ? intval($arguments['con-limit']) : -1);
		$requestLimit = ($arguments['req-limit'] !== null ? intval($arguments['req-limit']) : -1);

		$steamMarketScraper = new \App\Scraper\Steam\MarketListing($this->option('calculate'));
		$steamMarketScraper->update($app, $concurrentLimit, $requestLimit, $this->option('no-save'));

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
