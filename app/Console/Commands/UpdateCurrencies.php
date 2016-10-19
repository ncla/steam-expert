<?php namespace App\Console\Commands;

use App\Scraper\Currencies\Currencies;

class UpdateCurrencies extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:currencies';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Update currency conversion rates';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Updating currency conversion rates' . PHP_EOL);

		$currenciesScraper = new Currencies();
		$currenciesScraper->scrape();

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
