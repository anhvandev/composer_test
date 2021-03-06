<?php











namespace Composer\Util;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;





class Filesystem
{

private $processExecutor;

public function __construct(ProcessExecutor $executor = null)
{
$this->processExecutor = $executor;
}

public function remove($file)
{
if (is_dir($file)) {
return $this->removeDirectory($file);
}

if (file_exists($file)) {
return $this->unlink($file);
}

return false;
}







public function isDirEmpty($dir)
{
$finder = Finder::create()
->ignoreVCS(false)
->ignoreDotFiles(false)
->depth(0)
->in($dir);

return \count($finder) === 0;
}

public function emptyDirectory($dir, $ensureDirectoryExists = true)
{
if (is_link($dir) && file_exists($dir)) {
$this->unlink($dir);
}

if ($ensureDirectoryExists) {
$this->ensureDirectoryExists($dir);
}

if (is_dir($dir)) {
$finder = Finder::create()
->ignoreVCS(false)
->ignoreDotFiles(false)
->depth(0)
->in($dir);

foreach ($finder as $path) {
$this->remove((string) $path);
}
}
}











public function removeDirectory($directory)
{
if ($this->isSymlinkedDirectory($directory)) {
return $this->unlinkSymlinkedDirectory($directory);
}

if ($this->isJunction($directory)) {
return $this->removeJunction($directory);
}

if (is_link($directory)) {
return unlink($directory);
}

if (!is_dir($directory) || !file_exists($directory)) {
return true;
}

if (preg_match('{^(?:[a-z]:)?[/\\\\]+$}i', $directory)) {
throw new \RuntimeException('Aborting an attempted deletion of '.$directory.', this was probably not intended, if it is a real use case please report it.');
}

if (!\function_exists('proc_open')) {
return $this->removeDirectoryPhp($directory);
}

if (Platform::isWindows()) {
$cmd = sprintf('rmdir /S /Q %s', ProcessExecutor::escape(realpath($directory)));
} else {
$cmd = sprintf('rm -rf %s', ProcessExecutor::escape($directory));
}

$result = $this->getProcess()->execute($cmd, $output) === 0;


 clearstatcache();

if ($result && !is_dir($directory)) {
return true;
}

return $this->removeDirectoryPhp($directory);
}











public function removeDirectoryPhp($directory)
{
try {
$it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
} catch (\UnexpectedValueException $e) {

 
 clearstatcache();
usleep(100000);
if (!is_dir($directory)) {
return true;
}
$it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
}
$ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

foreach ($ri as $file) {
if ($file->isDir()) {
$this->rmdir($file->getPathname());
} else {
$this->unlink($file->getPathname());
}
}

return $this->rmdir($directory);
}

public function ensureDirectoryExists($directory)
{
if (!is_dir($directory)) {
if (file_exists($directory)) {
throw new \RuntimeException(
$directory.' exists and is not a directory.'
);
}
if (!@mkdir($directory, 0777, true)) {
throw new \RuntimeException(
$directory.' does not exist and could not be created.'
);
}
}
}








public function unlink($path)
{
$unlinked = @$this->unlinkImplementation($path);
if (!$unlinked) {

 if (Platform::isWindows()) {
usleep(350000);
$unlinked = @$this->unlinkImplementation($path);
}

if (!$unlinked) {
$error = error_get_last();
$message = 'Could not delete '.$path.': ' . @$error['message'];
if (Platform::isWindows()) {
$message .= "\nThis can be due to an antivirus or the Windows Search Indexer locking the file while they are analyzed";
}

throw new \RuntimeException($message);
}
}

return true;
}








public function rmdir($path)
{
$deleted = @rmdir($path);
if (!$deleted) {

 if (Platform::isWindows()) {
usleep(350000);
$deleted = @rmdir($path);
}

if (!$deleted) {
$error = error_get_last();
$message = 'Could not delete '.$path.': ' . @$error['message'];
if (Platform::isWindows()) {
$message .= "\nThis can be due to an antivirus or the Windows Search Indexer locking the file while they are analyzed";
}

throw new \RuntimeException($message);
}
}

return true;
}










public function copyThenRemove($source, $target)
{
$this->copy($source, $target);
if (!is_dir($source)) {
$this->unlink($source);

return;
}

$this->removeDirectoryPhp($source);
}








public function copy($source, $target)
{
if (!is_dir($source)) {
return copy($source, $target);
}

$it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
$ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
$this->ensureDirectoryExists($target);

$result = true;

foreach ($ri as $file) {
$targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathname();
if ($file->isDir()) {
$this->ensureDirectoryExists($targetPath);
} else {
$result = $result && copy($file->getPathname(), $targetPath);
}
}

return $result;
}

public function rename($source, $target)
{
if (true === @rename($source, $target)) {
return;
}

if (!\function_exists('proc_open')) {
$this->copyThenRemove($source, $target);

return;
}

if (Platform::isWindows()) {

 $command = sprintf('xcopy %s %s /E /I /Q /Y', ProcessExecutor::escape($source), ProcessExecutor::escape($target));
$result = $this->getProcess()->execute($command, $output);


 clearstatcache();

if (0 === $result) {
$this->remove($source);

return;
}
} else {

 
 $command = sprintf('mv %s %s', ProcessExecutor::escape($source), ProcessExecutor::escape($target));
$result = $this->getProcess()->execute($command, $output);


 clearstatcache();

if (0 === $result) {
return;
}
}

$this->copyThenRemove($source, $target);
}










public function findShortestPath($from, $to, $directories = false)
{
if (!$this->isAbsolutePath($from) || !$this->isAbsolutePath($to)) {
throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to));
}

