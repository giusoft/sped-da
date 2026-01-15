<?php

/*
 * PHP QR Code encoder
 *
 * Common constants
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Encoding modes
define('QR_MODE_NUL', -1);
define('QR_MODE_NUM', 0);
define('QR_MODE_AN', 1);
define('QR_MODE_8', 2);
define('QR_MODE_KANJI', 3);
define('QR_MODE_STRUCTURE', 4);

// Levels of error correction.
define('QR_ECLEVEL_L', 0);
define('QR_ECLEVEL_M', 1);
define('QR_ECLEVEL_Q', 2);
define('QR_ECLEVEL_H', 3);

// Supported output formats
define('QR_FORMAT_TEXT', 0);
define('QR_FORMAT_PNG',  1);

class qrstr
{
    /**
     *
     * @param array  $srctab   Array de strings (passado por referência)
     * @param int    $x        Posição inicial
     * @param int    $y        Índice da linha
     * @param string $repl     String de substituição
     * @param int|false $replLen Comprimento opcional da substituição
     */
    public static function set(
        array &$srctab,
        int $x,
        int $y,
        string $repl,
        int|false $replLen = false
    ): void {
        $srctab[$y] = substr_replace(
            $srctab[$y],
            ($replLen !== false) ? substr($repl, 0, $replLen) : $repl,
            $x,
            ($replLen !== false) ? $replLen : strlen($repl)
        );
    }
}