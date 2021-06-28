<?php
namespace MyCode;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
class MyComposer
{
    public static function installed(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        echo '<pre>';
        print_r($installedPackage);
        echo '</pre>';
        die;
    }
}
