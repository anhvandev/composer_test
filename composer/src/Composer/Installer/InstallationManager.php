<?php











namespace Composer\Installer;

use Composer\IO\IOInterface;
use Composer\IO\ConsoleIO;
use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\MarkAliasInstalledOperation;
use Composer\DependencyResolver\Operation\MarkAliasUninstalledOperation;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Util\Loop;
use React\Promise\PromiseInterface;








class InstallationManager
{

private $installers = array();

private $cache = array();

private $notifiablePackages = array();

private $loop;

private $io;

private $eventDispatcher;

private $outputProgress;

public function __construct(Loop $loop, IOInterface $io, EventDispatcher $eventDispatcher = null)
{
$this->loop = $loop;
$this->io = $io;
$this->eventDispatcher = $eventDispatcher;
}

public function reset()
{
$this->notifiablePackages = array();
}






public function addInstaller(InstallerInterface $installer)
{
array_unshift($this->installers, $installer);
$this->cache = array();
}






public function removeInstaller(InstallerInterface $installer)
{
if (false !== ($key = array_search($installer, $this->installers, true))) {
array_splice($this->installers, $key, 1);
$this->cache = array();
}
}








public function disablePlugins()
{
foreach ($this->installers as $i => $installer) {
if (!$installer instanceof PluginInstaller) {
continue;
}

unset($this->installers[$i]);
}
}









public function getInstaller($type)
{
$type = strtolower($type);

if (isset($this->cache[$type])) {
return $this->cache[$type];
}

foreach ($this->installers as $installer) {
if ($installer->supports($type)) {
return $this->cache[$type] = $installer;
}
}

throw new \InvalidArgumentException('Unknown installer type: '.$type);
}









public function isPackageInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
{
if ($package instanceof AliasPackage) {
return $repo->hasPackage($package) && $this->isPackageInstalled($repo, $package->getAliasOf());
}

return $this->getInstaller($package->getType())->isInstalled($repo, $package);
}







public function ensureBinariesPresence(PackageInterface $package)
{
try {
$installer = $this->getInstaller($package->getType());
} catch (\InvalidArgumentException $e) {

 return;
}


 if ($installer instanceof BinaryPresenceInterface) {
$installer->ensureBinariesPresence($package);
}
}









public function execute(RepositoryInterface $repo, array $operations, $devMode = true, $runScripts = true)
{
$cleanupPromises = array();

$loop = $this->loop;
$io = $this->io;
$runCleanup = function () use (&$cleanupPromises, $loop) {
$promises = array();

$loop->abortJobs();

foreach ($cleanupPromises as $cleanup) {
$promises[] = new \React\Promise\Promise(function ($resolve, $reject) use ($cleanup) {
$promise = $cleanup();
if (!$promise instanceof PromiseInterface) {
$resolve();
} else {
$promise->then(function () use ($resolve) {
$resolve();
});
}
});
}

if (!empty($promises)) {
$loop->wait($promises);
}
};

$handleInterruptsUnix = function_exists('pcntl_async_signals') && function_exists('pcntl_signal');
$handleInterruptsWindows = function_exists('sapi_windows_set_ctrl_handler');
$prevHandler = null;
$windowsHandler = null;
if ($handleInterruptsUnix) {
pcntl_async_signals(true);
$prevHandler = pcntl_signal_get_handler(SIGINT);
pcntl_signal(SIGINT, function ($sig) use ($runCleanup, $prevHandler, $io) {
$io->writeError('Received SIGINT, aborting', true, IOInterface::DEBUG);
$runCleanup();

if (!in_array($prevHandler, array(SIG_DFL, SIG_IGN), true)) {
call_user_func($prevHandler, $sig);
}

exit(130);
});
}
if ($handleInterruptsWindows) {
$windowsHandler = function ($event) use ($runCleanup, $io) {
if ($event !== PHP_WINDOWS_EVENT_CTRL_C) {
return;
}
$io->writeError('Received CTRL+C, aborting', true, IOInterface::DEBUG);
$runCleanup();

exit(130);
};
sapi_windows_set_ctrl_handler($windowsHandler);
}

try {

 
 $batches = array();
$batch = array();
foreach ($operations as $index => $operation) {
if (in_array($operation->getOperationType(), array('update', 'install'), true)) {
$package = $operation->getOperationType() === 'update' ? $operation->getTargetPackage() : $operation->getPackage();
if ($package->getType() === 'composer-plugin' && ($extra = $package->getExtra()) && isset($extra['plugin-modifies-downloads']) && $extra['plugin-modifies-downloads'] === true) {
if ($batch) {
$batches[] = $batch;
}
$batches[] = array($index => $operation);
$batch = array();

continue;
}
}
$batch[$index] = $operation;
}

if ($batch) {
$batches[] = $batch;
}

foreach ($batches as $batch) {
$this->downloadAndExecuteBatch($repo, $batch, $cleanupPromises, $devMode, $runScripts, $operations);
}
} catch (\Exception $e) {
$runCleanup();

if ($handleInterruptsUnix) {
pcntl_signal(SIGINT, $prevHandler);
}
if ($handleInterruptsWindows) {
sapi_windows_set_ctrl_handler($prevHandler, false);
}

throw $e;
}

if ($handleInterruptsUnix) {
pcntl_signal(SIGINT, $prevHandler);
}
if ($handleInterruptsWindows) {
sapi_windows_set_ctrl_handler($prevHandler, false);
}


 
 
 $repo->write($devMode, $this);
}





private function downloadAndExecuteBatch(RepositoryInterface $repo, array $operations, array &$cleanupPromises, $devMode, $runScripts, array $allOperations)
{
$promises = array();

foreach ($operations as $index => $operation) {
$opType = $operation->getOperationType();


 if (!in_array($opType, array('update', 'install', 'uninstall'))) {
continue;
}

if ($opType === 'update') {
$package = $operation->getTargetPackage();
$initialPackage = $operation->getInitialPackage();
} else {
$package = $operation->getPackage();
$initialPackage = null;
}
$installer = $this->getInstaller($package->getType());

$cleanupPromises[$index] = function () use ($opType, $installer, $package, $initialPackage) {

 
 if (!$package->getInstallationSource()) {
return;
}

return $installer->cleanup($opType, $package, $initialPackage);
};

if ($opType !== 'uninstall') {
$promise = $installer->download($package, $initialPackage);
if ($promise) {
$promises[] = $promise;
}
}
}


 if (count($promises)) {
$this->waitOnPromises($promises);
}


 
 $batches = array();
$batch = array();
foreach ($operations as $index => $operation) {
if (in_array($operation->getOperationType(), array('update', 'install'), true)) {
$package = $operation->getOperationType() === 'update' ? $operation->getTargetPackage() : $operation->getPackage();
if ($package->getType() === 'composer-plugin' || $package->getType() === 'composer-installer') {
if ($batch) {
$batches[] = $batch;
}
$batches[] = array($index => $operation);
$batch = array();

continue;
}
}
$batch[$index] = $operation;
}

if ($batch) {
$batches[] = $batch;
}

foreach ($batches as $batch) {
$this->executeBatch($repo, $batch, $cleanupPromises, $devMode, $runScripts, $allOperations);
}
}





private function executeBatch(RepositoryInterface $repo, array $operations, array $cleanupPromises, $devMode, $runScripts, array $allOperations)
{
$promises = array();
$postExecCallbacks = array();

foreach ($operations as $index => $operation) {
$opType = $operation->getOperationType();


 if (!in_array($opType, array('update', 'install', 'uninstall'))) {

 if ($this->io->isDebug()) {
$this->io->writeError('  - ' . $operation->show(false));
}
$this->$opType($repo, $operation);

continue;
}

if ($opType === 'update') {
$package = $operation->getTargetPackage();
$initialPackage = $operation->getInitialPackage();
} else {
$package = $operation->getPackage();
$initialPackage = null;
}
$installer = $this->getInstaller($package->getType());

$event = 'Composer\Installer\PackageEvents::PRE_PACKAGE_'.strtoupper($opType);
if (defined($event) && $runScripts && $this->eventDispatcher) {
$this->eventDispatcher->dispatchPackageEvent(constant($event), $devMode, $repo, $allOperations, $operation);
}

$dispatcher = $this->eventDispatcher;
$installManager = $this;
$io = $this->io;

$promise = $installer->prepare($opType, $package, $initialPackage);
if (!$promise instanceof PromiseInterface) {
$promise = \React\Promise\resolve();
}

$promise = $promise->then(function () use ($opType, $installManager, $repo, $operation) {
return $installManager->$opType($repo, $operation);
})->then($cleanupPromises[$index])
->then(function () use ($installManager, $devMode, $repo) {
$repo->write($devMode, $installManager);
}, function ($e) use ($opType, $package, $io) {
$io->writeError('    <error>' . ucfirst($opType) .' of '.$package->getPrettyName().' failed</error>');

throw $e;
});

$postExecCallbacks[] = function () use ($opType, $runScripts, $dispatcher, $devMode, $repo, $allOperations, $operation) {
$event = 'Composer\Installer\PackageEvents::POST_PACKAGE_'.strtoupper($opType);
if (defined($event) && $runScripts && $dispatcher) {
$dispatcher->dispatchPackageEvent(constant($event), $devMode, $repo, $allOperations, $operation);
}
};

$promises[] = $promise;
}


 if (count($promises)) {
$this->waitOnPromises($promises);
}

foreach ($postExecCallbacks as $cb) {
$cb();
}
}

private function waitOnPromises(array $promises)
{
$progress = null;
if ($this->outputProgress && $this->io instanceof ConsoleIO && !$this->io->isDebug() && count($promises) > 1) {
$progress = $this->io->getProgressBar();
}
$this->loop->wait($promises, $progress);
if ($progress) {
$progress->clear();
}
}







public function install(RepositoryInterface $repo, InstallOperation $operation)
{
$package = $operation->getPackage();
$installer = $this->getInstaller($package->getType());
$promise = $installer->install($repo, $package);
$this->markForNotification($package);

return $promise;
}







public function update(RepositoryInterface $repo, UpdateOperation $operation)
{
$initial = $operation->getInitialPackage();
$target = $operation->getTargetPackage();

$initialType = $initial->getType();
$targetType = $target->getType();

if ($initialType === $targetType) {
$installer = $this->getInstaller($initialType);
$promise = $installer->update($repo, $initial, $target);
$this->markForNotification($target);
} else {
$promise = $this->getInstaller($initialType)->uninstall($repo, $initial);
if (!$promise instanceof PromiseInterface) {
$promise = \React\Promise\resolve();
}

$installer = $this->getInstaller($targetType);
$promise->then(function () use ($installer, $repo, $target) {
return $installer->install($repo, $target);
});
}

return $promise;
}







public function uninstall(RepositoryInterface $repo, UninstallOperation $operation)
{
$package = $operation->getPackage();
$installer = $this->getInstaller($package->getType());

return $installer->uninstall($repo, $package);
}







public function markAliasInstalled(RepositoryInterface $repo, MarkAliasInstalledOperation $operation)
{
$package = $operation->getPackage();

if (!$repo->hasPackage($package)) {
$repo->addPackage(clone $package);
}
}







public function markAliasUninstalled(RepositoryInterface $repo, MarkAliasUninstalledOperation $operation)
{
$package = $operation->getPackage();

$repo->removePackage($package);
}







public function getInstallPath(PackageInterface $package)
{
$installer = $this->getInstaller($package->getType());

return $installer->getInstallPath($package);
}

public function setOutputProgress($outputProgress)
{
$this->outputProgress = $outputProgress;
}

public function notifyInstalls(IOInterface $io)
{
$promises = array();

try {
foreach ($this->notifiablePackages as $repoUrl => $packages) {

 if (strpos($repoUrl, '%package%')) {
foreach ($packages as $package) {
$url = str_replace('%package%', $package->getPrettyName(), $repoUrl);

$params = array(
'version' => $package->getPrettyVersion(),
'version_normalized' => $package->getVersion(),
);
$opts = array(
'retry-auth-failure' => false,
'http' => array(
'method' => 'POST',
'header' => array('Content-type: application/x-www-form-urlencoded'),
'content' => http_build_query($params, '', '&'),
'timeout' => 3,
),
);

$promises[] = $this->loop->getHttpDownloader()->add($url, $opts);
}

continue;
}

$postData = array('downloads' => array());
foreach ($packages as $package) {
$postData['downloads'][] = array(
'name' => $package->getPrettyName(),
'version' => $package->getVersion(),
);
}

$opts = array(
'retry-auth-failure' => false,
'http' => array(
'method' => 'POST',
'header' => array('Content-Type: application/json'),
'content' => json_encode($postData),
'timeout' => 6,
),
);

$promises[] = $this->loop->getHttpDownloader()->add($repoUrl, $opts);
}

$this->loop->wait($promises);
} catch (\Exception $e) {
}

$this->reset();
}

private function markForNotification(PackageInterface $package)
{
if ($package->getNotificationUrl()) {
$this->notifiablePackages[$package->getNotificationUrl()][$package->getName()] = $package;
}
}
}
