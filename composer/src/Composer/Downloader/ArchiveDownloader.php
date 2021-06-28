<?php











namespace Composer\Downloader;

use Composer\Package\PackageInterface;
use Symfony\Component\Finder\Finder;
use React\Promise\PromiseInterface;
use Composer\DependencyResolver\Operation\InstallOperation;








abstract class ArchiveDownloader extends FileDownloader
{





public function install(PackageInterface $package, $path, $output = true)
{
if ($output) {
$this->io->writeError("  - " . InstallOperation::format($package).": Extracting archive");
} else {
$this->io->writeError('Extracting archive', false);
}

$vendorDir = $this->config->get('vendor-dir');


 
 
 if (false === strpos($this->filesystem->normalizePath($vendorDir), $this->filesystem->normalizePath($path.DIRECTORY_SEPARATOR))) {
$this->filesystem->emptyDirectory($path);
}

do {
$temporaryDir = $vendorDir.'/composer/'.substr(md5(uniqid('', true)), 0, 8);
} while (is_dir($temporaryDir));

$this->addCleanupPath($package, $temporaryDir);

 
 if (!is_dir($path) || realpath($path) !== getcwd()) {
$this->addCleanupPath($package, $path);
}

$this->filesystem->ensureDirectoryExists($temporaryDir);
$fileName = $this->getFileName($package, $path);

$filesystem = $this->filesystem;
$self = $this;

$cleanup = function () use ($path, $filesystem, $temporaryDir, $package, $self) {

 $self->clearLastCacheWrite($package);


 $filesystem->removeDirectory($temporaryDir);
if (is_dir($path) && realpath($path) !== getcwd()) {
$filesystem->removeDirectory($path);
}
$self->removeCleanupPath($package, $temporaryDir);
$self->removeCleanupPath($package, realpath($path));
};

$promise = null;
try {
$promise = $this->extract($package, $fileName, $temporaryDir);
} catch (\Exception $e) {
$cleanup();
throw $e;
}

if (!$promise instanceof PromiseInterface) {
$promise = \React\Promise\resolve();
}

return $promise->then(function () use ($self, $package, $filesystem, $fileName, $temporaryDir, $path) {
$filesystem->unlink($fileName);







$getFolderContent = function ($dir) {
$finder = Finder::create()
->ignoreVCS(false)
->ignoreDotFiles(false)
->notName('.DS_Store')
->depth(0)
->in($dir);

return iterator_to_array($finder);
};

$renameAsOne = false;
if (!file_exists($path) || ($filesystem->isDirEmpty($path) && $filesystem->removeDirectory($path))) {
$renameAsOne = true;
}

$contentDir = $getFolderContent($temporaryDir);
$singleDirAtTopLevel = 1 === count($contentDir) && is_dir(reset($contentDir));

if ($renameAsOne) {

 if ($singleDirAtTopLevel) {
$extractedDir = (string) reset($contentDir);
} else {
$extractedDir = $temporaryDir;
}
$filesystem->rename($extractedDir, $path);
} else {

 if ($singleDirAtTopLevel) {
$contentDir = $getFolderContent((string) reset($contentDir));
}


 foreach ($contentDir as $file) {
$file = (string) $file;
$filesystem->rename($file, $path . '/' . basename($file));
}
}

$filesystem->removeDirectory($temporaryDir);
$self->removeCleanupPath($package, $temporaryDir);
$self->removeCleanupPath($package, $path);
}, function ($e) use ($cleanup) {
$cleanup();

throw $e;
});
}










abstract protected function extract(PackageInterface $package, $file, $path);
}
