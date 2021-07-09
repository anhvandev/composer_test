<?php
include 'vendor/autoload.php';
//const EXTRACT_DIRECTORY = __DIR__ . '/composer';
//require_once(EXTRACT_DIRECTORY . '/vendor/autoload.php');
//putenv('COMPOSER_HOME=' . EXTRACT_DIRECTORY . '/composer/bin');
/*if (file_exists(EXTRACT_DIRECTORY . '/vendor/autoload.php') == true) {
    echo "Extracted autoload already exists. Skipping phar extraction as presumably it's already extracted.";
} else {
    $composerPhar = new Phar("Composer.phar");
    //php.ini setting phar.readonly must be set to 0
    $composerPhar->extractTo(EXTRACT_DIRECTORY);
}*/

//This requires the phar to have been extracted successfully.
//Use the Composer classes
use Composer\Console\Application;
use Composer\Command\UpdateCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use MyCode\MyComposer;

chdir(__DIR__);

//$input = new ArrayInput(array('command' => 'require', 'packages' => ['illuminate/auth']));
// $output = new BufferedOutput();
// // $output = new ConsoleOutput();
// $application = new Application();
// $application->setAutoExit(false);
// $application->run($input, $output); //, $output);
// echo '<pre>';
// // print_r($output->fetch());
// echo '</pre>';
// die;
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