$from = lcfirst($this->normalizePath($from));
$to = lcfirst($this->normalizePath($to));

if ($directories) {
$from = rtrim($from, '/') . '/dummy_file';
}

if (\dirname($from) === \dirname($to)) {
return './'.basename($to);
}

$commonPath = $to;
while (strpos($from.'/', $commonPath.'/') !== 0 && '/' !== $commonPath && !preg_match('{^[a-z]:/?$}i', $commonPath)) {
$commonPath = strtr(\dirname($commonPath), '\\', '/');
}

if (0 !== strpos($from, $commonPath) || '/' === $commonPath) {
return $to;
}

$commonPath = rtrim($commonPath, '/') . '/';
$sourcePathDepth = substr_count(substr($from, \strlen($commonPath)), '/');
$commonPathCode = str_repeat('../', $sourcePathDepth);

return ($commonPathCode . substr($to, \strlen($commonPath))) ?: './';
}











public function findShortestPathCode($from, $to, $directories = false, $staticCode = false)
{
if (!$this->isAbsolutePath($from) || !$this->isAbsolutePath($to)) {
throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to));
}

$from = lcfirst($this->normalizePath($from));
$to = lcfirst($this->normalizePath($to));

if ($from === $to) {
return $directories ? '__DIR__' : '__FILE__';
}

$commonPath = $to;
while (strpos($from.'/', $commonPath.'/') !== 0 && '/' !== $commonPath && !preg_match('{^[a-z]:/?$}i', $commonPath) && '.' !== $commonPath) {
$commonPath = strtr(\dirname($commonPath), '\\', '/');
}

if (0 !== strpos($from, $commonPath) || '/' === $commonPath || '.' === $commonPath) {
return var_export($to, true);
}

$commonPath = rtrim($commonPath, '/') . '/';
if (strpos($to, $from.'/') === 0) {
return '__DIR__ . '.var_export(substr($to, \strlen($from)), true);
}
$sourcePathDepth = substr_count(substr($from, \strlen($commonPath)), '/') + $directories;
if ($staticCode) {
$commonPathCode = "__DIR__ . '".str_repeat('/..', $sourcePathDepth)."'";
} else {
$commonPathCode = str_repeat('dirname(', $sourcePathDepth).'__DIR__'.str_repeat(')', $sourcePathDepth);
}
$relTarget = substr($to, \strlen($commonPath));

return $commonPathCode . (\strlen($relTarget) ? '.' . var_export('/' . $relTarget, true) : '');
}







public function isAbsolutePath($path)
{
return strpos($path, '/') === 0 || substr($path, 1, 1) === ':' || strpos($path, '\\\\') === 0;
}









public function size($path)
{
if (!file_exists($path)) {
throw new \RuntimeException("$path does not exist.");
}
if (is_dir($path)) {
return $this->directorySize($path);
}

return filesize($path);
}








public function normalizePath($path)
{
$parts = array();
$path = strtr($path, '\\', '/');
$prefix = '';
$absolute = false;


 if (preg_match('{^( [0-9a-z]{2,}+: (?: // (?: [a-z]: )? )? | [a-z]: )}ix', $path, $match)) {
$prefix = $match[1];
$path = substr($path, \strlen($prefix));
}

if (strpos($path, '/') === 0) {
$absolute = true;
$path = substr($path, 1);
}

$up = false;
foreach (explode('/', $path) as $chunk) {
if ('..' === $chunk && ($absolute || $up)) {
array_pop($parts);
$up = !(empty($parts) || '..' === end($parts));
} elseif ('.' !== $chunk && '' !== $chunk) {
$parts[] = $chunk;
$up = '..' !== $chunk;
}
}

return $prefix.($absolute ? '/' : '').implode('/', $parts);
}









