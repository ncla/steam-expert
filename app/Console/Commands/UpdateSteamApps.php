<?php

namespace App\Console\Commands;

use App\Scraper\Steam\SteamApps;

class UpdateSteamApps extends BaseCommand
{
	/**
	 * The name and signature of the console command.
	 * @var string
	 */
	protected $signature = 'update:steamapps';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Update list of all Steam apps that have marketplace';

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
		$scraper = new SteamApps();
		$scraper->update();
	}
}
