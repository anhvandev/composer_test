<?php
namespace MyCode;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
class MyComposer
{
    public static function preinstallcmd()
    {
        echo 'pre-install-cmd';
    }
    
    public static function postinstallcmd()
    {
        echo 'post-install-cmd';
    }
    public static function preupdatecmd()
    {
        echo 'pre-update-cmd';
    }
    public static function postupdatecmd()
    {
        echo 'post-update-cmd';
    }
    public static function prestatuscmd()
    {
        echo 'pre-status-cmd';
    }
    public static function poststatuscmd()
    {
        echo 'post-status-cmd';
    }
    public static function prearchivecmd()
    {
        echo 'pre-archive-cmd';
    }
    public static function postarchivecmd()
    {
        echo 'post-archive-cmd';
    }
    public static function preautoloaddump()
    {
        echo 'pre-autoload-dump';
    }
    public static function postautoloaddump()
    {
        echo 'post-autoload-dump';
    }
    public static function postrootpackageinstall()
    {
        echo 'post-root-package-install';
    }
    public static function postcreateprojectcmd()
    {
        echo 'post-create-project-cmd';
    }
    public static function preoperationsexec()
    {
        echo 'pre-operations-exec';
    }
    public static function prepackageinstall()
    {
        echo 'pre-package-install';
    }
    public static function postpackageinstall()
    {
        echo 'post-package-install';
    }
    public static function prepackageupdate()
    {
        echo 'pre-package-update';
    }
    public static function postpackageupdate()
    {
        echo 'post-package-update';
    }
    public static function prepackageuninstall()
    {
        echo 'pre-package-uninstall';
    }
    public static function postpackageuninstall()
    {
        echo 'post-package-uninstall';
    }
}
