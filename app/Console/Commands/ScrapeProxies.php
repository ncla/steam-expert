<?php namespace App\Console\Commands;

use App\Proxy;

class ScrapeProxies extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:proxies';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Scrape proxies and insert them into database';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Scraping proxies' . PHP_EOL);

		$proxy = new Proxy();
		$proxy->scrape();

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
