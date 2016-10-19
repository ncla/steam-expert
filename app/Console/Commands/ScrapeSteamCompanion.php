<?php

namespace App\Console\Commands;

use App\Scraper\SteamCompanion\MainScraper;
use Illuminate\Console\Command;

class ScrapeSteamCompanion extends Command
{
	/**
	 * The name and signature of the console command.
	 * @var string
	 */
	protected $signature = 'update:companion';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Scrapes Steam Companion listed units and listed price history';

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
		$scraper->scrape();
	}
}
