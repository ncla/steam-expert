<?php

namespace App\Console\Commands;

use App\Scraper\SteamAnalyst\MainScraper;
use Illuminate\Console\Command;

class ScrapeSteamAnalyst extends Command
{
	/**
	 * The name and signature of the console command.
	 * @var string
	 */
	protected $signature = 'update:steamanalyst {--scrapeSupply} {--useDbList} {--saveGameItems}';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Scrape SteamAnalyst prices and averages, history.';

	/**
	 * Create a new command instance.
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$scraper = new MainScraper();
		$scraper->scrape($this->option('useDbList'), $this->option('saveGameItems'), $this->option('scrapeSupply'));
	}
}
