<?php namespace App\Console\Commands;

use App\ItemData;

class CalculateItemData extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:itemdata';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Calculate trending %, average and total sold for past 7/30/90 days from Steam data';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Updating item calculations table' . PHP_EOL);

		$calculator = new ItemData();
		$calculator->calculate();

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
