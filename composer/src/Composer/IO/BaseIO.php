<?php











namespace Composer\IO;

use Composer\Config;
use Composer\Util\ProcessExecutor;
use Psr\Log\LogLevel;

abstract class BaseIO implements IOInterface
{
protected $authentications = array();




public function getAuthentications()
{
return $this->authentications;
}




public function resetAuthentications()
{
$this->authentications = array();
}




public function hasAuthentication($repositoryName)
{
return isset($this->authentications[$repositoryName]);
}




public function getAuthentication($repositoryName)
{
if (isset($this->authentications[$repositoryName])) {
return $this->authentications[$repositoryName];
}

return array('username' => null, 'password' => null);
}




public function setAuthentication($repositoryName, $username, $password = null)
{
$this->authentications[$repositoryName] = array('username' => $username, 'password' => $password);
}




public function writeRaw($messages, $newline = true, $verbosity = self::NORMAL)
{
$this->write($messages, $newline, $verbosity);
}




public function writeErrorRaw($messages, $newline = true, $verbosity = self::NORMAL)
{
$this->writeError($messages, $newline, $verbosity);
}








protected function checkAndSetAuthentication($repositoryName, $username, $password = null)
{
if ($this->hasAuthentication($repositoryName)) {
$auth = $this->getAuthentication($repositoryName);
if ($auth['username'] === $username && $auth['password'] === $password) {
return;
}

$this->writeError(
sprintf(
"<warning>Warning: You should avoid overwriting already defined auth settings for %s.</warning>",
$repositoryName
)
);
}
$this->setAuthentication($repositoryName, $username, $password);
}




public function loadConfiguration(Config $config)
{
$bitbucketOauth = $config->get('bitbucket-oauth') ?: array();
$githubOauth = $config->get('github-oauth') ?: array();
$gitlabOauth = $config->get('gitlab-oauth') ?: array();
$gitlabToken = $config->get('gitlab-token') ?: array();
$httpBasic = $config->get('http-basic') ?: array();
$bearerToken = $config->get('bearer') ?: array();



foreach ($bitbucketOauth as $domain => $cred) {
$this->checkAndSetAuthentication($domain, $cred['consumer-key'], $cred['consumer-secret']);
}

foreach ($githubOauth as $domain => $token) {
if (!preg_match('{^[.a-z0-9]+$}', $token)) {
throw new \UnexpectedValueException('Your github oauth token for '.$domain.' contains invalid characters: "'.$token.'"');
}
$this->checkAndSetAuthentication($domain, $token, 'x-oauth-basic');
}

foreach ($gitlabOauth as $domain => $token) {
$this->checkAndSetAuthentication($domain, $token, 'oauth2');
}

foreach ($gitlabToken as $domain => $token) {
$username = is_array($token) && array_key_exists("username", $token) ? $token["username"] : $token;
$password = is_array($token) && array_key_exists("token", $token) ? $token["token"] : 'private-token';
$this->checkAndSetAuthentication($domain, $username, $password);
}


 foreach ($httpBasic as $domain => $cred) {
$this->checkAndSetAuthentication($domain, $cred['username'], $cred['password']);
}

foreach ($bearerToken as $domain => $token) {
$this->checkAndSetAuthentication($domain, $token, 'bearer');
}


 ProcessExecutor::setTimeout((int) $config->get('process-timeout'));
}








public function emergency($message, array $context = array())
{
return $this->log(LogLevel::EMERGENCY, $message, $context);
}











public function alert($message, array $context = array())
{
return $this->log(LogLevel::ALERT, $message, $context);
}










public function critical($message, array $context = array())
{
return $this->log(LogLevel::CRITICAL, $message, $context);
}









public function error($message, array $context = array())
{
return $this->log(LogLevel::ERROR, $message, $context);
}











public function warning($message, array $context = array())
{
return $this->log(LogLevel::WARNING, $message, $context);
}








public function notice($message, array $context = array())
{
return $this->log(LogLevel::NOTICE, $message, $context);
}










public function info($message, array $context = array())
{
return $this->log(LogLevel::INFO, $message, $context);
}








public function debug($message, array $context = array())
{
return $this->log(LogLevel::DEBUG, $message, $context);
}









public function log($level, $message, array $context = array())
{
if (in_array($level, array(LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR))) {
$this->writeError('<error>'.$message.'</error>');
} elseif ($level === LogLevel::WARNING) {
$this->writeError('<warning>'.$message.'</warning>');
} elseif ($level === LogLevel::NOTICE) {
$this->writeError('<info>'.$message.'</info>', true, self::VERBOSE);
} elseif ($level === LogLevel::INFO) {
$this->writeError('<info>'.$message.'</info>', true, self::VERY_VERBOSE);
} else {
$this->writeError($message, true, self::DEBUG);
}
}
}