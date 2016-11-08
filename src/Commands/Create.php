<?php
namespace jpuck\avhost\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command {
	protected function configure(){
		$this->setName('create')
			->setDescription('Create a virtual host.')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'What name would you like to use for the host?'
			);
	}
	public function execute(InputInterface $input, OutputInterface $output){
	}
}
