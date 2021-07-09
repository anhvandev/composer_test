<?php
namespace MyCode;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\EventDispatcher\Event as EventDispatcher;
use Composer\Installer\InstallerEvent;
use Composer\Plugin\CommandEvent;
class MyComposer
{
    private static $pack = [];

    public static function getEvent(EventDispatcher $ev)
    {
        // dump($ev);
    }
    
    public static function preinstallcmd()
    {
        // echo 'pre-install-cmd'.'<br/>';
    }
    
    public static function postinstallcmd()
    {
        // echo 'post-install-cmd'.'<br/>';
    }
    public static function preupdatecmd()
    {
        // echo 'pre-update-cmd'.'<br/>';
    }
    public static function postupdatecmd()
    {
        // echo 'post-update-cmd'.'<br/>';
    }
    public static function prestatuscmd()
    {
        // echo 'pre-status-cmd'.'<br/>';
    }
    public static function poststatuscmd()
    {
        // echo 'post-status-cmd'.'<br/>';
    }
    public static function prearchivecmd()
    {
        // echo 'pre-archive-cmd'.'<br/>';
    }
    public static function postarchivecmd()
    {
        // echo 'post-archive-cmd'.'<br/>';
    }
    public static function preautoloaddump()
    {
        // echo 'pre-autoload-dump'.'<br/>';
    }
    public static function postautoloaddump()
    {
        // echo 'post-autoload-dump'.'<br/>';
    }
    public static function postrootpackageinstall()
    {
        // echo 'post-root-package-install'.'<br/>';
    }
    public static function postcreateprojectcmd()
    {
        // echo 'post-create-project-cmd'.'<br/>';
    }
    public static function preoperationsexec(InstallerEvent $ev)
    {
        //dump(get_class_methods($ev));
        //dump($ev->getTransaction());
        // echo 'pre-operations-exec'.'<br/>';
    }
    public static function prepackageinstall()
    {
        // echo 'pre-package-install'.'<br/>';
    }
    public static function postpackageinstall(PackageEvent $ev)
    {
        //self::$pack[] = $ev->getOperation()->getPackage();
        //dump(self::$pack);
        // dump($ev->getOperation()->getPackage());
        // echo 'post-package-install'.'<br/>';
    }
    public static function prepackageupdate()
    {
        // echo 'pre-package-update'.'<br/>';
    }
    public static function postpackageupdate()
    {
        // echo 'post-package-update'.'<br/>';
    }
    public static function prepackageuninstall()
    {
        // echo 'pre-package-uninstall'.'<br/>';
    }
    public static function postpackageuninstall(PackageEvent $evt)
    {
        // dump($evt);
        // echo 'post-package-uninstall'.'<br/>';
    }
    public static function command(CommandEvent $evt)
    {
        // dump($evt);
        // echo 'post-package-uninstall'.'<br/>';
    }
}
