<?php











namespace Composer\Util;

use Composer\Config;




class Url
{






public static function updateDistReference(Config $config, $url, $ref)
{
$host = parse_url($url, PHP_URL_HOST);

if ($host === 'api.github.com' || $host === 'github.com' || $host === 'www.github.com') {
if (preg_match('{^https?://(?:www\.)?github\.com/([^/]+)/([^/]+)/(zip|tar)ball/(.+)$}i', $url, $match)) {

 $url = 'https://api.github.com/repos/' . $match[1] . '/'. $match[2] . '/' . $match[3] . 'ball/' . $ref;
} elseif (preg_match('{^https?://(?:www\.)?github\.com/([^/]+)/([^/]+)/archive/.+\.(zip|tar)(?:\.gz)?$}i', $url, $match)) {

 $url = 'https://api.github.com/repos/' . $match[1] . '/'. $match[2] . '/' . $match[3] . 'ball/' . $ref;
} elseif (preg_match('{^https?://api\.github\.com/repos/([^/]+)/([^/]+)/(zip|tar)ball(?:/.+)?$}i', $url, $match)) {

 $url = 'https://api.github.com/repos/' . $match[1] . '/'. $match[2] . '/' . $match[3] . 'ball/' . $ref;
}
} elseif ($host === 'bitbucket.org' || $host === 'www.bitbucket.org') {
if (preg_match('{^https?://(?:www\.)?bitbucket\.org/([^/]+)/([^/]+)/get/(.+)\.(zip|tar\.gz|tar\.bz2)$}i', $url, $match)) {

 $url = 'https://bitbucket.org/' . $match[1] . '/'. $match[2] . '/get/' . $ref . '.' . $match[4];
}
} elseif ($host === 'gitlab.com' || $host === 'www.gitlab.com') {
if (preg_match('{^https?://(?:www\.)?gitlab\.com/api/v[34]/projects/([^/]+)/repository/archive\.(zip|tar\.gz|tar\.bz2|tar)\?sha=.+$}i', $url, $match)) {

 $url = 'https://gitlab.com/api/v4/projects/' . $match[1] . '/repository/archive.' . $match[2] . '?sha=' . $ref;
}
} elseif (in_array($host, $config->get('github-domains'), true)) {
$url = preg_replace('{(/repos/[^/]+/[^/]+/(zip|tar)ball)(?:/.+)?$}i', '$1/'.$ref, $url);
} elseif (in_array($host, $config->get('gitlab-domains'), true)) {
$url = preg_replace('{(/api/v[34]/projects/[^/]+/repository/archive\.(?:zip|tar\.gz|tar\.bz2|tar)\?sha=).+$}i', '${1}'.$ref, $url);
}

return $url;
}





public static function getOrigin(Config $config, $url)
{
if (0 === strpos($url, 'file://')) {
return $url;
}

$origin = (string) parse_url($url, PHP_URL_HOST);
if ($port = parse_url($url, PHP_URL_PORT)) {
$origin .= ':'.$port;
}

if (strpos($origin, '.github.com') === (strlen($origin) - 11)) {
return 'github.com';
}

if ($origin === 'repo.packagist.org') {
return 'packagist.org';
}

if ($origin === '') {
$origin = $url;
}


 
 if (
is_array($config->get('gitlab-domains'))
&& false === strpos($origin, '/')
&& !in_array($origin, $config->get('gitlab-domains'))
) {
foreach ($config->get('gitlab-domains') as $gitlabDomain) {
if (0 === strpos($gitlabDomain, $origin)) {
return $gitlabDomain;
}
}
}

return $origin;
}

public static function sanitize($url)
{

 
 $url = preg_replace('{([&?]access_token=)[^&]+}', '$1***', $url);

$url = preg_replace_callback('{(?P<prefix>://|^)(?P<user>[^:/\s@]+):(?P<password>[^@\s/]+)@}i', function ($m) {
if (preg_match('{^[a-f0-9]{12,}$}', $m['user'])) {
return $m['prefix'].'***:***@';
}

return $m['prefix'].$m['user'].':***@';
}, $url);

return $url;
}
}
