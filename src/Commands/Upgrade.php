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

class Upgrade extends Command
{
    protected function configure()
    {
        $this->setName('upgrade')
            ->setDescription('Upgrade avhost to the latest release.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $url = "https://api.github.com/repos/jpuck/avhost/releases/latest";
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'jpuck/avhost');

        $data = json_decode(curl_exec($curl_handle), true);
        curl_close($curl_handle);

        $url = $data['assets'][0]['browser_download_url'];

        $curl = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
        ];

        curl_setopt_array($curl, $options);

        $responseData = curl_exec($curl);

        if (curl_errno($curl)){
            throw new Exception(curl_error($curl));
        } else {
            // TODO: Handle HTTP status code and response data
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }

        curl_close($curl);

        $localFilename = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];

        file_put_contents($localFilename, $responseData);

        chmod($localFilename, 0755);
    }
}
