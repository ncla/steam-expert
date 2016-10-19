<?php namespace App\Console\Commands;

class UpdateLounge extends BaseCommand
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'update:lounge';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Update CS:GO Lounge values';

	/**
	 * Execute the console command.
	 * @return mixed
	 */
	public function handle()
	{
		$this->comment(PHP_EOL . 'Fetching CS:GO Lounge values' . PHP_EOL);

		$goItems = new \App\Scraper\Lounge\GoItems();
		$goItems->update();

		$this->comment(PHP_EOL . 'Task finished' . PHP_EOL);
	}

}
