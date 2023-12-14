<?php declare(strict_types=1);
/**
 *  Performance test, do not use PhpUnit to avoid mem overhead
 */

include_once(dirname(__DIR__) . '/vendor/autoload.php');


use Oeuvres\Kit\Bitset\{BitBool, BitInt, BitIntSparse, BitNoop, BitString};


final class BitSetTest
{
    static $memStart;
    static public function bench(): void
    {
        self::$memStart = memory_get_usage();
        $loops = 1;
        $width = 100000;

        foreach (["BitNoop", "BitBool", "BitString", "BitInt", "BitIntFull", "BitString", "BitIntFull", "BitInt"] as $className) {
            echo "\n$className [0, " . ($width - 1) ."]\n";
            $writes = 100000;
            self::loop('Oeuvres\Kit\Bitset\\'.$className, $width, $writes, 10*$writes);
        }
    }

    static public function b64(): void
    {
        foreach (["BitBool", "BitString", "BitInt", "BitIntFull"] as $className) {
            echo str_pad($className, 15);
            $obj = 'Oeuvres\Kit\Bitset\\'.$className;
            $bitSet = new $obj();
            $bitSet->set(0);
            $bitSet->set(1);
            $bitSet->set(2);
            $bitSet->set(3);
            $bitSet->set(4);
            $bitSet->set(5);
            $bitSet->set(32);
            $bitSet->set(64);
            $bitSet->set(71);
            $bitSet->set(72);
            echo implode('-', str_split( bin2hex($bitSet->toBytes()), 16));
            $base64 = $bitSet->toBase64();
            echo '  ' . $base64;
            $bitSet->fromBase64($base64);
            echo ' base64_decode=' . self::hex(base64_decode($base64));
            echo ' toBytes()=' . self::hex($bitSet->toBytes());
            echo "\n";
        }
    }

    static public function hex(string $bytes):string
    {
        return implode('-', str_split( bin2hex($bytes), 16));
    }

    static public function loop(string $className, $width, $writes, $reads): void
    {
        $bitSet = new $className();
        $width--;
        $time_start = microtime(true);
        for ($i = 0; $i < $writes; $i++) {
            $bintIndex = random_int(0, $width);
            $bitSet->set($bintIndex);
        }
        $dur = self::seconds(microtime(true) - $time_start);
        $mem = self::human(memory_get_usage() - self::$memStart);
        echo "writes | $writes | $dur | $mem\n";

        $time_start = microtime(true);
        for ($i = 0; $i < $reads; $i++) {
            $bintIndex = random_int(0, $width);
            $bitSet->get($bintIndex);
        }
        $dur = self::seconds(microtime(true) - $time_start);
        $mem = self::human(memory_get_usage() - self::$memStart);
        echo "reads | $reads| $dur | $mem\n";
        unset($bitSet);
    }

    public static function human($size)
    {
        $unit=['b', 'Kb', 'Mb','Gb','Tb','Pb'];
        return round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
    public static function seconds($seconds)
    {
        return round($seconds,2).' s.';
    }

    public static function test()
    {
        foreach (["BitBool", "BitInt", "BitIntFull", "BitString"] as $className) {
            $set = [1];
            echo str_pad($className, 15);
            $obj = 'Oeuvres\Kit\Bitset\\'.$className;
            $bitSet = new $obj();
            echo implode(',', $set) . "  ";
            foreach ($set as $bit) $bitSet->set($bit);
            echo $bitSet->toBase64();
            $n = 10;
            foreach ($bitSet as $key => $bit) {
                echo " $key:$bit";
                if (!--$n) break; 
            }
            echo "\n";
        }
    }

}
BitSetTest::test();
// BitSetTest::b64();
// BitSetTest::bench();
