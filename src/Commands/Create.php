<?php
namespace jpuck\avhost\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use jpuck\avhost\VHostTemplate;

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
				'no-indexes',
				null,
				InputOption::VALUE_NONE,
				'Do not allow directory indexes'
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
				'ssl-self-sign',
				'S',
				InputOption::VALUE_NONE,
				'Create a self-signed certificate'
			)->addOption(
				'no-require-ssl',
				null,
				InputOption::VALUE_NONE,
				'Do not redirect plain hosts to encrypted connection'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output){
		$hostname  = $input->getArgument('hostname');
		$directory = $input->getArgument('directory');

		$ssl['crt'] = $input->getOption('ssl-certificate');
		$ssl['key'] = $input->getOption('ssl-key');
		$ssl['chn'] = $input->getOption('ssl-chain');
		$ssl = array_filter($ssl);

		if($input->getOption('no-require-ssl')){
			$ssl['req'] = false;
		}

		if(empty($ssl)){
			$ssl = null;
		}

		if($input->getOption('no-indexes')){
			$opts['indexes'] = false;
		}

		file_put_contents("$hostname.conf",
			new VHostTemplate($hostname, $directory, $ssl, $opts ?? null)
		);
	}
}
