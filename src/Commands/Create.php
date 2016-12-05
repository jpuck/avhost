<?php
namespace jpuck\avhost\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
			)->addOption(
				'forbidden-default',
				null,
				InputOption::VALUE_NONE,
				'Forbid requests for undefined hosts'
			);
	}

	public function execute(InputInterface $input, OutputInterface $output){
		if($input->getOption('forbidden-default')){
			$opts['forbidden'] = true;
			$hostname = '0000-forbidden.example.com';
			$directory = sys_get_temp_dir();
		} else {
			$hostname  = $input->getArgument('hostname');
			$directory = $input->getArgument('directory');
		}

		// check explicit values first
		$ssl['crt'] = $input->getOption('ssl-certificate');
		$ssl['key'] = $input->getOption('ssl-key');
		$ssl['chn'] = $input->getOption('ssl-chain');
		$ssl = array_filter($ssl);

		if(empty($ssl)){
			$ssl = null;

			// check for self-signed option
			if($input->getOption('ssl-self-sign')){
				$ssl = $this->createSelfSignedCertificate($hostname);
			}
		}

		if(!empty($ssl) && $input->getOption('no-require-ssl')){
			$ssl['req'] = false;
		}

		if($input->getOption('no-indexes')){
			$opts['indexes'] = false;
		}

		file_put_contents("$hostname.conf",
			new VHostTemplate($hostname, $directory,
				array_merge($ssl ?? [], $opts ?? [])
			)
		);
	}

	protected function createSelfSignedCertificate(String $hostname){
		$ssl['crt'] = "/etc/ssl/certs/$hostname.crt";
		$ssl['key'] = "/etc/ssl/private/$hostname.key";

		foreach($ssl as $file){
			if(file_exists($file)){
				throw new RuntimeException("$file already exists.");
			}

			if(!is_writable(dirname($file))){
				throw new RuntimeException(
					"$file is not writable. Run with sudo"
				);
			}
		}

		$command = "openssl req -x509 -nodes -sha256 -days 3650 ".
			"-newkey rsa:2048 -keyout $ssl[key] ".
			"-out $ssl[crt] ".
			"-subj '/CN=$hostname'";

		$process = new Process($command);
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}

		// openssl sends informational output to stderr
		// http://unix.stackexchange.com/a/131400/148062
		echo $process->getErrorOutput();

		return $ssl;
	}
}
