<?php











namespace Composer\Util;






class Platform
{






public static function expandPath($path)
{
if (preg_match('#^~[\\/]#', $path)) {
return self::getUserDirectory() . substr($path, 1);
}

return preg_replace_callback('#^(\$|(?P<percent>%))(?P<var>\w++)(?(percent)%)(?P<path>.*)#', function ($matches) {

 if (Platform::isWindows() && $matches['var'] == 'HOME') {
return (getenv('HOME') ?: getenv('USERPROFILE')) . $matches['path'];
}

return getenv($matches['var']) . $matches['path'];
}, $path);
}





public static function getUserDirectory()
{
if (false !== ($home = getenv('HOME'))) {
return $home;
}

if (self::isWindows() && false !== ($home = getenv('USERPROFILE'))) {
return $home;
}

if (\function_exists('posix_getuid') && \function_exists('posix_getpwuid')) {
$info = posix_getpwuid(posix_getuid());

return $info['dir'];
}

throw new \RuntimeException('Could not determine user directory');
}




public static function isWindows()
{
return \defined('PHP_WINDOWS_VERSION_BUILD');
}





public static function strlen($str)
{
static $useMbString = null;
if (null === $useMbString) {
$useMbString = \function_exists('mb_strlen') && ini_get('mbstring.func_overload');
}

if ($useMbString) {
return mb_strlen($str, '8bit');
}

return \strlen($str);
}

public static function isTty($fd = null)
{
if ($fd === null) {
$fd = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');
}


 
 if (in_array(strtoupper(getenv('MSYSTEM') ?: ''), array('MINGW32', 'MINGW64'), true)) {
return true;
}


 
 if (function_exists('stream_isatty')) {
return stream_isatty($fd);
}


 if (function_exists('posix_isatty') && posix_isatty($fd)) {
return true;
}

$stat = @fstat($fd);

 return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
}
}
