<?php











namespace Composer\Util;

use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\ValidatingArrayLoader;
use Composer\Package\Loader\InvalidPackageException;
use Composer\Json\JsonValidationException;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Spdx\SpdxLicenses;







class ConfigValidator
{
const CHECK_VERSION = 1;

private $io;

public function __construct(IOInterface $io)
{
$this->io = $io;
}










public function validate($file, $arrayLoaderValidationFlags = ValidatingArrayLoader::CHECK_ALL, $flags = self::CHECK_VERSION)
{
$errors = array();
$publishErrors = array();
$warnings = array();


 $laxValid = false;
try {
$json = new JsonFile($file, null, $this->io);
$manifest = $json->read();

$json->validateSchema(JsonFile::LAX_SCHEMA);
$laxValid = true;
$json->validateSchema();
} catch (JsonValidationException $e) {
foreach ($e->getErrors() as $message) {
if ($laxValid) {
$publishErrors[] = $message;
} else {
$errors[] = $message;
}
}
} catch (\Exception $e) {
$errors[] = $e->getMessage();

return array($errors, $publishErrors, $warnings);
}


 if (empty($manifest['license'])) {
$warnings[] = 'No license specified, it is recommended to do so. For closed-source software you may use "proprietary" as license.';
} else {
$licenses = (array) $manifest['license'];


 foreach ($licenses as $key => $license) {
if ('proprietary' === $license) {
unset($licenses[$key]);
}
}

$licenseValidator = new SpdxLicenses();
foreach ($licenses as $license) {
$spdxLicense = $licenseValidator->getLicenseByIdentifier($license);
if ($spdxLicense && $spdxLicense[3]) {
if (preg_match('{^[AL]?GPL-[123](\.[01])?\+$}i', $license)) {
$warnings[] = sprintf(
'License "%s" is a deprecated SPDX license identifier, use "'.str_replace('+', '', $license).'-or-later" instead',
$license
);
} elseif (preg_match('{^[AL]?GPL-[123](\.[01])?$}i', $license)) {
$warnings[] = sprintf(
'License "%s" is a deprecated SPDX license identifier, use "'.$license.'-only" or "'.$license.'-or-later" instead',
$license
);
} else {
$warnings[] = sprintf(
'License "%s" is a deprecated SPDX license identifier, see https://spdx.org/licenses/',
$license
);
}
}
}
}

if (($flags & self::CHECK_VERSION) && isset($manifest['version'])) {
$warnings[] = 'The version field is present, it is recommended to leave it out if the package is published on Packagist.';
}

if (!empty($manifest['name']) && preg_match('{[A-Z]}', $manifest['name'])) {
$suggestName = preg_replace('{(?:([a-z])([A-Z])|([A-Z])([A-Z][a-z]))}', '\\1\\3-\\2\\4', $manifest['name']);
$suggestName = strtolower($suggestName);

$publishErrors[] = sprintf(
'Name "%s" does not match the best practice (e.g. lower-cased/with-dashes). We suggest using "%s" instead. As such you will not be able to submit it to Packagist.',
$manifest['name'],
$suggestName
);
}

if (!empty($manifest['type']) && $manifest['type'] == 'composer-installer') {
$warnings[] = "The package type 'composer-installer' is deprecated. Please distribute your custom installers as plugins from now on. See https://getcomposer.org/doc/articles/plugins.md for plugin documentation.";
}


 if (isset($manifest['require'], $manifest['require-dev'])) {
$requireOverrides = array_intersect_key($manifest['require'], $manifest['require-dev']);

if (!empty($requireOverrides)) {
$plural = (count($requireOverrides) > 1) ? 'are' : 'is';
$warnings[] = implode(', ', array_keys($requireOverrides)). " {$plural} required both in require and require-dev, this can lead to unexpected behavior";
}
}


 foreach (array('provide', 'replace') as $linkType) {
if (isset($manifest[$linkType])) {
foreach (array('require', 'require-dev') as $requireType) {
if (isset($manifest[$requireType])) {
foreach ($manifest[$linkType] as $provide => $constraint) {
if (isset($manifest[$requireType][$provide])) {
$warnings[] = 'The package ' . $provide . ' in '.$requireType.' is also listed in '.$linkType.' which satisfies the requirement. Remove it from '.$linkType.' if you wish to install it.';
}
}
}
}
}
}


 $require = isset($manifest['require']) ? $manifest['require'] : array();
$requireDev = isset($manifest['require-dev']) ? $manifest['require-dev'] : array();
$packages = array_merge($require, $requireDev);
foreach ($packages as $package => $version) {
if (preg_match('/#/', $version) === 1) {
$warnings[] = sprintf(
'The package "%s" is pointing to a commit-ref, this is bad practice and can cause unforeseen issues.',
$package
);
}
}


 $scriptsDescriptions = isset($manifest['scripts-descriptions']) ? $manifest['scripts-descriptions'] : array();
$scripts = isset($manifest['scripts']) ? $manifest['scripts'] : array();
foreach ($scriptsDescriptions as $scriptName => $scriptDescription) {
if (!array_key_exists($scriptName, $scripts)) {
$warnings[] = sprintf(
'Description for non-existent script "%s" found in "scripts-descriptions"',
$scriptName
);
}
}


 if (isset($manifest['autoload']['psr-0'][''])) {
$warnings[] = "Defining autoload.psr-0 with an empty namespace prefix is a bad idea for performance";
}
if (isset($manifest['autoload']['psr-4'][''])) {
$warnings[] = "Defining autoload.psr-4 with an empty namespace prefix is a bad idea for performance";
}

$loader = new ValidatingArrayLoader(new ArrayLoader(), true, null, $arrayLoaderValidationFlags);
try {
if (!isset($manifest['version'])) {
$manifest['version'] = '1.0.0';
}
if (!isset($manifest['name'])) {
$manifest['name'] = 'dummy/dummy';
}
$loader->load($manifest);
} catch (InvalidPackageException $e) {
$errors = array_merge($errors, $e->getErrors());
}

$warnings = array_merge($warnings, $loader->getWarnings());

return array($errors, $publishErrors, $warnings);
}
}
