<?php











namespace Composer\Package\Version;

use Composer\Repository\PlatformRepository;
use Composer\Semver\VersionParser as SemverVersionParser;
use Composer\Semver\Semver;

class VersionParser extends SemverVersionParser
{
const DEFAULT_BRANCH_ALIAS = '9999999-dev';

private static $constraints = array();




public function parseConstraints($constraints)
{
if (!isset(self::$constraints[$constraints])) {
self::$constraints[$constraints] = parent::parseConstraints($constraints);
}

return self::$constraints[$constraints];
}











public function parseNameVersionPairs(array $pairs)
{
$pairs = array_values($pairs);
$result = array();

for ($i = 0, $count = count($pairs); $i < $count; $i++) {
$pair = preg_replace('{^([^=: ]+)[=: ](.*)$}', '$1 $2', trim($pairs[$i]));
if (false === strpos($pair, ' ') && isset($pairs[$i + 1]) && false === strpos($pairs[$i + 1], '/') && !PlatformRepository::isPlatformPackage($pairs[$i + 1])) {
$pair .= ' '.$pairs[$i + 1];
$i++;
}

if (strpos($pair, ' ')) {
list($name, $version) = explode(' ', $pair, 2);
$result[] = array('name' => $name, 'version' => $version);
} else {
$result[] = array('name' => $pair);
}
}

return $result;
}




public static function isUpgrade($normalizedFrom, $normalizedTo)
{
if ($normalizedFrom === $normalizedTo) {
return true;
}

$normalizedFrom = str_replace(array('dev-master', 'dev-trunk', 'dev-default'), VersionParser::DEFAULT_BRANCH_ALIAS, $normalizedFrom);
$normalizedTo = str_replace(array('dev-master', 'dev-trunk', 'dev-default'), VersionParser::DEFAULT_BRANCH_ALIAS, $normalizedTo);

if (strpos($normalizedFrom, 'dev-') === 0 || strpos($normalizedTo, 'dev-') === 0) {
return true;
}

$sorted = Semver::sort(array($normalizedTo, $normalizedFrom));

return $sorted[0] === $normalizedFrom;
}
}
