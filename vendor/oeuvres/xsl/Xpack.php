<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */
namespace Oeuvres\Xsl;

class Xpack
{
    /** Where it’s place here */
    static private string $dir;
    /**
     * Initialize static variable
     */
    static public function dir()
    {
        if (isset(self::$dir)) return self::$dir;
        self::$dir = __DIR__ . DIRECTORY_SEPARATOR;
        return self::$dir;
    }
}
