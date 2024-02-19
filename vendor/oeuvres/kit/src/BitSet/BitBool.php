<?php declare(strict_types=1);

/**
 * BitSet implemented as a sparce array of booleans
 * 
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2023 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Kit\BitSet;
 
use InvalidArgumentException, OutOfRangeException;

class BitBool extends BitSet
{
    const ZERO = 0;
    const ONE = 1;
    /**
     * Array of booleans
     */
    private $data = [];

    public function __construct()
    {
    }


    public function set(int $bitIndex):void
    {
        if ($bitIndex < 0) {
            throw new OutOfRangeException("\$bitIndex={$bitIndex}, negative index not supported");
        }
        $this->data[$bitIndex] = SELF::ONE;
        $this->length = max($this->length, ++$bitIndex);
    }



    /**
     * Returns the bool value of the bit at the specified index
     *
     * @throws OutOfRangeException
     *
     * @param int $bitIndex
     *
     * @return bool
     */
    public function get(int $bitIndex):bool
    {
        if ($bitIndex < 0) {
            throw new OutOfRangeException("\$bitIndex={$bitIndex}, negative index not supported");
        }
        // because size is not defined, answer
        if (!isset($this->data[$bitIndex])) {
            return false;
        }
        return $this->data[$bitIndex];
    }

    /**
     * Output a binary string of data with no holes, 
     * OK for base64 conversion
     */
    public function toBytes($bin=false): string
    {
        // rounding to next 8 multiple, to ensure to not forget a byte
        $length = $this->length;
        $chars = '';
        for ($i = 0; $i < $length; $i += 8) {
            // convert binary to big Endian byte
            $codePoint = 
                  (isset($this->data[$i + 0])? $this->data[$i + 0] << 0: 0)
                | (isset($this->data[$i + 1])? $this->data[$i + 1] << 1: 0)
                | (isset($this->data[$i + 2])? $this->data[$i + 2] << 2: 0)
                | (isset($this->data[$i + 3])? $this->data[$i + 3] << 3: 0)
                | (isset($this->data[$i + 4])? $this->data[$i + 4] << 4: 0)
                | (isset($this->data[$i + 5])? $this->data[$i + 5] << 5: 0)
                | (isset($this->data[$i + 6])? $this->data[$i + 6] << 6: 0)
                | (isset($this->data[$i + 7])? $this->data[$i + 7] << 7: 0)
            ;
            if ($bin) {
                $chars .= strrev(decbin($codePoint)) . ' ';
            }
            else {
                $chars .= chr($codePoint);
            }
        }
        return $chars;
    }

    public function fromBytes(string $bytes = null): bool
    {
        if (empty($bytes)) {
            $this->data = [];
            return false;
        }

        $this->data = [];
        $len = strlen($bytes);
        for ($charIndex = 0; $charIndex < $len; $charIndex++) {
            $bitIndex = $charIndex << 3;
            $c = ord($bytes[$charIndex]);
            if ($c === 0) continue;
            if ($c&1  ) $this->data[$bitIndex + 0] = self::ONE;
            if ($c&2  ) $this->data[$bitIndex + 1] = self::ONE;
            if ($c&4  ) $this->data[$bitIndex + 2] = self::ONE;
            if ($c&8  ) $this->data[$bitIndex + 3] = self::ONE;
            if ($c&16 ) $this->data[$bitIndex + 4] = self::ONE;
            if ($c&32 ) $this->data[$bitIndex + 5] = self::ONE;
            if ($c&64 ) $this->data[$bitIndex + 6] = self::ONE;
            if ($c&128) $this->data[$bitIndex + 7] = self::ONE;
        }
        return true;
    }

    /**
     * Returns a human-readable string representation of the bit set as binary
     *
     * @return string
     */
    public function __toString(): string
    {
        return trim($this->toBytes(true));
    }


    /**
     * Returns the highest set bit plus one
     *
     * @return int
     */
    public function length(): int
    {
        return $this->length;
    }

    public function rewind(): void
    {
        $value = reset($this->data);
        while(!$value) {
            // caution, false is reserved as end reach
            if ($value === false) {
                $this->itValid = false;
                return;
            }
            $value = next($this->data);
        }
        $this->itValid = true;
        $this->itKey = 0;
        $this->itBit = key($this->data);
    }

    public function next(): void
    {
        $value = next($this->data);
        while(!$value) {
            // caution, false is reserved as end reach
            if ($value === false) {
                $this->itValid = false;
                return;
            }
            $value = next($this->data);
        }
        $this->itKey++;
        $this->itBit = key($this->data);
    }
}
