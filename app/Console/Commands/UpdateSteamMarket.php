<?php namespace App\Console\Commands;

use App\ComponentState;
use App\Helpers\Log;
use App\ItemData;
use App\PriceHistory;
use App\Scraper\Steam\MarketListing;
use App\Scraper\Steam\MarketSearch;
use App\Scraper\Steam\SteamApps;
use Carbon\Carbon;

class UpdateSteamMarket extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:steammarket';

	protected $signature = 'update:steammarket';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Scrape list of all Steam market apps, market search pages and market listing pages';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		try {
			Log::msg('Running Steam market apps update')->info();

			$steamAppsUpdator = new SteamApps();
			$steamAppsUpdator->update();

			Log::msg('Running item list update')->info();
			$startTime = microtime(true);

			$marketSearchScraper = new MarketSearch();
			$marketSearchScraper->update();

			$totalTime = (microtime(true) - $startTime);
			Log::msg('Item list updating finished, execution time: ' . $totalTime)->info()->echo();

			$time = microtime(true);
			Log::msg('Running market listing scraper')->info();

			$marketListingScraper = new MarketListing();
			$marketListingScraper->update();

			Log::msg('Updating item calculations for avg/trend/totalsold')->info();

			$calculator = new ItemData();
			$calculator->calculate();

			$totalTime = (microtime(true) - $time);
			Log::msg('Market listing pricehistory updating time: ' . $totalTime)->info()->echo();

			ComponentState::set(ComponentState::LAST_MARKET_LISTING_UPDATE_TIME, Carbon::createFromTimestamp(microtime(true) - $startTime)->toTimeString());
		} catch (\Exception $e) {
			Log::msg($e->__toString())->error();
		}
	}

}
