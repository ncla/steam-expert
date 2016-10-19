<?php namespace App\Console\Commands;

use App\ComponentState;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->name) {
			ComponentState::set(ComponentState::CURRENTLY_RUNNING_COMMAND, $this->name);
		}

		parent::execute($input, $output);

		if ($this->name) {
			ComponentState::set(ComponentState::CURRENTLY_RUNNING_COMMAND, 'none');
		}
	}
}