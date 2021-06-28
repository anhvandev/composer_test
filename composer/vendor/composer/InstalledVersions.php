<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
'root' => 
array (
'pretty_version' => '2.0.11',
'version' => '2.0.11.0',
'aliases' => 
array (
),
'reference' => 'a5a5632da0b1c2d6fa9a3b65f1f4e90d1f04abb9',
'name' => 'composer/composer',
),
'versions' => 
array (
'composer/ca-bundle' => 
array (
'pretty_version' => '1.2.9',
'version' => '1.2.9.0',
'aliases' => 
array (
),
'reference' => '78a0e288fdcebf92aa2318a8d3656168da6ac1a5',
),
'composer/composer' => 
array (
'pretty_version' => '2.0.11',
'version' => '2.0.11.0',
'aliases' => 
array (
),
'reference' => 'a5a5632da0b1c2d6fa9a3b65f1f4e90d1f04abb9',
),
'composer/semver' => 
array (
'pretty_version' => '3.2.4',
'version' => '3.2.4.0',
'aliases' => 
array (
),
'reference' => 'a02fdf930a3c1c3ed3a49b5f63859c0c20e10464',
),
'composer/spdx-licenses' => 
array (
'pretty_version' => '1.5.5',
'version' => '1.5.5.0',
'aliases' => 
array (
),
'reference' => 'de30328a7af8680efdc03e396aad24befd513200',
),
'composer/xdebug-handler' => 
array (
'pretty_version' => '1.4.5',
'version' => '1.4.5.0',
'aliases' => 
array (
),
'reference' => 'f28d44c286812c714741478d968104c5e604a1d4',
),
'justinrainbow/json-schema' => 
array (
'pretty_version' => '5.2.10',
'version' => '5.2.10.0',
'aliases' => 
array (
),
'reference' => '2ba9c8c862ecd5510ed16c6340aa9f6eadb4f31b',
),
'psr/log' => 
array (
'pretty_version' => '1.1.3',
'version' => '1.1.3.0',
'aliases' => 
array (
),
'reference' => '0f73288fd15629204f9d42b7055f72dacbe811fc',
),
'react/promise' => 
array (
'pretty_version' => 'v1.2.1',
'version' => '1.2.1.0',
'aliases' => 
array (
),
'reference' => 'eefff597e67ff66b719f8171480add3c91474a1e',
),
'seld/jsonlint' => 
array (
'pretty_version' => '1.8.3',
'version' => '1.8.3.0',
'aliases' => 
array (
),
'reference' => '9ad6ce79c342fbd44df10ea95511a1b24dee5b57',
),
'seld/phar-utils' => 
array (
'pretty_version' => '1.1.1',
'version' => '1.1.1.0',
'aliases' => 
array (
),
'reference' => '8674b1d84ffb47cc59a101f5d5a3b61e87d23796',
),
'symfony/console' => 
array (
'pretty_version' => 'v2.8.52',
'version' => '2.8.52.0',
'aliases' => 
array (
),
'reference' => 'cbcf4b5e233af15cd2bbd50dee1ccc9b7927dc12',
),
'symfony/debug' => 
array (
'pretty_version' => 'v2.8.52',
'version' => '2.8.52.0',
'aliases' => 
array (
),
'reference' => '74251c8d50dd3be7c4ce0c7b862497cdc641a5d0',
),
'symfony/filesystem' => 
array (
'pretty_version' => 'v2.8.52',
'version' => '2.8.52.0',
'aliases' => 
array (
),
'reference' => '7ae46872dad09dffb7fe1e93a0937097339d0080',
),
'symfony/finder' => 
array (
'pretty_version' => 'v2.8.52',
'version' => '2.8.52.0',
'aliases' => 
array (
),
'reference' => '1444eac52273e345d9b95129bf914639305a9ba4',
),
'symfony/polyfill-ctype' => 
array (
'pretty_version' => 'v1.19.0',
'version' => '1.19.0.0',
'aliases' => 
array (
),
'reference' => 'aed596913b70fae57be53d86faa2e9ef85a2297b',
),
'symfony/polyfill-mbstring' => 
array (
'pretty_version' => 'v1.19.0',
'version' => '1.19.0.0',
'aliases' => 
array (
),
'reference' => 'b5f7b932ee6fa802fc792eabd77c4c88084517ce',
),
'symfony/process' => 
array (
'pretty_version' => 'v2.8.52',
'version' => '2.8.52.0',
'aliases' => 
array (
),
'reference' => 'c3591a09c78639822b0b290d44edb69bf9f05dc8',
),
),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
