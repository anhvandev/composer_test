<?php











namespace Composer\Util;

use Composer\Downloader\DownloaderInterface;
use Composer\Package\PackageInterface;
use React\Promise\PromiseInterface;

class SyncHelper
{











public static function downloadAndInstallPackageSync(Loop $loop, DownloaderInterface $downloader, $path, PackageInterface $package, PackageInterface $prevPackage = null)
{
$type = $prevPackage ? 'update' : 'install';

try {
self::await($loop, $downloader->download($package, $path, $prevPackage));

self::await($loop, $downloader->prepare($type, $package, $path, $prevPackage));

if ($type === 'update') {
self::await($loop, $downloader->update($package, $path, $prevPackage));
} else {
self::await($loop, $downloader->install($package, $path, $prevPackage));
}
} catch (\Exception $e) {
self::await($loop, $downloader->cleanup($type, $package, $path, $prevPackage));
throw $e;
}

self::await($loop, $downloader->cleanup($type, $package, $path, $prevPackage));
}







public static function await(Loop $loop, PromiseInterface $promise = null)
{
if ($promise) {
$loop->wait(array($promise));
}
}
}
