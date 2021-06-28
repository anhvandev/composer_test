<?php











namespace Composer\Json;

use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Composer\Util\HttpDownloader;
use Composer\IO\IOInterface;
use Composer\Downloader\TransportException;







class JsonFile
{
const LAX_SCHEMA = 1;
const STRICT_SCHEMA = 2;

const JSON_UNESCAPED_SLASHES = 64;
const JSON_PRETTY_PRINT = 128;
const JSON_UNESCAPED_UNICODE = 256;

const COMPOSER_SCHEMA_PATH = '/../../../res/composer-schema.json';

private $path;
private $httpDownloader;
private $io;









public function __construct($path, HttpDownloader $httpDownloader = null, IOInterface $io = null)
{
$this->path = $path;

if (null === $httpDownloader && preg_match('{^https?://}i', $path)) {
throw new \InvalidArgumentException('http urls require a HttpDownloader instance to be passed');
}
$this->httpDownloader = $httpDownloader;
$this->io = $io;
}




public function getPath()
{
return $this->path;
}






public function exists()
{
return is_file($this->path);
}








public function read()
{
try {
if ($this->httpDownloader) {
$json = $this->httpDownloader->get($this->path)->getBody();
} else {
if ($this->io && $this->io->isDebug()) {
$realpathInfo = '';
$realpath = realpath($this->path);
if (false !== $realpath && $realpath !== $this->path) {
$realpathInfo = ' (' . $realpath . ')';
}
$this->io->writeError('Reading ' . $this->path . $realpathInfo);
}
$json = file_get_contents($this->path);
}
} catch (TransportException $e) {
throw new \RuntimeException($e->getMessage(), 0, $e);
} catch (\Exception $e) {
throw new \RuntimeException('Could not read '.$this->path."\n\n".$e->getMessage());
}

return static::parseJson($json, $this->path);
}








public function write(array $hash, $options = 448)
{
if ($this->path === 'php://memory') {
file_put_contents($this->path, static::encode($hash, $options));

return;
}

$dir = dirname($this->path);
if (!is_dir($dir)) {
if (file_exists($dir)) {
throw new \UnexpectedValueException(
realpath($dir).' exists and is not a directory.'
);
}
if (!@mkdir($dir, 0777, true)) {
throw new \UnexpectedValueException(
$dir.' does not exist and could not be created.'
);
}
}

$retries = 3;
while ($retries--) {
try {
$this->filePutContentsIfModified($this->path, static::encode($hash, $options). ($options & self::JSON_PRETTY_PRINT ? "\n" : ''));
break;
} catch (\Exception $e) {
if ($retries) {
usleep(500000);
continue;
}

throw $e;
}
}
}




private function filePutContentsIfModified($path, $content)
{
$currentContent = @file_get_contents($path);
if (!$currentContent || ($currentContent != $content)) {
return file_put_contents($path, $content);
}

return 0;
}










public function validateSchema($schema = self::STRICT_SCHEMA, $schemaFile = null)
{
$content = file_get_contents($this->path);
$data = json_decode($content);

if (null === $data && 'null' !== $content) {
self::validateSyntax($content, $this->path);
}

if (null === $schemaFile) {
$schemaFile = __DIR__ . self::COMPOSER_SCHEMA_PATH;
}


 if (false === strpos($schemaFile, '://')) {
$schemaFile = 'file://' . $schemaFile;
}

$schemaData = (object) array('$ref' => $schemaFile);

if ($schema === self::LAX_SCHEMA) {
$schemaData->additionalProperties = true;
$schemaData->required = array();
}

$validator = new Validator();
$validator->check($data, $schemaData);



if (!$validator->isValid()) {
$errors = array();
foreach ((array) $validator->getErrors() as $error) {
$errors[] = ($error['property'] ? $error['property'].' : ' : '').$error['message'];
}
throw new JsonValidationException('"'.$this->path.'" does not match the expected JSON schema', $errors);
}

return true;
}








public static function encode($data, $options = 448)
{
if (PHP_VERSION_ID >= 50400) {
$json = json_encode($data, $options);
if (false === $json) {
self::throwEncodeError(json_last_error());
}


 if (PHP_VERSION_ID < 50428 || (PHP_VERSION_ID >= 50500 && PHP_VERSION_ID < 50512) || (defined('JSON_C_VERSION') && version_compare(phpversion('json'), '1.3.6', '<'))) {
$json = preg_replace('/\[\s+\]/', '[]', $json);
$json = preg_replace('/\{\s+\}/', '{}', $json);
}

return $json;
}

$json = json_encode($data);
if (false === $json) {
self::throwEncodeError(json_last_error());
}

$prettyPrint = (bool) ($options & self::JSON_PRETTY_PRINT);
$unescapeUnicode = (bool) ($options & self::JSON_UNESCAPED_UNICODE);
$unescapeSlashes = (bool) ($options & self::JSON_UNESCAPED_SLASHES);

if (!$prettyPrint && !$unescapeUnicode && !$unescapeSlashes) {
return $json;
}

return JsonFormatter::format($json, $unescapeUnicode, $unescapeSlashes);
}







private static function throwEncodeError($code)
{
switch ($code) {
case JSON_ERROR_DEPTH:
$msg = 'Maximum stack depth exceeded';
break;
case JSON_ERROR_STATE_MISMATCH:
$msg = 'Underflow or the modes mismatch';
break;
case JSON_ERROR_CTRL_CHAR:
$msg = 'Unexpected control character found';
break;
case JSON_ERROR_UTF8:
$msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
break;
default:
$msg = 'Unknown error';
}

throw new \RuntimeException('JSON encoding failed: '.$msg);
}










public static function parseJson($json, $file = null)
{
if (null === $json) {
return;
}
$data = json_decode($json, true);
if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
self::validateSyntax($json, $file);
}

return $data;
}










protected static function validateSyntax($json, $file = null)
{
$parser = new JsonParser();
$result = $parser->lint($json);
if (null === $result) {
if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
throw new \UnexpectedValueException('"'.$file.'" is not UTF-8, could not parse as JSON');
}

return true;
}

throw new ParsingException('"'.$file.'" does not contain valid JSON'."\n".$result->getMessage(), $result->getDetails());
}
}
