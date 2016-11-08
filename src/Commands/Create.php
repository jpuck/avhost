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
				'hostname',
				InputArgument::REQUIRED,
				'Name of the virtual host, e.g. www.example.com'
			)->addArgument(
				'directory',
				InputArgument::REQUIRED,
				'File system folder to serve as document root'
			)->addOption(
				'ssl-certificate',
				'c',
				InputOption::VALUE_REQUIRED,
				'SSL certificate'
			)->addOption(
				'ssl-key',
				'k',
				InputOption::VALUE_REQUIRED,
				'SSL key'
			)->addOption(
				'ssl-chain',
				'C',
				InputOption::VALUE_REQUIRED,
				'SSL certificate intermediate chain'
			)->addOption(
				'no-require-ssl',
				null,
				InputOption::VALUE_NONE,
				'Do not redirect plain hosts to encrypted connection'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output){
	}
}