public static function trimTrailingSlash($path)
{
if (!preg_match('{^[/\\\\]+$}', $path)) {
$path = rtrim($path, '/\\');
}

return $path;
}







public static function isLocalPath($path)
{
return (bool) preg_match('{^(file://(?!//)|/(?!/)|/?[a-z]:[\\\\/]|\.\.[\\\\/]|[a-z0-9_.-]+[\\\\/])}i', $path);
}

public static function getPlatformPath($path)
{
if (Platform::isWindows()) {
$path = preg_replace('{^(?:file:///([a-z]):?/)}i', 'file://$1:/', $path);
}

return preg_replace('{^file://}i', '', $path);
}

protected function directorySize($directory)
{
$it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

$size = 0;
foreach ($ri as $file) {
if ($file->isFile()) {
$size += $file->getSize();
}
}

return $size;
}




protected function getProcess()
{
if (!$this->processExecutor) {
$this->processExecutor = new ProcessExecutor();
}

return $this->processExecutor;
}










private function unlinkImplementation($path)
{
if (Platform::isWindows() && is_dir($path) && is_link($path)) {
return rmdir($path);
}

return unlink($path);
}








public function relativeSymlink($target, $link)
{
if (!function_exists('symlink')) {
return false;
}

$cwd = getcwd();

$relativePath = $this->findShortestPath($link, $target);
chdir(\dirname($link));
$result = @symlink($relativePath, $link);

chdir($cwd);

return $result;
}








public function isSymlinkedDirectory($directory)
{
if (!is_dir($directory)) {
return false;
}

$resolved = $this->resolveSymlinkedDirectorySymlink($directory);

return is_link($resolved);
}






private function unlinkSymlinkedDirectory($directory)
{
$resolved = $this->resolveSymlinkedDirectorySymlink($directory);

return $this->unlink($resolved);
}








private function resolveSymlinkedDirectorySymlink($pathname)
{
if (!is_dir($pathname)) {
return $pathname;
}

$resolved = rtrim($pathname, '/');

if (!\strlen($resolved)) {
return $pathname;
}

return $resolved;
}







public function junction($target, $junction)
{
if (!Platform::isWindows()) {
throw new \LogicException(sprintf('Function %s is not available on non-Windows platform', __CLASS__));
}
if (!is_dir($target)) {
throw new IOException(sprintf('Cannot junction to "%s" as it is not a directory.', $target), 0, null, $target);
}
$cmd = sprintf(
'mklink /J %s %s',
ProcessExecutor::escape(str_replace('/', DIRECTORY_SEPARATOR, $junction)),
ProcessExecutor::escape(realpath($target))
);
if ($this->getProcess()->execute($cmd, $output) !== 0) {
throw new IOException(sprintf('Failed to create junction to "%s" at "%s".', $target, $junction), 0, null, $target);
}
clearstatcache(true, $junction);
}





















public function isJunction($junction)
{
if (!Platform::isWindows()) {
return false;
}


 clearstatcache(true, $junction);

if (!is_dir($junction) || is_link($junction)) {
return false;
}

$stat = lstat($junction);


 return $stat ? 0x4000 !== ($stat['mode'] & 0xF000) : false;
}







public function removeJunction($junction)
{
if (!Platform::isWindows()) {
return false;
}
$junction = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $junction), DIRECTORY_SEPARATOR);
if (!$this->isJunction($junction)) {
throw new IOException(sprintf('%s is not a junction and thus cannot be removed as one', $junction));
}

return $this->rmdir($junction);
}

public function filePutContentsIfModified($path, $content)
{
$currentContent = @file_get_contents($path);
if (!$currentContent || ($currentContent != $content)) {
return file_put_contents($path, $content);
}

return 0;
}







public function safeCopy($source, $target)
{
if (!file_exists($target) || !file_exists($source) || !$this->filesAreEqual($source, $target)) {
$source = fopen($source, 'r');
$target = fopen($target, 'w+');

stream_copy_to_stream($source, $target);
fclose($source);
fclose($target);
}
}





private function filesAreEqual($a, $b)
{

 if (filesize($a) !== filesize($b)) {
return false;
}


 $ah = fopen($a, 'rb');
$bh = fopen($b, 'rb');

$result = true;
while (!feof($ah)) {
if (fread($ah, 8192) != fread($bh, 8192)) {
$result = false;
break;
}
}

fclose($ah);
fclose($bh);

return $result;
}
}
