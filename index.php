<?php
include 'vendor/autoload.php';


use Composer\Console\Application;
use Composer\Command\UpdateCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use MyCode\MyComposer;

chdir(__DIR__);

try
{
    $output = new BufferedOutput();
    $application = new Application();
    $application->setAutoExit(false);
    $cli_args = new \Symfony\Component\Console\Input\StringInput('require navi/alo');
    $exitcode = $application->run($cli_args, $output);
    $txt = $output->fetch();
    dump($txt);
    $re = '/Using version\s+(.*?)\s+for|\s+for\s+(.*?)[\r\n]+.\/composer.json/mi';
    preg_match_all($re, $txt, $matches, PREG_SET_ORDER, 0);
    dump($matches);
}
catch(\Exception $e)
{
    dump($e->getTrace());
}

