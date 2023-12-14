<?php declare(strict_types=1);

/**
 * A no-operation bitset, for stats
 * 
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2023 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Kit\BitSet;
 
use InvalidArgumentException, OutOfRangeException;

class BitNoop
{

    /**
     * @return void
     */
    public function set(int $bitIndex)
    {
    }



    /**
     */
    public function get(int $bitIndex)
    {
    }

    public function toBase64(): string
    {
        return '';
    }


}
