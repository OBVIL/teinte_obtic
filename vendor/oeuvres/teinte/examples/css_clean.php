<?php declare(strict_types=1);

/**
 * Example usage of the CSS parser to get a light model
 */

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log, LoggerCli};
use Oeuvres\Teinte\Format\{CssModel};

Log::setLogger(new LoggerCli(LogLevel::DEBUG));
if (count($argv) < 2) {
    return Log::warning("A filename is waited as an argument");
}
$src_file = $argv[1];

$css = new CssModel();
$css->load($src_file);
print_r($css->asArray());
echo $css->asXml();
