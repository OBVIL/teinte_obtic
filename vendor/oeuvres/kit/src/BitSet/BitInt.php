<?php declare(strict_types=1);

/**
 * BitSet implemented as 
 * 
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2023 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Kit\BitSet;
 
use InvalidArgumentException, OutOfRangeException;

class BitInt extends BitSet
{
    /**
     * @var array of int
     */
    protected array $data = [];
    /**
     * Biggest int index
     */
    protected int $intLength = -1;
    /**
     * PHP integer, number of bits to shift from bitIndex to get intIndex
     */
    static int $intShift;
    /**
     * PHP integer, modulo
     */
    static int $intModulo;


    public function __construct()
    {
        // PHP integers maybe 32 or 64 bits 
        self::$intShift = intval(log(PHP_INT_SIZE << 3, 2));
        self::$intModulo = (PHP_INT_SIZE << 3);
    }

    private function resize(int $bitIndex): int
    {
        $full = (get_class($this) == 'Oeuvres\Kit\Bitset\BitIntFull');
        $intLength = $this->intLength;
        if ($bitIndex < 0) {
            throw new OutOfRangeException("\$bitIndex={$bitIndex}, negative index not supported");
        }
        $this->length = max($this->length, $bitIndex + 1);
        $intIndex = $bitIndex >> self::$intShift;


        if ($full && $intIndex >= $intLength) {
            $this->data = array_merge(
                $this->data, 
                array_fill($intLength, $intIndex - ($intLength - 1), 0) 
            );
        }
        else if (!isset($this->data[$intIndex])) {
            $this->data[$intIndex] = 0;
        }
        $this->intLength = max($intLength, $intIndex + 1);
        return $intIndex;
    }


    public function set(int $bitIndex):void
    {
        $intIndex = $this->resize($bitIndex);
        // bits can shift out left side
        $bitMask = 1 << ($bitIndex % self::$intModulo);
        $this->data[$intIndex] = $this->data[$intIndex] | $bitMask;
    }

    public function get(int $bitIndex):bool
    {
        // because size is not defined, answer
        if ($bitIndex >= $this->length) {
            return false;
        }
        $intIndex = $bitIndex >> self::$intShift;
        if (!isset($this->data[$intIndex])) {
            return false;
            
        }
        $bitMask = 1 << ($bitIndex % self::$intModulo);
        return boolval($this->data[$intIndex] & $bitMask);
    }

    /**
     * Returns the number of bits of space actually in use by this BitSet to represent bit values.
     *
     * @return int
     */
    public function size()
    {
        return count($this->data) << self::$intShift;
    }

    public function toBytes(): string
    {
        $chars = '';
        $end = $this->intLength;
        if (PHP_INT_SIZE == 8) {
            $format = 'q*'; // unsigned long long
        }
        else {
            $format = 'P';
        }
        // integer processed one by one, because of endian consistency
        for ($i = 0; $i < $end; $i++) {
            if (!isset($this->data[$i])) {
                $chars .= str_repeat("\00", PHP_INT_SIZE);
                continue;
            }
            $chars .= pack($format, $this->data[$i]);
        }
        // trim chars to last bit
        // $remaining = $bitIndex >> self::$intShift;
        return substr($chars, 0, ($this->length + 7) >> 3);
    }

    public function fromBytes(string $bytes = null): bool
    {
        if (empty($bytes)) {
            $this->data = [];
            return true;
        }
        $full = (get_class($this) == 'Oeuvres\Kit\Bitset\BitIntFull');
        $charLen = strlen($bytes);
        // ensure bytes to be multiple of int size
        $bytes .= str_repeat("\00", PHP_INT_SIZE - ($charLen % PHP_INT_SIZE));
        $data = [];
        $intIndex = -1;
        if (PHP_INT_SIZE == 8) {
            for ($charIndex = 0; $charIndex < $charLen; $charIndex += PHP_INT_SIZE) {
                $intIndex++;
                $intValue = 
                      ord($bytes[$charIndex + 0]) << 0
                    | ord($bytes[$charIndex + 1]) << 8
                    | ord($bytes[$charIndex + 2]) << 16
                    | ord($bytes[$charIndex + 3]) << 24
                    | ord($bytes[$charIndex + 4]) << 32
                    | ord($bytes[$charIndex + 5]) << 40
                    | ord($bytes[$charIndex + 6]) << 48
                    | ord($bytes[$charIndex + 7]) << 56
                ;
                if (!$full && !$intValue) continue;
                $data[$intIndex] = $intValue;
            }
        }
        else {
            for ($charIndex = 0; $charIndex < $charLen; $charIndex += PHP_INT_SIZE) {
                $intIndex++;
                $intValue = 
                      ord($bytes[$charIndex + 0]) << 0
                    | ord($bytes[$charIndex + 1]) << 8
                    | ord($bytes[$charIndex + 2]) << 16
                    | ord($bytes[$charIndex + 3]) << 24
                ;
                if (!$full && !$intValue) continue;
                $data[$intIndex] = $intValue;
            }
        }
        $this->length = $charLen << 3;
        $this->data = $data;
        return true;
    }

    public function rewind(): void
    {
        $this->itValid = false;
        $this->itKey = -1;
        $this->itBit = -1;
        $word = reset($this->data);
        while(!$word) {
            if ($word === false) {
                return;
            }
            $word = next($this->data);
        }
        $this->itValid = true;
        $this->itKey = 0;
        $intIndex = key($this->data);
        $this->itBit = $intIndex << self::$intShift;
        // find first set bit, should have one
        $bitMask = 1;
        do {
            if (($word & $bitMask) != 0) {
                return;
            }
            $this->itBit++;
            $bitMask <<= 1;
        } while ($bitMask != 0);
        // Should not arrive
    }

    public function next(): void
    {
        // loop in array with holes
        $bitIndex = $this->itBit;
        // search after current value
        $bitIndex++;
        $bitMask = 1 << ($bitIndex % self::$intModulo);
        // first bit of next word
        if ($bitMask === 1) {
            $word = next($this->data);
        }
        else {
            $word = current($this->data);
        }
        do {
            // end of data, go out
            if ($word === false) {
                $this->itValid = false;
                return;
            }
                // empty word, go next
            if ($word === 0) {
                $word = next($this->data);
                $bitMask = 1; // reset possible bitMask
                continue;
            }
            while ($bitMask != 0) {
                if (($word & $bitMask) != 0) {
                    // log2(bitMask) will bug with the last bit, in is signed
                    $this->itBit = 
                        (key($this->data) << self::$intShift) 
                        + self::BIT_POS[$bitMask];
                    $this->itKey++;
                    return;
                }
                $bitMask <<= 1; // become 0 when last bit is shifted
            }
            $bitMask = 1; // reset bitMask
            $word = next($this->data);
        } while(true);
    }

}
