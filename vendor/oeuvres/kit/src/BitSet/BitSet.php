<?php declare(strict_types=1);

/**
 * BitSet 
 * 
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2023 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Kit\BitSet;
 
use InvalidArgumentException, Iterator;

abstract class BitSet implements Iterator
{
    /**
     * Table of bit set 
     */
    const BIT_POS = [
        0x0000000000000001 => 0,
        0x0000000000000002 => 1,
        0x0000000000000004 => 2,
        0x0000000000000008 => 3,
        0x0000000000000010 => 4,
        0x0000000000000020 => 5,
        0x0000000000000040 => 6,
        0x0000000000000080 => 7,
        0x0000000000000100 => 8,
        0x0000000000000200 => 9,
        0x0000000000000400 => 10,
        0x0000000000000800 => 11,
        0x0000000000001000 => 12,
        0x0000000000002000 => 13,
        0x0000000000004000 => 14,
        0x0000000000008000 => 15,
        0x0000000000010000 => 16,
        0x0000000000020000 => 17,
        0x0000000000040000 => 18,
        0x0000000000080000 => 19,
        0x0000000000100000 => 20,
        0x0000000000200000 => 21,
        0x0000000000400000 => 22,
        0x0000000000800000 => 23,
        0x0000000001000000 => 24,
        0x0000000002000000 => 25,
        0x0000000004000000 => 26,
        0x0000000008000000 => 27,
        0x0000000010000000 => 28,
        0x0000000020000000 => 29,
        0x0000000040000000 => 30,
        0x0000000080000000 => 31,
        0x0000000100000000 => 32,
        0x0000000200000000 => 33,
        0x0000000400000000 => 34,
        0x0000000800000000 => 35,
        0x0000001000000000 => 36,
        0x0000002000000000 => 37,
        0x0000004000000000 => 38,
        0x0000008000000000 => 39,
        0x0000010000000000 => 40,
        0x0000020000000000 => 41,
        0x0000040000000000 => 42,
        0x0000080000000000 => 43,
        0x0000100000000000 => 44,
        0x0000200000000000 => 45,
        0x0000400000000000 => 46,
        0x0000800000000000 => 47,
        0x0001000000000000 => 48,
        0x0002000000000000 => 49,
        0x0004000000000000 => 50,
        0x0008000000000000 => 51,
        0x0010000000000000 => 52,
        0x0020000000000000 => 53,
        0x0040000000000000 => 54,
        0x0080000000000000 => 55,
        0x0100000000000000 => 56,
        0x0200000000000000 => 57,
        0x0400000000000000 => 58,
        0x0800000000000000 => 59,
        0x1000000000000000 => 60,
        0x2000000000000000 => 61,
        0x4000000000000000 => 62,
        0x8000000000000000 => 63,
    ];

    /**
     * Biggest bit index
     */
    protected $length = 0;
    /**
     * Iterator, current value
     */
    protected $itBit = -1;
    /**
     * Iterator, current key, count of bit = 1
     */
    protected $itKey = -1;
    /**
     * Iterator, end is reached
     */
    protected $itValid; 
    /**
     * Returns the highest set bit plus one
     *
     * @return int
     */
    public function length(): int
    {
        return $this->length;
    }

    abstract public function rewind(): void;

    abstract public function next(): void;

    public function current(): int
    {
        return $this->itBit;
    }

    public function key(): int
    {
        return $this->itKey;
    }

    public function valid(): bool {
        return $this->itValid;
    }

    /**
     * Returns the bits as binary string of bytes
     */
    abstract public function toBytes(): string;
    
    /**
     * Base 64 export of bytes
     */
    public function toBase64(): string
    {
        return base64_encode($this->toBytes());
    }

    /**
     * Import bits from a binary string of bytes
     */
    abstract public function fromBytes(string $bytes): bool;
    /**
     * From Base 64 import bytes
     */
    public function fromBase64(string $base64=null): bool
    {
        if (empty($base64)) {
            $this->length = 0;
            $this->fromBytes('');
            return true;
        }
        // maybe a base64 URL compat
        $base64 = strtr($base64, '-_', '+/');
        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            return false;
        }
        return $this->fromBytes($bytes);
    }

    /**
     * Returns a human-readable string representation of the bit set as binary
     * in little endian order.
     */
    public function __toString(): string
    {
        $chars = '';
        $bytes = $this->toBytes();
        $len = strlen($bytes);
        for($i = 0 ; $i < $len ; $i++) {
            $chars .= ' ' . strrev(decbin(ord($bytes[$i])));
        }
        return trim($chars);
    }
    /**
     * Sets the bit at the specified index to true.
     */
    abstract public function set(int $bitIndex):void;
    /**
     * Get the bit at the specified index.
     */
    abstract public function get(int $bitIndex):bool;

}