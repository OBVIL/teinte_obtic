<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Oeuvres\Kit\{Log, LoggerCli, Xsl};
use Psr\Log\LogLevel;

Log::setLogger(new LoggerCli(LogLevel::DEBUG));

$xml = file_get_contents(__DIR__.'/res/blanqui1866_prise-armes.xml');

$dom = Xt::load(__DIR__.'/res/blanqui1866_prise-armes.xml');
echo Xt::transformToXML(
    __DIR__ . "/res/tei_dc.xsl",
    $dom
);
