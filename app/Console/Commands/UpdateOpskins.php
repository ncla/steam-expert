<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Scraper\Opskins\SalesHistory;

class UpdateOpskins extends Command
{
	/**
	 * The name and signature of the console command.
	 * @var string
	 */
	protected $signature = 'update:opskins';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Update OPSkins sales history and calculate item data from it';

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
		$salesHistoryScrape = new SalesHistory();
		$salesHistoryScrape->update();
	}
}
