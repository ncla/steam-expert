<?php namespace App\Console\Commands;

class UpdateMarketSearch extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:marketsearch';

	protected $signature = 'update:marketsearch {app?} {con-limit?} {req-limit?} {--no-save}';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Update market values and quantity from Steam market';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Fetching market prices from market search pages' . PHP_EOL);

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

		$steamMarketScraper = new \App\Scraper\Steam\MarketSearch();
		$steamMarketScraper->update($app, $concurrentLimit, $requestLimit, $this->option('no-save'));

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
