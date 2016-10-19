<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\UpdateMarketSearch',
		'App\Console\Commands\ScrapeProxies',
		'App\Console\Commands\UpdateLounge',
		'App\Console\Commands\TestProxies',
		'App\Console\Commands\UpdateMarketListing',
		'App\Console\Commands\CalculateItemData',
		'App\Console\Commands\UpdateCurrencies',
		'App\Console\Commands\UpdateSteamApps',
		'App\Console\Commands\UpdateSteamMarket',
		'App\Console\Commands\ScrapeSteamCompanion',
		'App\Console\Commands\ScrapeSteamAnalyst',
		'App\Console\Commands\UpdateOpskins'
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule $schedule
	 *
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('update:proxies')
			->daily()->name('Update proxies')->withoutOverlapping();

		$schedule->command('testproxies')
			->daily()->name('Test proxies')->withoutOverlapping();

		$schedule->command('update:lounge')
			->daily()->name('Update CS:GO Lounge item list')->withoutOverlapping();

		$schedule->command('update:steammarket')
			->hourly()->name('Update all Steam market related things')->withoutOverlapping();
	}
}
