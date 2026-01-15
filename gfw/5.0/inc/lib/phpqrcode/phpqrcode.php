<?php

/*
 * PHP QR Code encoder
 *
 * This file contains MERGED version of PHP QR Code library.
 * It was auto-generated from full version for your convenience.
 *
 * This merged version was configured to not requre any external files,
 * with disabled cache, error loging and weker but faster mask matching.
 * If you need tune it up please use non-merged version.
 *
 * For full version, documentation, examples of use please visit:
 *
 *    http://phpqrcode.sourceforge.net/
 *    https://sourceforge.net/projects/phpqrcode/
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

/*
 * Version: 1.1.4
 * Build: 2010100721
 */

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

// QR modes
define('QR_MODE_NUL', -1);
define('QR_MODE_NUM', 0);
define('QR_MODE_AN', 1);
define('QR_MODE_8', 2);
define('QR_MODE_KANJI', 3);
define('QR_MODE_STRUCTURE', 4);

// Levels of error correction
define('QR_ECLEVEL_L', 0);
define('QR_ECLEVEL_M', 1);
define('QR_ECLEVEL_Q', 2);
define('QR_ECLEVEL_H', 3);

// Supported output formats
define('QR_FORMAT_TEXT', 0);
define('QR_FORMAT_PNG', 1);

class qrstr
{
    public static function set(
        array &$srctab,
        int $x,
        int $y,
        string $repl,
        int|false $replLen = false
    ): void {
        $srctab[$y] = substr_replace(
            $srctab[$y],
            $replLen !== false ? substr($repl, 0, $replLen) : $repl,
            $x,
            $replLen !== false ? $replLen : strlen($repl)
        );
    }
}

/*
 * PHP QR Code encoder
 * Config file
 */

define('QR_CACHEABLE', false);
define('QR_CACHE_DIR', false);
define('QR_LOG_DIR', false);

define('QR_FIND_BEST_MASK', true);
define('QR_FIND_FROM_RANDOM', 2);
define('QR_DEFAULT_MASK', 2);

define('QR_PNG_MAXIMUM_SIZE', 1024);

/*
 * PHP QR Code encoder
 *
 * Toolset, handy and debug utilites.
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

class QRtools
{
    // ---------------------------------------------------------------------
    public static function binarize(array $frame): array
    {
        $len = count($frame);

        foreach ($frame as &$frameLine) {
            for ($i = 0; $i < $len; $i++) {
                $frameLine[$i] = (ord($frameLine[$i]) & 1) ? '1' : '0';
            }
        }

        return $frame;
    }

    // ---------------------------------------------------------------------
    public static function tcpdfBarcodeArray(
        string $code,
        array|string $mode = 'QR,L',
        string $tcPdfVersion = '4.5.037'
    ): array {
        if (!is_array($mode)) {
            $mode = explode(',', $mode);
        }

        $eccLevel = $mode[1] ?? 'L';

        $qrTab = QRcode::text($code, false, $eccLevel);
        $size = count($qrTab);

        $barcodeArray = [
            'num_rows' => $size,
            'num_cols' => $size,
            'bcode' => [],
        ];

        foreach ($qrTab as $line) {
            $row = [];

            foreach (str_split($line) as $char) {
                $row[] = $char === '1' ? 1 : 0;
            }

            $barcodeArray['bcode'][] = $row;
        }

        return $barcodeArray;
    }

    // ---------------------------------------------------------------------
    public static function clearCache(): void
    {
        self::$frames = [];
    }

    // ---------------------------------------------------------------------
    public static function buildCache(): void
    {
        self::markTime('before_build_cache');

        $mask = new QRmask();

        for ($version = 1; $version <= QRSPEC_VERSION_MAX; $version++) {
            $frame = QRspec::newFrame($version);

            if (QR_IMAGE) {
                $fileName = QR_CACHE_DIR . 'frame_' . $version . '.png';
                QRimage::png(self::binarize($frame), $fileName, 1, 0);
            }

            $width = count($frame);
            $bitMask = array_fill(0, $width, array_fill(0, $width, 0));

            for ($maskNo = 0; $maskNo < 8; $maskNo++) {
                $mask->makeMaskNo(
                    $maskNo,
                    $width,
                    $frame,
                    $bitMask,
                    true
                );
            }
        }

        self::markTime('after_build_cache');
    }

    // ---------------------------------------------------------------------
    public static function log(string|false $outfile, string $err): void
    {
        if (QR_LOG_DIR === false || $err === '') {
            return;
        }

        $filename = $outfile !== false
            ? QR_LOG_DIR . basename($outfile) . '-errors.txt'
            : QR_LOG_DIR . 'errors.txt';

        file_put_contents(
            $filename,
            date('Y-m-d H:i:s') . ': ' . $err,
            FILE_APPEND
        );
    }

    // ---------------------------------------------------------------------
    public static function dumpMask(array $frame): void
    {
        $width = count($frame);

        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                echo ord($frame[$y][$x]) . ',';
            }
        }
    }

    // ---------------------------------------------------------------------
    public static function markTime(string $markerId): void
    {
        [$usec, $sec] = explode(' ', microtime());
        $time = (float) $usec + (float) $sec;

        if (!isset($GLOBALS['qr_time_bench'])) {
            $GLOBALS['qr_time_bench'] = [];
        }

        $GLOBALS['qr_time_bench'][$markerId] = $time;
    }

    // ---------------------------------------------------------------------
    public static function timeBenchmark(): void
    {
        self::markTime('finish');

        $lastTime = 0;
        $startTime = 0;
        $p = 0;

        echo '<table cellpadding="3" cellspacing="1">
            <thead>
                <tr style="border-bottom:1px solid silver">
                    <td colspan="2" style="text-align:center">BENCHMARK</td>
                </tr>
            </thead>
            <tbody>';

        foreach ($GLOBALS['qr_time_bench'] as $markerId => $thisTime) {
            if ($p > 0) {
                echo '<tr>
                    <th style="text-align:right">till ' . $markerId . ':</th>
                    <td>' . number_format($thisTime - $lastTime, 6) . 's</td>
                </tr>';
            } else {
                $startTime = $thisTime;
            }

            $lastTime = $thisTime;
            $p++;
        }

        echo '</tbody>
            <tfoot>
                <tr style="border-top:2px solid black">
                    <th style="text-align:right">TOTAL:</th>
                    <td>' . number_format($lastTime - $startTime, 6) . 's</td>
                </tr>
            </tfoot>
        </table>';
    }
}

// -------------------------------------------------------------------------

QRtools::markTime('start');


/*
 * PHP QR Code encoder
 *
 * QR Code specifications
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * The following data / specifications are taken from
 * "Two dimensional symbol -- QR-code -- Basic Specification" (JIS X0510:2004)
 *  or
 * "Automatic identification and data capture techniques --
 *  QR Code 2005 bar code symbology specification" (ISO/IEC 18004:2006)
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

define('QRSPEC_VERSION_MAX', 40);
define('QRSPEC_WIDTH_MAX',   177);

define('QRCAP_WIDTH',        0);
define('QRCAP_WORDS',        1);
define('QRCAP_REMINDER',     2);
define('QRCAP_EC',           3);

class QRspec
{

    public static array $capacity = [
        [  0,    0, 0, [   0,    0,    0,    0]],
        [ 21,   26, 0, [   7,   10,   13,   17]],
        [ 25,   44, 7, [  10,   16,   22,   28]],
        [ 29,   70, 7, [  15,   26,   36,   44]],
        [ 33,  100, 7, [  20,   36,   52,   64]],
        [ 37,  134, 7, [  26,   48,   72,   88]],
        [ 41,  172, 7, [  36,   64,   96,  112]],
        [ 45,  196, 0, [  40,   72,  108,  130]],
        [ 49,  242, 0, [  48,   88,  132,  156]],
        [ 53,  292, 0, [  60,  110,  160,  192]],
        [ 57,  346, 0, [  72,  130,  192,  224]],
        [ 61,  404, 0, [  80,  150,  224,  264]],
        [ 65,  466, 0, [  96,  176,  260,  308]],
        [ 69,  532, 0, [ 104,  198,  288,  352]],
        [ 73,  581, 3, [ 120,  216,  320,  384]],
        [ 77,  655, 3, [ 132,  240,  360,  432]],
        [ 81,  733, 3, [ 144,  280,  408,  480]],
        [ 85,  815, 3, [ 168,  308,  448,  532]],
        [ 89,  901, 3, [ 180,  338,  504,  588]],
        [ 93,  991, 3, [ 196,  364,  546,  650]],
        [ 97, 1085, 3, [ 224,  416,  600,  700]],
        [101, 1156, 4, [ 224,  442,  644,  750]],
        [105, 1258, 4, [ 252,  476,  690,  816]],
        [109, 1364, 4, [ 270,  504,  750,  900]],
        [113, 1474, 4, [ 300,  560,  810,  960]],
        [117, 1588, 4, [ 312,  588,  870, 1050]],
        [121, 1706, 4, [ 336,  644,  952, 1110]],
        [125, 1828, 4, [ 360,  700, 1020, 1200]],
        [129, 1921, 3, [ 390,  728, 1050, 1260]],
        [133, 2051, 3, [ 420,  784, 1140, 1350]],
        [137, 2185, 3, [ 450,  812, 1200, 1440]],
        [141, 2323, 3, [ 480,  868, 1290, 1530]],
        [145, 2465, 3, [ 510,  924, 1350, 1620]],
        [149, 2611, 3, [ 540,  980, 1440, 1710]],
        [153, 2761, 3, [ 570, 1036, 1530, 1800]],
        [157, 2876, 0, [ 570, 1064, 1590, 1890]],
        [161, 3034, 0, [ 600, 1120, 1680, 1980]],
        [165, 3196, 0, [ 630, 1204, 1770, 2100]],
        [169, 3362, 0, [ 660, 1260, 1860, 2220]],
        [173, 3532, 0, [ 720, 1316, 1950, 2310]],
        [177, 3706, 0, [ 750, 1372, 2040, 2430]],
    ];

    public static function getDataLength(int $version, int $level): int
    {
        return self::$capacity[$version][QRCAP_WORDS]
             - self::$capacity[$version][QRCAP_EC][$level];
    }

    public static function getECCLength(int $version, int $level): int
    {
        return self::$capacity[$version][QRCAP_EC][$level];
    }

    public static function getWidth(int $version): int
    {
        return self::$capacity[$version][QRCAP_WIDTH];
    }

    public static function getRemainder(int $version): int
    {
        return self::$capacity[$version][QRCAP_REMINDER];
    }

    public static function getMinimumVersion(int $size, int $level): int
    {
        for ($i = 1; $i <= QRSPEC_VERSION_MAX; $i++) {
            $words = self::getDataLength($i, $level);
            if ($words >= $size) {
                return $i;
            }
        }
        return -1;
    }

    //######################################################################
    public static array $lengthTableBits = [
        [10, 12, 14],
        [ 9, 11, 13],
        [ 8, 16, 16],
        [ 8, 10, 12]
    ];



    public static function lengthIndicator(int $mode, int $version): int
    {
        if ($mode === QR_MODE_STRUCTURE) {
            return 0;
        }

        if ($version <= 9) {
            $l = 0;
        } elseif ($version <= 26) {
            $l = 1;
        } else {
            $l = 2;
        }

        return self::$lengthTableBits[$mode][$l];
    }



    public static function maximumWords(int $mode, int $version): int
    {
        if ($mode === QR_MODE_STRUCTURE) {
            return 3;
        }

        if ($version <= 9) {
            $l = 0;
        } elseif ($version <= 26) {
            $l = 1;
        } else {
            $l = 2;
        }

        $bits  = self::$lengthTableBits[$mode][$l];
        $words = (1 << $bits) - 1;

        if ($mode === QR_MODE_KANJI) {
            $words *= 2;
        }

        return $words;
    }



    public static array $eccTable = [
        [[ 0,  0], [ 0,  0], [ 0,  0], [ 0,  0]],
        [[ 1,  0], [ 1,  0], [ 1,  0], [ 1,  0]],
        [[ 1,  0], [ 1,  0], [ 1,  0], [ 1,  0]],
        [[ 1,  0], [ 1,  0], [ 2,  0], [ 2,  0]],
        [[ 1,  0], [ 2,  0], [ 2,  0], [ 4,  0]],
        [[ 1,  0], [ 2,  0], [ 2,  2], [ 2,  2]],
        [[ 2,  0], [ 4,  0], [ 4,  0], [ 4,  0]],
        [[ 2,  0], [ 4,  0], [ 2,  4], [ 4,  1]],
        [[ 2,  0], [ 2,  2], [ 4,  2], [ 4,  2]],
        [[ 2,  0], [ 3,  2], [ 4,  4], [ 4,  4]],
        [[ 2,  2], [ 4,  1], [ 6,  2], [ 6,  2]],
        [[ 4,  0], [ 1,  4], [ 4,  4], [ 3,  8]],
        [[ 2,  2], [ 6,  2], [ 4,  6], [ 7,  4]],
        [[ 4,  0], [ 8,  1], [ 8,  4], [12,  4]],
        [[ 3,  1], [ 4,  5], [11,  5], [11,  5]],
        [[ 5,  1], [ 5,  5], [ 5,  7], [11,  7]],
        [[ 5,  1], [ 7,  3], [15,  2], [ 3, 13]],
        [[ 1,  5], [10,  1], [ 1, 15], [ 2, 17]],
        [[ 5,  1], [ 9,  4], [17,  1], [ 2, 19]],
        [[ 3,  4], [ 3, 11], [17,  4], [ 9, 16]],
        [[ 3,  5], [ 3, 13], [15,  5], [15, 10]],
        [[ 4,  4], [17,  0], [17,  6], [19,  6]],
        [[ 2,  7], [17,  0], [ 7, 16], [34,  0]],
        [[ 4,  5], [ 4, 14], [11, 14], [16, 14]],
        [[ 6,  4], [ 6, 14], [11, 16], [30,  2]],
        [[ 8,  4], [ 8, 13], [ 7, 22], [22, 13]],
        [[10,  2], [19,  4], [28,  6], [33,  4]],
        [[ 8,  4], [22,  3], [ 8, 26], [12, 28]],
        [[ 3, 10], [ 3, 23], [ 4, 31], [11, 31]],
        [[ 7,  7], [21,  7], [ 1, 37], [19, 26]],
        [[ 5, 10], [19, 10], [15, 25], [23, 25]],
        [[13,  3], [ 2, 29], [42,  1], [23, 28]],
        [[17,  0], [10, 23], [10, 35], [19, 35]],
        [[17,  1], [14, 21], [29, 19], [11, 46]],
        [[13,  6], [14, 23], [44,  7], [59,  1]],
        [[12,  7], [12, 26], [39, 14], [22, 41]],
        [[ 6, 14], [ 6, 34], [46, 10], [ 2, 64]],
        [[17,  4], [29, 14], [49, 10], [24, 46]],
        [[ 4, 18], [13, 32], [48, 14], [42, 32]],
        [[20,  4], [40,  7], [43, 22], [10, 67]],
        [[19,  6], [18, 31], [34, 34], [20, 61]],
    ];



    public static function getEccSpec(int $version, int $level, array &$spec): void
    {
        if (count($spec) < 5) {
            $spec = [0, 0, 0, 0, 0];
        }

        $b1   = self::$eccTable[$version][$level][0];
        $b2   = self::$eccTable[$version][$level][1];
        $data = self::getDataLength($version, $level);
        $ecc  = self::getECCLength($version, $level);

        if ($b2 === 0) {
            $spec[0] = $b1;
            $spec[1] = intdiv($data, $b1);
            $spec[2] = intdiv($ecc, $b1);
            $spec[3] = 0;
            $spec[4] = 0;
        } else {
            $spec[0] = $b1;
            $spec[1] = intdiv($data, ($b1 + $b2));
            $spec[2] = intdiv($ecc,  ($b1 + $b2));
            $spec[3] = $b2;
            $spec[4] = $spec[1] + 1;
        }
    }

    // Alignment pattern ---------------------------------------------------

    // Positions of alignment patterns.
    // This array includes only the second and the third position of the
    // alignment patterns. Rest of them can be calculated from the distance
    // between them.
    // See Table 1 in Appendix E (pp.71) of JIS X0510:2004.
    public static array $alignmentPattern = [
        [ 0,  0],
        [ 0,  0], [18,  0], [22,  0], [26,  0], [30,  0],
        [34,  0], [22, 38], [24, 42], [26, 46], [28, 50],
        [30, 54], [32, 58], [34, 62], [26, 46], [26, 48],
        [26, 50], [30, 54], [30, 56], [30, 58], [34, 62],
        [28, 50], [26, 50], [30, 54], [28, 54], [32, 58],
        [30, 58], [34, 62], [26, 50], [30, 54], [26, 52],
        [30, 56], [34, 60], [30, 58], [34, 62], [30, 54],
        [24, 50], [28, 54], [32, 58], [26, 54], [30, 58],
    ];



    public static function putAlignmentMarker(array &$frame, int $ox, int $oy): void
    {
        $finder = [
            "\xa1\xa1\xa1\xa1\xa1",
            "\xa1\xa0\xa0\xa0\xa1",
            "\xa1\xa0\xa1\xa0\xa1",
            "\xa1\xa0\xa0\xa0\xa1",
            "\xa1\xa1\xa1\xa1\xa1"
        ];

        $yStart = $oy - 2;
        $xStart = $ox - 2;

        for ($y = 0; $y < 5; $y++) {
            QRstr::set($frame, $xStart, $yStart + $y, $finder[$y]);
        }
    }



    public static function putAlignmentPattern(int $version, array &$frame, int $width): void
    {
        if ($version < 2) {
            return;
        }

        $d = self::$alignmentPattern[$version][1] - self::$alignmentPattern[$version][0];

        if ($d < 0) {
            $w = 2;
        } else {
            $w = (int)(($width - self::$alignmentPattern[$version][0]) / $d + 2);
        }

        if ($w * $w - 3 === 1) {
            $x = self::$alignmentPattern[$version][0];
            $y = self::$alignmentPattern[$version][0];
            self::putAlignmentMarker($frame, $x, $y);
            return;
        }

        $cx = self::$alignmentPattern[$version][0];
        for ($x = 1; $x < $w - 1; $x++) {
            self::putAlignmentMarker($frame, 6, $cx);
            self::putAlignmentMarker($frame, $cx, 6);
            $cx += $d;
        }

        $cy = self::$alignmentPattern[$version][0];
        for ($y = 0; $y < $w - 1; $y++) {
            $cx = self::$alignmentPattern[$version][0];
            for ($x = 0; $x < $w - 1; $x++) {
                self::putAlignmentMarker($frame, $cx, $cy);
                $cx += $d;
            }
            $cy += $d;
        }
    }

    // Version information pattern -----------------------------------------

    // Version information pattern (BCH coded).
    // See Table 1 in Appendix D (pp.68) of JIS X0510:2004.
    // size: [QRSPEC_VERSION_MAX - 6]
    public static array $versionPattern = [
        0x07c94, 0x085bc, 0x09a99, 0x0a4d3, 0x0bbf6, 0x0c762, 0x0d847, 0x0e60d,
        0x0f928, 0x10b78, 0x1145d, 0x12a17, 0x13532, 0x149a6, 0x15683, 0x168c9,
        0x177ec, 0x18ec4, 0x191e1, 0x1afab, 0x1b08e, 0x1cc1a, 0x1d33f, 0x1ed75,
        0x1f250, 0x209d5, 0x216f0, 0x228ba, 0x2379f, 0x24b0b, 0x2542e, 0x26a64,
        0x27541, 0x28c69
    ];

    public static function getVersionPattern(int $version): int
    {
        if ($version < 7 || $version > QRSPEC_VERSION_MAX) {
            return 0;
        }

        return self::$versionPattern[$version - 7];
    }



    public static array $formatInfo = [
        [0x77c4, 0x72f3, 0x7daa, 0x789d, 0x662f, 0x6318, 0x6c41, 0x6976],
        [0x5412, 0x5125, 0x5e7c, 0x5b4b, 0x45f9, 0x40ce, 0x4f97, 0x4aa0],
        [0x355f, 0x3068, 0x3f31, 0x3a06, 0x24b4, 0x2183, 0x2eda, 0x2bed],
        [0x1689, 0x13be, 0x1ce7, 0x19d0, 0x0762, 0x0255, 0x0d0c, 0x083b]
    ];

    public static function getFormatInfo(int $mask, int $level): int
    {
        if ($mask < 0 || $mask > 7 || $level < 0 || $level > 3) {
            return 0;
        }

        return self::$formatInfo[$level][$mask];
    }

    // Frame ---------------------------------------------------------------
    // Cache of initial frames.
    public static array $frames = [];

    /** --------------------------------------------------------------------
     * Put a finder pattern.
     * @param frame
     * @param width
     * @param ox,oy upper-left coordinate of the pattern
     */
    public static function putFinderPattern(array &$frame, int $ox, int $oy): void
    {
        $finder = [
            "\xc1\xc1\xc1\xc1\xc1\xc1\xc1",
            "\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
            "\xc1\xc1\xc1\xc1\xc1\xc1\xc1"
        ];

        for ($y = 0; $y < 7; $y++) {
            QRstr::set($frame, $ox, $oy + $y, $finder[$y]);
        }
    }


    public static function createFrame(int $version): array
    {
        $width = self::$capacity[$version][QRCAP_WIDTH];
        $frameLine = str_repeat("\0", $width);
        $frame = array_fill(0, $width, $frameLine);

        self::putFinderPattern($frame, 0, 0);
        self::putFinderPattern($frame, $width - 7, 0);
        self::putFinderPattern($frame, 0, $width - 7);

        // Separator
        $yOffset = $width - 7;

        for($y=0; $y<7; $y++) {
            $frame[$y][7] = "\xc0";
            $frame[$y][$width - 8] = "\xc0";
            $frame[$yOffset][7] = "\xc0";
            $yOffset++;
        }

        $setPattern = str_repeat("\xc0", 8);

        QRstr::set($frame, 0, 7, $setPattern);
        QRstr::set($frame, $width-8, 7, $setPattern);
        QRstr::set($frame, 0, $width - 8, $setPattern);

        // Format info
        $setPattern = str_repeat("\x84", 9);
        QRstr::set($frame, 0, 8, $setPattern);
        QRstr::set($frame, $width - 8, 8, $setPattern, 8);

        $yOffset = $width - 8;

        for($y=0; $y<8; $y++,$yOffset++) {
            $frame[$y][8] = "\x84";
            $frame[$yOffset][8] = "\x84";
        }

        // Timing pattern
        for($i=1; $i<$width-15; $i++) {
            $frame[6][7+$i] = chr(0x90 | ($i & 1));
            $frame[7+$i][6] = chr(0x90 | ($i & 1));
        }

        // Alignment pattern
        self::putAlignmentPattern($version, $frame, $width);

        // Version information
        if($version >= 7) {
            $vinf = self::getVersionPattern($version);

            $v = $vinf;

            for($x=0; $x<6; $x++) {
                for($y=0; $y<3; $y++) {
                    $frame[($width - 11)+$y][$x] = chr(0x88 | ($v & 1));
                    $v = $v >> 1;
                }
            }

            $v = $vinf;
            for($y=0; $y<6; $y++) {
                for($x=0; $x<3; $x++) {
                    $frame[$y][$x+($width - 11)] = chr(0x88 | ($v & 1));
                    $v = $v >> 1;
                }
            }
        }

        // and a little bit...
        $frame[$width - 8][8] = "\x81";

        return $frame;
    }


    public static function debug($frame, $binary_mode = false)
    {
        if ($binary_mode) {

                foreach ($frame as &$frameLine) {
                    $frameLine = join('<span class="m">&nbsp;&nbsp;</span>', explode('0', $frameLine));
                    $frameLine = join('&#9608;&#9608;', explode('1', $frameLine));
                }

                ?>
            <style>
                .m { background-color: white; }
            </style>
            <?php
                echo '<pre><tt><br/ ><br/ ><br/ >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                echo join("<br/ >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $frame);
                echo '</tt></pre><br/ ><br/ ><br/ ><br/ ><br/ ><br/ >';

        } else {

            foreach ($frame as &$frameLine) {
                $frameLine = join('<span class="m">&nbsp;</span>',  explode("\xc0", $frameLine));
                $frameLine = join('<span class="m">&#9618;</span>', explode("\xc1", $frameLine));
                $frameLine = join('<span class="p">&nbsp;</span>',  explode("\xa0", $frameLine));
                $frameLine = join('<span class="p">&#9618;</span>', explode("\xa1", $frameLine));
                $frameLine = join('<span class="s">&#9671;</span>', explode("\x84", $frameLine)); //format 0
                $frameLine = join('<span class="s">&#9670;</span>', explode("\x85", $frameLine)); //format 1
                $frameLine = join('<span class="x">&#9762;</span>', explode("\x81", $frameLine)); //special bit
                $frameLine = join('<span class="c">&nbsp;</span>',  explode("\x90", $frameLine)); //clock 0
                $frameLine = join('<span class="c">&#9719;</span>', explode("\x91", $frameLine)); //clock 1
                $frameLine = join('<span class="f">&nbsp;</span>',  explode("\x88", $frameLine)); //version
                $frameLine = join('<span class="f">&#9618;</span>', explode("\x89", $frameLine)); //version
                $frameLine = join('&#9830;', explode("\x01", $frameLine));
                $frameLine = join('&#8901;', explode("\0", $frameLine));
            }

            ?>
            <style>
                .p { background-color: yellow; }
                .m { background-color: #00FF00; }
                .s { background-color: #FF0000; }
                .c { background-color: aqua; }
                .x { background-color: pink; }
                .f { background-color: gold; }
            </style>
            <?php
            echo "<pre><tt>";
            echo join("<br/ >", $frame);
            echo "</tt></pre>";

        }
    }


    public static function serial(array $frame): string
    {
        return gzcompress(implode("\n", $frame), 9);
    }

    public static function unserial(string $code): array
    {
        return explode("\n", gzuncompress($code));
    }


    public static function newFrame($version)
    {
        if ($version < 1 || $version > QRSPEC_VERSION_MAX) {
            return null;
        }

        if (!isset(self::$frames[$version])) {

            $fileName = QR_CACHE_DIR.'frame_'.$version.'.dat';

            if (QR_CACHEABLE) {
                if (file_exists($fileName)) {
                    self::$frames[$version] = self::unserial(file_get_contents($fileName));
                } else {
                    self::$frames[$version] = self::createFrame($version);
                    file_put_contents($fileName, self::serial(self::$frames[$version]));
                }
            } else {
                self::$frames[$version] = self::createFrame($version);
            }
        }

        if (is_null(self::$frames[$version])) {
            return null;
        }

        return self::$frames[$version];
    }


    public static function rsBlockNum(array $spec): int     { return $spec[0] + $spec[3]; }
    public static function rsBlockNum1(array $spec): int    { return $spec[0]; }
    public static function rsDataCodes1(array $spec): int   { return $spec[1]; }
    public static function rsEccCodes1(array $spec): int    { return $spec[2]; }
    public static function rsBlockNum2(array $spec): int    { return $spec[3]; }
    public static function rsDataCodes2(array $spec): int   { return $spec[4]; }
    public static function rsEccCodes2(array $spec): int    { return $spec[2]; }
    public static function rsDataLength(array $spec): int   { return ($spec[0] * $spec[1]) + ($spec[3] * $spec[4]); }
    public static function rsEccLength(array $spec): int    { return ($spec[0] + $spec[3]) * $spec[2]; }

}


/*
 * PHP QR Code encoder
 *
 * Image output of code using GD2
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

define('QR_IMAGE', true);

class QRimage
{


    public static function png(
        array $frame,
        string|false $filename = false,
        int $pixelPerPoint = 4,
        int $outerFrame = 4,
        bool $saveAndPrint = false
    ): void {
        $image = self::image($frame, $pixelPerPoint, $outerFrame);

        if ($filename === false) {
            header('Content-Type: image/png');
            imagepng($image);
        } else {
            imagepng($image, $filename);

            if ($saveAndPrint === true) {
                header('Content-Type: image/png');
                imagepng($image);
            }
        }

        imagedestroy($image);
    }

    // ----------------------------------------------------------------------
    public static function jpg(
        array $frame,
        string|false $filename = false,
        int $pixelPerPoint = 8,
        int $outerFrame = 4,
        int $quality = 85
    ): void {
        $image = self::image($frame, $pixelPerPoint, $outerFrame);

        if ($filename === false) {
            header('Content-Type: image/jpeg');
            imagejpeg($image, null, $quality);
        } else {
            imagejpeg($image, $filename, $quality);
        }

        imagedestroy($image);
    }

    // ----------------------------------------------------------------------
    private static function image(
        array $frame,
        int $pixelPerPoint = 4,
        int $outerFrame = 4
    ): GdImage {
        $h = count($frame);
        $w = strlen($frame[0]);

        $imgW = $w + 2 * $outerFrame;
        $imgH = $h + 2 * $outerFrame;

        // Imagem base
        $baseImage = imagecreatetruecolor($imgW, $imgH);

        $white = imagecolorallocate($baseImage, 255, 255, 255);
        $black = imagecolorallocate($baseImage, 0, 0, 0);

        imagefill($baseImage, 0, 0, $white);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($frame[$y][$x] === '1') {
                    imagesetpixel(
                        $baseImage,
                        $x + $outerFrame,
                        $y + $outerFrame,
                        $black
                    );
                }
            }
        }

        // Imagem final redimensionada
        $targetImage = imagecreatetruecolor(
            $imgW * $pixelPerPoint,
            $imgH * $pixelPerPoint
        );

        imagecopyresized(
            $targetImage,
            $baseImage,
            0,
            0,
            0,
            0,
            $imgW * $pixelPerPoint,
            $imgH * $pixelPerPoint,
            $imgW,
            $imgH
        );

        imagedestroy($baseImage);

        return $targetImage;
    }
}


/*
 * PHP QR Code encoder
 *
 * Input encoding class
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

define('STRUCTURE_HEADER_BITS',  20);
define('MAX_STRUCTURED_SYMBOLS', 16);

class QRinputItem
{
    public int $mode;
    public int $size;
    public array $data;
    public ?QRbitstream $bstream;

    public function __construct(
        int $mode,
        int $size,
        array $data,
        ?QRbitstream $bstream = null
    ) {
        $setData = array_slice($data, 0, $size);

        if (count($setData) < $size) {
            $setData = array_merge(
                $setData,
                array_fill(0, $size - count($setData), 0)
            );
        }

        if (!QRinput::check($mode, $size, $setData)) {
            throw new Exception(
                'Error m:' . $mode . ',s:' . $size . ',d:' . implode(',', $setData)
            );
        }

        $this->mode    = $mode;
        $this->size    = $size;
        $this->data    = $setData;
        $this->bstream = $bstream;
    }

    // ----------------------------------------------------------------------
    public function encodeModeNum(int $version): int
    {
        try {
            $words = intdiv($this->size, 3);
            $bs = new QRbitstream();

            $bs->appendNum(4, 0x1);
            $bs->appendNum(
                QRspec::lengthIndicator(QR_MODE_NUM, $version),
                $this->size
            );

            for ($i = 0; $i < $words; $i++) {
                $val  = (ord($this->data[$i * 3])     - ord('0')) * 100;
                $val += (ord($this->data[$i * 3 + 1]) - ord('0')) * 10;
                $val += (ord($this->data[$i * 3 + 2]) - ord('0'));
                $bs->appendNum(10, $val);
            }

            $rest = $this->size - $words * 3;

            if ($rest === 1) {
                $bs->appendNum(4, ord($this->data[$words * 3]) - ord('0'));
            } elseif ($rest === 2) {
                $val  = (ord($this->data[$words * 3])     - ord('0')) * 10;
                $val += (ord($this->data[$words * 3 + 1]) - ord('0'));
                $bs->appendNum(7, $val);
            }

            $this->bstream = $bs;
            return 0;

        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function encodeModeAn(int $version): int
    {
        try {
            $words = (int)($this->size / 2);
            $bs = new QRbitstream();

            $bs->appendNum(4, 0x02);
            $bs->appendNum(
                QRspec::lengthIndicator(QR_MODE_AN, $version),
                $this->size
            );

            for ($i = 0; $i < $words; $i++) {
                $val  = QRinput::lookAnTable(ord($this->data[$i * 2])) * 45;
                $val += QRinput::lookAnTable(ord($this->data[$i * 2 + 1]));
                $bs->appendNum(11, $val);
            }

            if ($this->size & 1) {
                $bs->appendNum(
                    6,
                    QRinput::lookAnTable(ord($this->data[$words * 2]))
                );
            }

            $this->bstream = $bs;
            return 0;

        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function encodeMode8(int $version): int
    {
        try {
            $bs = new QRbitstream();

            $bs->appendNum(4, 0x4);
            $bs->appendNum(
                QRspec::lengthIndicator(QR_MODE_8, $version),
                $this->size
            );

            for ($i = 0; $i < $this->size; $i++) {
                $bs->appendNum(8, ord($this->data[$i]));
            }

            $this->bstream = $bs;
            return 0;

        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function encodeModeKanji(int $version): int
    {
        try {
            $bs = new QRbitstream();

            $bs->appendNum(4, 0x8);
            $bs->appendNum(
                QRspec::lengthIndicator(QR_MODE_KANJI, $version),
                intdiv($this->size, 2)
            );

            for ($i = 0; $i < $this->size; $i += 2) {
                $val = (ord($this->data[$i]) << 8) | ord($this->data[$i + 1]);
                $val -= ($val <= 0x9ffc) ? 0x8140 : 0xc140;
                $val = (($val >> 8) * 0xc0) + ($val & 0xff);

                $bs->appendNum(13, $val);
            }

            $this->bstream = $bs;
            return 0;

        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function encodeModeStructure(): int
    {
        try {
            $bs = new QRbitstream();

            $bs->appendNum(4, 0x03);
            $bs->appendNum(4, ord($this->data[1]) - 1);
            $bs->appendNum(4, ord($this->data[0]) - 1);
            $bs->appendNum(8, ord($this->data[2]));

            $this->bstream = $bs;
            return 0;

        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function estimateBitStreamSizeOfEntry(int $version): int
    {
        if ($version === 0) {
            $version = 1;
        }

        $bits = match ($this->mode) {
            QR_MODE_NUM       => QRinput::estimateBitsModeNum($this->size),
            QR_MODE_AN        => QRinput::estimateBitsModeAn($this->size),
            QR_MODE_8         => QRinput::estimateBitsMode8($this->size),
            QR_MODE_KANJI     => QRinput::estimateBitsModeKanji($this->size),
            QR_MODE_STRUCTURE => STRUCTURE_HEADER_BITS,
            default           => 0,
        };

        if ($bits === STRUCTURE_HEADER_BITS) {
            return $bits;
        }

        $l = QRspec::lengthIndicator($this->mode, $version);
        $m = 1 << $l;
        $num = (int)(($this->size + $m - 1) / $m);

        return $bits + $num * (4 + $l);
    }

    // ----------------------------------------------------------------------
    public function encodeBitStream(int $version): int
    {
        try {
            unset($this->bstream);
            $words = QRspec::maximumWords($this->mode, $version);

            if ($this->size > $words) {
                $st1 = new self($this->mode, $words, $this->data);
                $st2 = new self(
                    $this->mode,
                    $this->size - $words,
                    array_slice($this->data, $words)
                );

                $st1->encodeBitStream($version);
                $st2->encodeBitStream($version);

                $this->bstream = new QRbitstream();
                $this->bstream->append($st1->bstream);
                $this->bstream->append($st2->bstream);

                unset($st1);
                unset($st2);

            } else {
                $ret = match ($this->mode) {
                    QR_MODE_NUM       => $this->encodeModeNum($version),
                    QR_MODE_AN        => $this->encodeModeAn($version),
                    QR_MODE_8         => $this->encodeMode8($version),
                    QR_MODE_KANJI     => $this->encodeModeKanji($version),
                    QR_MODE_STRUCTURE => $this->encodeModeStructure(),
                    default           => -1,
                };

                if ($ret < 0) {
                    return -1;
                }
            }

            return $this->bstream->size();

        } catch (Exception $e) {
            return -1;
        }
    }
}


class QRinput
{
    /** @var QRinputItem[] */
    public array $items = [];

    private int $version;
    private int $level;

    // ----------------------------------------------------------------------
    public function __construct(int $version = 0, int $level = QR_ECLEVEL_L)
    {
        if ($version < 0 || $version > QRSPEC_VERSION_MAX || $level > QR_ECLEVEL_H) {
            throw new Exception('Invalid version no');
        }

        $this->version = $version;
        $this->level   = $level;
    }

    // ----------------------------------------------------------------------
    public function getVersion(): int
    {
        return $this->version;
    }

    // ----------------------------------------------------------------------
    public function setVersion(int $version): int
    {
        if ($version < 0 || $version > QRSPEC_VERSION_MAX) {
            throw new Exception('Invalid version no');
        }

        $this->version = $version;
        return 0;
    }

    // ----------------------------------------------------------------------
    public function getErrorCorrectionLevel(): int
    {
        return $this->level;
    }

    // ----------------------------------------------------------------------
    public function setErrorCorrectionLevel(int $level): int
    {
        if ($level > QR_ECLEVEL_H) {
            throw new Exception('Invalid ECLEVEL');
        }

        $this->level = $level;
        return 0;
    }

    // ----------------------------------------------------------------------
    public function appendEntry(QRinputItem $entry): void
    {
        $this->items[] = $entry;
    }

    // ----------------------------------------------------------------------
    public function append(int $mode, int $size, array $data): int
    {
        try {
            $this->items[] = new QRinputItem($mode, $size, $data);
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function insertStructuredAppendHeader(
        int $size,
        int $index,
        int $parity
    ): int {
        if ($size > MAX_STRUCTURED_SYMBOLS) {
            throw new Exception('insertStructuredAppendHeader wrong size');
        }

        if ($index <= 0 || $index > MAX_STRUCTURED_SYMBOLS) {
            throw new Exception('insertStructuredAppendHeader wrong index');
        }

        $buf = [$size, $index, $parity];

        try {
            $entry = new QRinputItem(QR_MODE_STRUCTURE, 3, $buf);
            array_unshift($this->items, $entry);
            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    // ----------------------------------------------------------------------
    public function calcParity(): int
    {
        $parity = 0;

        foreach ($this->items as $item) {
            if ($item->mode !== QR_MODE_STRUCTURE) {
                for ($i = $item->size - 1; $i >= 0; $i--) {
                    $parity ^= $item->data[$i];
                }
            }
        }

        return $parity;
    }


    public static function checkModeNum(int $size, array $data): bool
    {
        for ($i = 0; $i < $size; $i++) {
            if (ord($data[$i]) < ord('0') || ord($data[$i]) > ord('9')) {
                return false;
            }
        }
        return true;
    }

    public static function estimateBitsModeNum(int $size): int
    {
        $w = intdiv($size, 3);
        $bits = $w * 10;

        return match ($size - $w * 3) {
            1 => $bits + 4,
            2 => $bits + 7,
            default => $bits,
        };
    }

    public static array $anTable = [
        -1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
        -1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
        36,-1,-1,-1,37,38,-1,-1,-1,-1,39,40,-1,41,42,43,
         0,1,2,3,4,5,6,7,8,9,44,-1,-1,-1,-1,-1,
        -1,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,
        25,26,27,28,29,30,31,32,33,34,35,-1,-1,-1,-1,-1
    ];

    public static function lookAnTable(int $c): int
    {
        return ($c > 127) ? -1 : self::$anTable[$c];
    }

    public static function checkModeAn(int $size, array $data): bool
    {
        for ($i = 0; $i < $size; $i++) {
            if (self::lookAnTable(ord($data[$i])) === -1) {
                return false;
            }
        }
        return true;
    }

    public static function estimateBitsModeAn(int $size): int
    {
        return intdiv($size, 2) * 11 + (($size & 1) ? 6 : 0);
    }

    public static function estimateBitsMode8(int $size): int
    {
        return $size * 8;
    }

    public static function estimateBitsModeKanji(int $size): int
    {
        return intdiv($size, 2) * 13;
    }

    public static function checkModeKanji(int $size, array $data): bool
    {
        if ($size & 1) {
            return false;
        }

        for ($i = 0; $i < $size; $i += 2) {
            $val = (ord($data[$i]) << 8) | ord($data[$i + 1]);
            if (
                $val < 0x8140
                || ($val > 0x9ffc && $val < 0xe040)
                || $val > 0xebbf
            ) {
                return false;
            }
        }

        return true;
    }

    public static function check(int $mode, int $size, array $data): bool
    {
        if ($size <= 0) {
            return false;
        }

        return match ($mode) {
            QR_MODE_NUM       => self::checkModeNum($size, $data),
            QR_MODE_AN        => self::checkModeAn($size, $data),
            QR_MODE_KANJI     => self::checkModeKanji($size, $data),
            QR_MODE_8,
            QR_MODE_STRUCTURE => true,
            default           => false,
        };
    }



    public function estimateBitStreamSize(int $version): int
    {
        $bits = 0;
        foreach ($this->items as $item) {
            $bits += $item->estimateBitStreamSizeOfEntry($version);
        }
        return $bits;
    }

    public function estimateVersion(): int
    {
        $version = 0;
        do {
            $bits = $this->estimateBitStreamSize($version);
            $next = QRspec::getMinimumVersion(
                intdiv($bits + 7, 8),
                $this->level
            );
            if ($next < 0) {
                return -1;
            }
            $version = $next;
        } while ($version > $this->version);

        return $version;
    }

    public static function lengthOfCode(int $mode, int $version, int $bits): int
    {
        $payload = $bits - 4 - QRspec::lengthIndicator($mode, $version);

        switch ($mode) {
            case QR_MODE_NUM:
                $chunks = intdiv($payload, 10);
                $remain = $payload - $chunks * 10;
                $size = $chunks * 3 + (($remain >= 7) ? 2 : (($remain >= 4) ? 1 : 0));
                break;

            case QR_MODE_AN:
                $chunks = intdiv($payload, 11);
                $remain = $payload - $chunks * 11;
                $size = $chunks * 2 + (($remain >= 6) ? 1 : 0);
                break;

            case QR_MODE_8:
                $size = intdiv($payload, 8);
                break;

            case QR_MODE_KANJI:
                $size = intdiv($payload, 13) * 2;
                break;

            case QR_MODE_STRUCTURE:
                $size = intdiv($payload, 8);
                break;

            default:
                $size = 0;
        }

        $max = QRspec::maximumWords($mode, $version);
        return max(0, min($size, $max));
    }

    public function createBitStream(): int
    {
        $total = 0;

        foreach ($this->items as $item) {
            $bits = $item->encodeBitStream($this->version);
            if ($bits < 0) {
                return -1;
            }
            $total += $bits;
        }

        return $total;
    }

    public function convertData(): int
    {
        $ver = $this->estimateVersion();
        if ($ver > $this->version) {
            $this->setVersion($ver);
        }

        for(;;) {
            $bits = $this->createBitStream();

            if ($bits < 0) {
                return -1;
            }

            $ver = QRspec::getMinimumVersion((int)(($bits + 7) / 8), $this->level);
            if($ver < 0) {
                throw new Exception('WRONG VERSION');
                return -1;
            } else if($ver > $this->getVersion()) {
                $this->setVersion($ver);
            } else {
                break;
            }
        }

        return 0;
    }

    public function appendPaddingBit(QRbitstream $bstream): int
    {
        $bits = $bstream->size();
        $maxwords = QRspec::getDataLength($this->version, $this->level);
        $maxbits = $maxwords * 8;

        if ($maxbits == $bits) {
            return 0;
        }

        if ($maxbits - $bits < 5) {
            return $bstream->appendNum($maxbits - $bits, 0);
        }

        $bits += 4;
        $words = intdiv($bits + 7, 8);

        $padding = new QRbitstream();
        $padding->appendNum($words * 8 - $bits + 4, 0);

        $padlen = $maxwords - $words;
        if ($padlen > 0) {
            $padbuf = [];
            for ($i = 0; $i < $padlen; $i++) {
                $padbuf[] = ($i & 1) ? 0x11 : 0xec;
            }
            $padding->appendBytes($padlen, $padbuf);
        }

        return $bstream->append($padding);
    }

    public function mergeBitStream(): ?QRbitstream
    {
        if ($this->convertData() < 0) {
            return null;
        }

        $bstream = new QRbitstream();
        foreach ($this->items as $item) {
            if ($bstream->append($item->bstream) < 0) {
                return null;
            }
        }

        return $bstream;
    }

    public function getBitStream(): ?QRbitstream
    {
        $bstream = $this->mergeBitStream();
        if ($bstream === null) {
            return null;
        }

        return ($this->appendPaddingBit($bstream) < 0) ? null : $bstream;
    }

    public function getByteStream(): ?array
    {
        $bstream = $this->getBitStream();
            if (!$bstream) {
                return null;
            }

            return $bstream->toByte();
    }
}


/*
 * PHP QR Code encoder
 *
 * Bitstream class
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

class QRbitstream
{
    public array $data = [];


    public function size(): int
    {
        return count($this->data);
    }


    public function allocate(int $setLength): int
    {
        $this->data = array_fill(0, $setLength, 0);
        return 0;
    }


    public static function newFromNum(int $bits, int $num): QRbitstream
    {
        $bstream = new self();
        $bstream->allocate($bits);

        $mask = 1 << ($bits - 1);

        for ($i = 0; $i < $bits; $i++) {
            $bstream->data[$i] = ($num & $mask) ? 1 : 0;
            $mask >>= 1;
        }

        return $bstream;
    }


    public static function newFromBytes(int $size, array $data): QRbitstream
    {
        $bstream = new self();
        $bstream->allocate($size * 8);

        $p = 0;

        for ($i = 0; $i < $size; $i++) {
            $mask = 0x80;

            for ($j = 0; $j < 8; $j++) {
                $bstream->data[$p++] = ($data[$i] & $mask) ? 1 : 0;
                $mask >>= 1;
            }
        }

        return $bstream;
    }


    public function append(QRbitstream $arg): int
    {
        if (is_null($arg)) {
            return -1;
        }

        if ($arg->size() === 0) {
            return 0;
        }

        if ($this->size() === 0) {
            $this->data = $arg->data;
            return 0;
        }

        $this->data = array_merge($this->data, $arg->data);

        return 0;
    }


    public function appendNum(int $bits, int $num): int
    {
        if ($bits === 0) {
            return 0;
        }

        $b = self::newFromNum($bits, $num);
        return $this->append($b);
    }


    public function appendBytes(int $size, array $data): int
    {
        if ($size === 0) {
            return 0;
        }

        $b = self::newFromBytes($size, $data);
        return $this->append($b);
    }


    public function toByte(): array
    {
        $size = $this->size();

        if ($size === 0) {
            return [];
        }

        $data = array_fill(0, (int)(($size + 7) / 8), 0);
        $bytes = intdiv($size, 8);

        $p = 0;

        for ($i = 0; $i < $bytes; $i++) {
            $v = 0;

            for ($j = 0; $j < 8; $j++) {
                $v = ($v << 1) | $this->data[$p++];
            }

            $data[$i] = $v;
        }

        if ($size & 7) {
            $v = 0;
            $remaining = $size & 7;

            for ($j = 0; $j < $remaining; $j++) {
                $v = ($v << 1) | $this->data[$p++];
            }

            $data[$bytes] = $v;
        }

        return $data;
    }
}


/*
 * PHP QR Code encoder
 *
 * Input splitting classes
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * The following data / specifications are taken from
 * "Two dimensional symbol -- QR-code -- Basic Specification" (JIS X0510:2004)
 *  or
 * "Automatic identification and data capture techniques --
 *  QR Code 2005 bar code symbology specification" (ISO/IEC 18004:2006)
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
class QRsplit
{
    public string $dataStr = '';
    public QRinput $input;
    public int $modeHint;

    public function __construct(string $dataStr, QRinput $input, int $modeHint)
    {
        $this->dataStr  = $dataStr;
        $this->input    = $input;
        $this->modeHint = $modeHint;
    }


    public static function isdigitat(string $str, int $pos): bool
    {
        if ($pos >= strlen($str)) {
            return false;
        }

        return ((ord($str[$pos]) >= ord('0')) && (ord($str[$pos]) <= ord('9')));
    }


    public static function isalnumat(string $str, int $pos): bool
    {
        if ($pos >= strlen($str)) {
            return false;
        }

        return (QRinput::lookAnTable(ord($str[$pos])) >= 0);
    }


    public function identifyMode(int $pos): int
    {
        if ($pos >= strlen($this->dataStr)) {
            return QR_MODE_NUL;
        }

        if (self::isdigitat($this->dataStr, $pos)) {
            return QR_MODE_NUM;
        }

        if (self::isalnumat($this->dataStr, $pos)) {
            return QR_MODE_AN;
        }

        if ($this->modeHint === QR_MODE_KANJI && $pos + 1 < strlen($this->dataStr)) {

            $word = (ord($c) << 8) | ord($this->dataStr[$pos+1]);

            if (
                ($word >= 0x8140 && $word <= 0x9ffc)
                || ($word >= 0xe040 && $word <= 0xebbf)
            ) {
                return QR_MODE_KANJI;
            }
        }

        return QR_MODE_8;
    }


    public function eatNum(): int
    {
        $ln = QRspec::lengthIndicator(QR_MODE_NUM, $this->input->getVersion());

        $p = 0;
        while (self::isdigitat($this->dataStr, $p)) {
            $p++;
        }

        $run  = $p;
        $mode = $this->identifyMode($p);

        if ($mode === QR_MODE_8) {
            $dif = QRinput::estimateBitsModeNum($run) + 4 + $ln
                + QRinput::estimateBitsMode8(1)         // + 4 + l8
                - QRinput::estimateBitsMode8($run + 1); // - 4 - l8
            if ($dif > 0) {
                return $this->eat8();
            }
        }

        if ($mode === QR_MODE_AN) {
            $dif = QRinput::estimateBitsModeNum($run) + 4 + $ln
                + QRinput::estimateBitsModeAn(1)         // + 4 + la
                - QRinput::estimateBitsModeAn($run + 1); // - 4 - la
            if ($dif > 0) {
                return $this->eatAn();
            }
        }

        $ret = $this->input->append(QR_MODE_NUM, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }


    public function eatAn(): int
    {
        $la = QRspec::lengthIndicator(QR_MODE_AN,  $this->input->getVersion());
        $ln = QRspec::lengthIndicator(QR_MODE_NUM, $this->input->getVersion());

        $p = 0;

        while (self::isalnumat($this->dataStr, $p)) {
            if (self::isdigitat($this->dataStr, $p)) {
                $q = $p;
                while (self::isdigitat($this->dataStr, $q)) {
                    $q++;
                }

                $dif = QRinput::estimateBitsModeAn($p) // + 4 + la
                    + QRinput::estimateBitsModeNum($q - $p) + 4 + $ln
                    - QRinput::estimateBitsModeAn($q); // - 4 - la

                if f($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }

            } else {
                $p++;
            }
        }

        $run = $p;

        if (!self::isalnumat($this->dataStr, $p)) {
            $dif = QRinput::estimateBitsModeAn($run) + 4 + $la
                + QRinput::estimateBitsMode8(1) // + 4 + l8
                - QRinput::estimateBitsMode8($run + 1); // - 4 - l8

            if ($dif > 0) {
                return $this->eat8();
            }
        }

        $ret = $this->input->append(QR_MODE_AN, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }


    public function eatKanji(): int
    {
        $p = 0;

        while ($this->identifyMode($p) === QR_MODE_KANJI) {
            $p += 2;
        }

        $ret = $this->input->append(QR_MODE_KANJI, $p, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }


    public function eat8(): int
    {
        $la = QRspec::lengthIndicator(QR_MODE_AN, $this->input->getVersion());
        $ln = QRspec::lengthIndicator(QR_MODE_NUM, $this->input->getVersion());

        $p = 1;
        $len = strlen($this->dataStr);
        while ($p < $len) {
            $mode = $this->identifyMode($p);

            if ($mode === QR_MODE_KANJI) {
                break;
            }

            if ($mode === QR_MODE_NUM) {
                $q = $p;
                while (self::isdigitat($this->dataStr, $q)) {
                    $q++;
                }

                $dif = QRinput::estimateBitsMode8($p) // + 4 + l8
                    + QRinput::estimateBitsModeNum($q - $p) + 4 + $ln
                    - QRinput::estimateBitsMode8($q); // - 4 - l8

                if ($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }

            } elseif ($mode === QR_MODE_AN) {
                $q = $p;
                while (self::isalnumat($this->dataStr, $q)) {
                    $q++;
                }

                $dif = QRinput::estimateBitsMode8($p)  // + 4 + l8
                    + QRinput::estimateBitsModeAn($q - $p) + 4 + $la
                    - QRinput::estimateBitsMode8($q); // - 4 - l8

                if ($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }

            } else {
                $p++;
            }
        }

        $run = $p;
        $ret = $this->input->append(QR_MODE_8, $run, str_split($this->dataStr));
        if($ret < 0) {
            return -1;
        }

        return $run;
    }


    public function splitString(): int
    {
        while (strlen($this->dataStr) > 0) {
            if($this->dataStr == '') {
                return 0;
            }

            $mode = $this->identifyMode(0);

            switch ($mode) {
                case QR_MODE_NUM:
                    $length = $this->eatNum();
                    break;
                case QR_MODE_AN:
                    $length = $this->eatAn();
                    break;
                case QR_MODE_KANJI:
                    if ($hint == QR_MODE_KANJI) {
                        $length = $this->eatKanji();
                    } else {
                        $length = $this->eat8();
                    }
                    break;
                default:
                    $length = $this->eat8();
                    break;
            }

            if($length == 0) {
                return 0;
            }

            if($length < 0) {
                return -1;
            }

            $this->dataStr = substr($this->dataStr, $length);
        }
    }


    public function toUpper(): string
    {
        $len = strlen($this->dataStr);
        $p = 0;

        while ($p < $len) {
            $mode = self::identifyMode(substr($this->dataStr, $p), $this->modeHint);
            if ($mode == QR_MODE_KANJI) {
                $p += 2;
            } else {
                if (
                    ord($this->dataStr[$p]) >= ord('a')
                    && ord($this->dataStr[$p]) <= ord('z')
                ) {
                    $this->dataStr[$p] = chr(ord($this->dataStr[$p]) - 32);
                }

                $p++;
            }
        }

        return $this->dataStr;
    }


    public static function splitStringToQRinput(
        string $string,
        QRinput $input,
        int $modeHint,
        bool $casesensitive = true
    ): int {
        if (is_null($string) || $string == '\0' || $string == '') {
            throw new Exception('empty string!!!');
        }

        $split = new QRsplit($string, $input, $modeHint);

        if (!$casesensitive) {
            $split->toUpper();
        }

        return $split->splitString();
    }
}


/*
 * PHP QR Code encoder
 *
 * Reed-Solomon error correction support
 *
 * Copyright (C) 2002, 2003, 2004, 2006 Phil Karn, KA9Q
 * (libfec is released under the GNU Lesser General Public License.)
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

class QRrsItem
{
    public int $mm;                 // Bits per symbol
    public int $nn;                 // Symbols per block (= (1<<mm)-1)
    public array $alpha_to = [];    // log lookup table
    public array $index_of = [];    // Antilog lookup table
    public array $genpoly = [];     // Generator polynomial
    public int $nroots;             // Number of generator roots = number of parity symbols
    public int $fcr;                // First consecutive root, index form
    public int $prim;               // Primitive element, index form
    public int $iprim;              // prim-th root of 1, index form
    public int $pad;                // Padding bytes in shortened block
    public int $gfpoly;

    public function modnn(int $x): int
    {
        while ($x >= $this->nn) {
            $x -= $this->nn;
            $x = ($x >> $this->mm) + ($x & $this->nn);
        }
        return $x;
    }



    public static function init_rs_char(
        int $symsize,
        int $gfpoly,
        int $fcr,
        int $prim,
        int $nroots,
        int $pad
    ): ?self {

        // Common code for intializing a Reed-Solomon control block (char or int symbols)
        // Copyright 2004 Phil Karn, KA9Q
        // May be used under the terms of the GNU Lesser General Public License (LGPL)

        // Check parameter ranges
        if ($symsize < 0 || $symsize > 8) {
            return null;
        }

        if ($fcr < 0 || $fcr >= (1 << $symsize)){
            return null;
        }

        if ($prim <= 0 || $prim >= (1 << $symsize)){
            return null;
        }

        if ($nroots < 0 || $nroots >= (1 << $symsize)){
            return null;
        }

        if ($pad < 0 || $pad >= ((1 << $symsize) - 1 - $nroots)){
            return null;
        }

        $rs = new QRrsItem();
        $rs->mm = $symsize;
        $rs->nn = (1 << $symsize) - 1;
        $rs->pad = $pad;
        $rs->gfpoly = $gfpoly;
        $rs->fcr = $fcr;
        $rs->prim = $prim;
        $rs->nroots = $nroots;

        $rs->alpha_to = array_fill(0, $rs->nn + 1, 0);
        $rs->index_of = array_fill(0, $rs->nn + 1, 0);

        $NN =& $rs->nn;
        $A0 =& $NN;

        // Generate Galois field lookup tables
        $rs->index_of[0] = $A0; // log(zero) = -inf
        $rs->alpha_to[$A0] = 0; // alpha**-inf = 0
        $sr = 1;
        for ($i = 0; $i < $rs->nn; $i++) {
            $rs->index_of[$sr] = $i;
            $rs->alpha_to[$i] = $sr;
            $sr <<= 1;
            if ($sr & (1 << $symsize)) {
                $sr ^= $gfpoly;
            }

            $sr &= $rs->nn;
        }

        if ($sr !== 1) {
            return null; // field generator polynomial is not primitive!
        }

        /* Form RS code generator polynomial from its roots */
        $rs->genpoly = array_fill(0, $nroots + 1, 0);
        $rs->genpoly[0] = 1;

        for ($i = 0,$root=$fcr*$prim; $i < $nroots; $i++, $root += $prim) {
            $rs->genpoly[$i+1] = 1;

            // Multiply rs->genpoly[] by  @**(root + x)
            for ($j = $i; $j > 0; $j--) {
                if ($rs->genpoly[$j] != 0) {
                    $rs->genpoly[$j] = $rs->genpoly[$j-1] ^ $rs->alpha_to[$rs->modnn($rs->index_of[$rs->genpoly[$j]] + $root)];
                } else {
                    $rs->genpoly[$j] = $rs->genpoly[$j-1];
                }
            }
            // rs->genpoly[0] can never be zero
            $rs->genpoly[0] = $rs->alpha_to[$rs->modnn($rs->index_of[$rs->genpoly[0]] + $root)];
        }

        // convert rs->genpoly[] to index form for quicker encoding
        for ($i = 0; $i <= $nroots; $i++) {
            $rs->genpoly[$i] = $rs->index_of[$rs->genpoly[$i]];
        }

        return $rs;
    }


    public function encode_rs_char(array $data, array &$parity): void
    {
        $A0 = $this->nn;
        $parity = array_fill(0, $this->nroots, 0);

        $limit = $this->nn - $this->nroots - $this->pad;

        for ($i = 0; $i < $limit; $i++) {
            $feedback = $this->index_of[$data[$i] ^ $parity[0]];

            if ($feedback !== $A0) {
                $feedback = $this->modnn($this->nn - $this->genpoly[$this->nroots] + $feedback);
                for ($j = 1; $j < $this->nroots; $j++) {
                    $parity[$j] ^= $this->alpha_to[
                        $this->modnn($feedback + $this->genpoly[$this->nroots - $j])
                    ];
                }
            }

            // Shift
            for ($j = 0; $j < $this->nroots - 1; $j++) {
                $parity[$j] = $parity[$j + 1];
            }

            $parity[$this->nroots - 1] = ($feedback !== $A0)
                ? $this->alpha_to[$this->modnn($feedback + $this->genpoly[0])]
                : 0;
        }
    }
}


class QRrs
{
    public static array $items = [];

    public static function init_rs(
        int $symsize,
        int $gfpoly,
        int $fcr,
        int $prim,
        int $nroots,
        int $pad
    ): ?QRrsItem {
        foreach (self::$items as $rs) {
            if ($rs->pad !== $pad) {
                continue;
            }

            if ($rs->nroots !== $nroots) {
                continue;
            }

            if ($rs->mm !== $symsize) {
                continue;
            }

            if ($rs->gfpoly !== $gfpoly) {
                continue;
            }

            if ($rs->fcr !== $fcr) {
                continue;
            }

            if ($rs->prim !== $prim) {
                continue;
            }

            return $rs;
        }

        $rs = QRrsItem::init_rs_char(
            $symsize,
            $gfpoly,
            $fcr,
            $prim,
            $nroots,
            $pad
        );

        array_unshift(self::$items, $rs);
        return $rs;
    }
}


/*
 * PHP QR Code encoder
 *
 * Masking
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

define('N1', 3);
define('N2', 3);
define('N3', 40);
define('N4', 10);

class QRmask
{
    public array $runLength = [];

    public function __construct()
    {
        $this->runLength = array_fill(0, QRSPEC_WIDTH_MAX + 1, 0);
    }


    public function writeFormatInformation(
        int $width,
        array &$frame,
        int $mask,
        int $level
    ): int {
        $blacks = 0;
        $format = QRspec::getFormatInfo($mask, $level);

        for ($i = 0; $i < 8; $i++) {
            if ($format & 1) {
                $blacks += 2;
                $v = 0x85;
            } else {
                $v = 0x84;
            }

            $frame[8][$width - 1 - $i] = chr($v);
            if ($i < 6) {
                $frame[$i][8] = chr($v);
            } else {
                $frame[$i + 1][8] = chr($v);
            }

            $format >>= 1;
        }

        for ($i = 0; $i < 7; $i++) {
            if ($format & 1) {
                $blacks += 2;
                $v = 0x85;
            } else {
                $v = 0x84;
            }

            $frame[$width - 7 + $i][8] = chr($v);
            if ($i == 0) {
                $frame[8][7] = chr($v);
            } else {
                $frame[8][6 - $i] = chr($v);
            }

            $format >>= 1;
        }

        return $blacks;
    }


    public function mask0(int $x, int $y): int {
        return ($x + $y) & 1;
    }

    public function mask1(int $x, int $y): int {
        return $y & 1;
    }

    public function mask2(int $x, int $y): int {
        return $x % 3;
    }

    public function mask3(int $x, int $y): int {
        return ($x + $y) % 3;
    }

    public function mask4(int $x, int $y): int {
        return ((int)($y / 2) + (int)($x / 3)) & 1;
    }

    public function mask5(int $x, int $y): int {
        return (($x * $y) & 1) + (($x * $y) % 3);
    }

    public function mask6(int $x, int $y): int {
        return ((($x * $y) & 1) + (($x * $y) % 3)) & 1;
    }

    public function mask7(int $x, int $y): int {
        return ((($x * $y) % 3) + (($x + $y) & 1)) & 1;
    }


    private function generateMaskNo(
        int $maskNo,
        int $width,
        array $frame
    ): array {
        $bitMask = array_fill(0, $width, array_fill(0, $width, 0));

        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (ord($frame[$y][$x]) & 0x80) {
                    $bitMask[$y][$x] = 0;
                } else {
                    $maskFunc = call_user_func(array($this, 'mask' . $maskNo), $x, $y);
                    $bitMask[$y][$x] = ($maskFunc == 0) ? 1 : 0;
                }

            }
        }

        return $bitMask;
    }


    public static function serial(array $bitFrame): string
    {
        return gzcompress(
            implode("\n", array_map(fn($l) => implode('', $l), $bitFrame)),
            9
        );
    }

    public static function unserial(string $code): array
    {
        return array_map(
            'str_split',
            explode("\n", gzuncompress($code))
        );
    }


    public function makeMaskNo(
        int $maskNo,
        int $width,
        array $s,
        array &$d,
        bool $maskGenOnly = false
    ): int {
        $fileName = QR_CACHE_DIR.'mask_'.$maskNo.DIRECTORY_SEPARATOR.'mask_'.$width.'_'.$maskNo.'.dat';

        if (QR_CACHEABLE) {
            if (file_exists($fileName)) {
                $bitMask = self::unserial(file_get_contents($fileName));
            } else {
                $bitMask = $this->generateMaskNo($maskNo, $width, $s);
                if (!file_exists(QR_CACHE_DIR.'mask_'.$maskNo)) {
                    mkdir(QR_CACHE_DIR.'mask_'.$maskNo);
                }

                file_put_contents($fileName, self::serial($bitMask));
            }
        } else {
            $bitMask = $this->generateMaskNo($maskNo, $width, $s);
        }

        if ($maskGenOnly) {
            return 0;
        }

        $d = $s;
        $b = 0;
        for ($y = 0; $y < $width; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($bitMask[$y][$x] == 1) {
                    $d[$y][$x] = chr(ord($s[$y][$x]) ^ (int)$bitMask[$y][$x]);
                }

                $b += (int)(ord($d[$y][$x]) & 1);
            }
        }

        return $b;
    }


    public function makeMask(int $width, array $frame, int $maskNo, int $level): array
    {
        $masked = array_fill(0, $width, str_repeat("\0", $width));
        $this->makeMaskNo($maskNo, $width, $frame, $masked);
        $this->writeFormatInformation($width, $masked, $maskNo, $level);

        return $masked;
    }


    public function calcN1N3(int $length): int
    {
        $demerit = 0;

        for ($i = 0; $i < $length; $i++) {

            if ($this->runLength[$i] >= 5) {
                $demerit += N1 + ($this->runLength[$i] - 5);
            }

            if (($i & 1) === 1) {
                if (
                    $i >= 3 &&
                    $i < ($length - 2) &&
                    ($this->runLength[$i] % 3) === 0
                ) {
                    $fact = (int)($this->runLength[$i] / 3);

                    if (
                        $this->runLength[$i - 2] === $fact
                        && $this->runLength[$i - 1] === $fact
                        && $this->runLength[$i + 1] === $fact
                        && $this->runLength[$i + 2] === $fact
                    ) {
                        if (
                            ($this->runLength[$i - 3] < 0)
                            || ($this->runLength[$i - 3] >= (4 * $fact))
                        ) {
                            $demerit += N3;
                        } elseif (
                            ($i + 3) >= $length
                            || ($this->runLength[$i + 3] >= (4 * $fact))
                        ) {
                            $demerit += N3;
                        }
                    }
                }
            }
        }

        return $demerit;
    }


    public function evaluateSymbol(int $width, array $frame): int
    {
        $demerit = 0;

        for ($y = 0; $y < $width; $y++) {
            $head = 0;
            $this->runLength[0] = 1;

            $frameY = $frame[$y];
            if ($y > 0) {
                $frameYM = $frame[$y-1];
            }

            for ($x = 0; $x < $width; $x++) {

                if ($x > 0 && $y > 0) {
                    $b22 =
                        ord($frameY[$x]) &
                        ord($frameY[$x - 1]) &
                        ord($frameYM[$x]) &
                        ord($frameYM[$x - 1]);

                    $w22 =
                        ord($frameY[$x]) |
                        ord($frameY[$x - 1]) |
                        ord($frameYM[$x]) |
                        ord($frameYM[$x - 1]);

                    if ((($b22 | ($w22 ^ 1)) & 1) === 1) {
                        $demerit += N2;
                    }
                }

                if ($x === 0 && (ord($frameY[$x]) & 1)) {
                    $this->runLength[0] = -1;
                    $head = 1;
                    $this->runLength[$head] = 1;
                } elseif ($x > 0) {
                    if ((ord($frameY[$x]) ^ ord($frameY[$x - 1])) & 1) {
                        $head++;
                        $this->runLength[$head] = 1;
                    } else {
                        $this->runLength[$head]++;
                    }
                }
            }

            $demerit += $this->calcN1N3($head + 1);
        }

        for ($x = 0; $x < $width; $x++) {
            $head = 0;
            $this->runLength[0] = 1;

            for ($y = 0; $y < $width; $y++) {
                if ($y === 0 && (ord($frame[$y][$x]) & 1)) {
                    $this->runLength[0] = -1;
                    $head = 1;
                    $this->runLength[$head] = 1;
                } elseif ($y > 0) {
                    if ((ord($frame[$y][$x]) ^ ord($frame[$y - 1][$x])) & 1) {
                        $head++;
                        $this->runLength[$head] = 1;
                    } else {
                        $this->runLength[$head]++;
                    }
                }
            }

            $demerit += $this->calcN1N3($head + 1);
        }

        return $demerit;
    }


    public function mask(int $width, array $frame, int $level): array
    {
        $minDemerit = PHP_INT_MAX;
        $bestMask = $frame;

        $checkedMasks = [0, 1, 2, 3, 4, 5, 6, 7];

        if (QR_FIND_FROM_RANDOM !== false) {
            $howManyOut = 8 - (QR_FIND_FROM_RANDOM % 9);

            for ($i = 0; $i < $howManyOut; $i++) {
                $remPos = rand(0, count($checkedMasks) - 1);
                unset($checkedMasks[$remPos]);
                $checkedMasks = array_values($checkedMasks);
            }
        }

        foreach ($checkedMasks as $i) {
            $mask = array_fill(0, $width, str_repeat("\0", $width));

            $demerit = 0;
            $blacks  = 0;
            $blacks  = $this->makeMaskNo($i, $width, $frame, $mask);
            $blacks += $this->writeFormatInformation($width, $mask, $i, $level);

            $blacksPercent = (int)(100 * $blacks / ($width * $width));
            $demerit = (int)((abs($blacksPercent - 50) / 5) * N4);
            $demerit += $this->evaluateSymbol($width, $mask);

            if ($demerit < $minDemerit) {
                $minDemerit = $demerit;
                $bestMask = $mask;
                $bestMaskNum = $i;
            }
        }

        return $bestMask;
    }

}



/*
 * PHP QR Code encoder
 *
 * Main encoder classes.
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

class QRrsblock
{
    public int $dataLength;
    public array $data = [];
    public int $eccLength;
    public array $ecc = [];

    public function __construct(
        int $dl,
        array $data,
        int $el,
        array &$ecc,
        QRrsItem $rs
    ) {
        $rs->encode_rs_char($data, $ecc);

        $this->dataLength = $dl;
        $this->data = $data;
        $this->eccLength = $el;
        $this->ecc = $ecc;
    }
}


class QRrawcode
{
    public int $version;
    public array $datacode = [];
    public array $ecccode = [];
    public int $blocks;
    public array $rsblocks = []; //of RSblock
    public int $count = 0;
    public int $dataLength;
    public int $eccLength;
    public int $b1;

    public function __construct(QRinput $input)
    {
        $spec = [0, 0, 0, 0, 0];

        $this->datacode = $input->getByteStream();
        if ($this->datacode === null) {
            throw new Exception('null input string');
        }

        QRspec::getEccSpec(
            $input->getVersion(),
            $input->getErrorCorrectionLevel(),
            $spec
        );

        $this->version     = $input->getVersion();
        $this->b1          = QRspec::rsBlockNum1($spec);
        $this->dataLength  = QRspec::rsDataLength($spec);
        $this->eccLength   = QRspec::rsEccLength($spec);
        $this->ecccode     = array_fill(0, $this->eccLength, 0);
        $this->blocks      = QRspec::rsBlockNum($spec);

        if ($this->init($spec) < 0) {
            throw new Exception('block alloc error');
        }
    }



    public function init(array $spec): int
    {
        $dl = QRspec::rsDataCodes1($spec);
        $el = QRspec::rsEccCodes1($spec);
        $rs = QRrs::init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);

        $blockNo = 0;
        $dataPos = 0;
        $eccPos  = 0;

        for ($i = 0; $i < QRspec::rsBlockNum1($spec); $i++) {
            $ecc = array_slice($this->ecccode, $eccPos);
            $this->rsblocks[$blockNo] = new QRrsblock($dl, array_slice($this->datacode, $dataPos), $el,  $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode,0, $eccPos), $ecc);

            $dataPos += $dl;
            $eccPos  += $el;
            $blockNo++;
        }

        if (QRspec::rsBlockNum2($spec) === 0) {
            return 0;
        }

        $dl = QRspec::rsDataCodes2($spec);
        $el = QRspec::rsEccCodes2($spec);
        $rs = QRrs::init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        if ($rs === null) {
            return -1;
        }

        for ($i = 0; $i < QRspec::rsBlockNum2($spec); $i++) {
            $ecc = array_slice($this->ecccode, $eccPos);

            $this->rsblocks[$blockNo] = new QRrsblock($dl, array_slice($this->datacode, $dataPos), $el, $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode,0, $eccPos), $ecc);

            $dataPos += $dl;
            $eccPos  += $el;
            $blockNo++;
        }

        return 0;
    }



    public function getCode(): int
    {
        if ($this->count < $this->dataLength) {

            $row = $this->count % $this->blocks;
            $col = $this->count / $this->blocks;

            if ($col >= $this->rsblocks[0]->dataLength) {
                $row += $this->b1;
            }

            $ret = $this->rsblocks[$row]->data[$col];

        } elseif ($this->count < $this->dataLength + $this->eccLength) {
            $row = ($this->count - $this->dataLength) % $this->blocks;
            $col = ($this->count - $this->dataLength) / $this->blocks;
            $ret = $this->rsblocks[$row]->ecc[$col];
        } else {
            return 0;
        }

        $this->count++;

        return $ret;
    }
}


class QRcode
{
    public int $version;
    public int $width;
    public array $data;

    public function encodeMask(QRinput $input, int $mask): self
    {
        if ($input->getVersion() < 0 || $input->getVersion() > QRSPEC_VERSION_MAX) {
            throw new Exception('wrong version');
        }

        if ($input->getErrorCorrectionLevel() > QR_ECLEVEL_H) {
            throw new Exception('wrong level');
        }

        $raw = new QRrawcode($input);

        QRtools::markTime('after_raw');

        $version = $raw->version;
        $width   = QRspec::getWidth($version);
        $frame   = QRspec::newFrame($version);

        $filler = new FrameFiller($width, $frame);
        if (is_null($filler)) {
            return null;
        }

        // inteleaved data and ecc codes
        for ($i = 0; $i < $raw->dataLength + $raw->eccLength; $i++) {
            $code = $raw->getCode();
            $bit = 0x80;
            for ($j = 0; $j < 8; $j++) {
                $addr = $filler->next();
                $filler->setFrameAt($addr, 0x02 | (($bit & $code) != 0));
                $bit = $bit >> 1;
            }
        }

        QRtools::markTime('after_filler');

        unset($raw);

        // remainder bits
        $j = QRspec::getRemainder($version);
        for ($i = 0; $i < $j; $i++) {
            $addr = $filler->next();
            $filler->setFrameAt($addr, 0x02);
        }

        $frame = $filler->frame;
        unset($filler);

        // masking
        $maskObj = new QRmask();
        if ($mask < 0) {

            if (QR_FIND_BEST_MASK) {
                $masked = $maskObj->mask($width, $frame, $input->getErrorCorrectionLevel());
            } else {
                $masked = $maskObj->makeMask($width, $frame, (intval(QR_DEFAULT_MASK) % 8), $input->getErrorCorrectionLevel());
            }

        } else {
            $masked = $maskObj->makeMask($width, $frame, $mask, $input->getErrorCorrectionLevel());
        }

        if($masked == null) {
            return null;
        }

        QRtools::markTime('after_mask');

        $this->version = $version;
        $this->width = $width;
        $this->data = $masked;

        return $this;
    }


    public function encodeInput(QRinput $input): self
    {
        return $this->encodeMask($input, -1);
    }


    public function encodeString8bit(
        string $string,
        int $version,
        int $level
    ): self {
        if ($string === '') {
            throw new Exception('empty string!');
        }

        $input = new QRinput($version, $level);
        if ($input == null) {
            return null;
        }

        $ret = $input->append(
            $input,
            QR_MODE_8,
            strlen($string),
            str_split($string)
        );

        if ($ret < 0) {
            unset($input);
            return null;
        }

        return $this->encodeInput($input);
    }


    public function encodeString(
        string $string,
        int $version,
        int $level,
        int $hint,
        bool $casesensitive
    ): self {
        if ($hint !== QR_MODE_8 && $hint !== QR_MODE_KANJI) {
            throw new Exception('bad hint');
        }

        $input = new QRinput($version, $level);
        if ($input == null) {
            return null;
        }

        $ret = QRsplit::splitStringToQRinput(
            $string,
            $input,
            $hint,
            $casesensitive
        );

        if ($ret < 0) {
            return null;
        }

        return $this->encodeInput($input);
    }


    public static function png(
        string $text,
        string|false $outfile = false,
        int $level = QR_ECLEVEL_L,
        int $size = 3,
        int $margin = 4,
        bool $saveandprint = false
    ) {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encodePNG($text, $outfile, $saveandprint);
    }


    public static function text(
        string $text,
        string|false $outfile = false,
        int $level = QR_ECLEVEL_L,
        int $size = 3,
        int $margin = 4
    ) {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encode($text, $outfile);
    }



    public static function raw(
        string $text,
        string|false $outfile = false,
        int $level = QR_ECLEVEL_L,
        int $size = 3,
        int $margin = 4
    ) {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encodeRAW($text, $outfile);
    }
}


class FrameFiller
{
    public int $width;
    public array $frame;
    public int $x;
    public int $y;
    public int $dir;
    public int $bit;


    public function __construct(int $width, array $frame)
    {
        $this->width = $width;
        $this->frame = $frame;
        $this->x     = $width - 1;
        $this->y     = $width - 1;
        $this->dir   = -1;
        $this->bit   = -1;
    }


    public function setFrameAt(array $at, int $val): void
    {
        $this->frame[$at['y']][$at['x']] = chr($val);
    }


    public function getFrameAt(array $at): int
    {
        return ord($this->frame[$at['y']][$at['x']]);
    }


    public function next(): ?array
    {
        do {
            if ($this->bit === -1) {
                $this->bit = 0;
                return ['x' => $this->x, 'y' => $this->y];
            }

            $x = $this->x;
            $y = $this->y;
            $w = $this->width;

            if ($this->bit === 0) {
                $x--;
                $this->bit++;
            } else {
                $x++;
                $y += $this->dir;
                $this->bit--;
            }

            if ($this->dir < 0) {
                if ($y < 0) {
                    $y = 0;
                    $x -= 2;
                    $this->dir = 1;

                    if ($x === 6) {
                        $x--;
                        $y = 9;
                    }
                }
            } else {
                if ($y === $w) {
                    $y = $w - 1;
                    $x -= 2;
                    $this->dir = -1;

                    if ($x === 6) {
                        $x--;
                        $y -= 8;
                    }
                }
            }

            if ($x < 0 || $y < 0) {
                return null;
            }

            $this->x = $x;
            $this->y = $y;

        } while (ord($this->frame[$y][$x]) & 0x80);

        return ['x' => $x, 'y' => $y];
    }
}


class QRencode
{
    public bool $casesensitive = true;
    public bool $eightbit = false;

    public int $version = 0;
    public int $size = 3;
    public int $margin = 4;

    public int $structured = 0; // not supported yet

    public int $level = QR_ECLEVEL_L;
    public int $hint = QR_MODE_8;

    public static function factory(
        int|string $level = QR_ECLEVEL_L,
        int $size = 3,
        int $margin = 4
    ): self {
        $enc = new QRencode();
        $enc->size   = $size;
        $enc->margin = $margin;

        switch ((string)$level) {
            case '0':
            case '1':
            case '2':
            case '3':
                $enc->level = (int)$level;
                break;

            case 'l':
            case 'L':
                $enc->level = QR_ECLEVEL_L;
                break;

            case 'm':
            case 'M':
                $enc->level = QR_ECLEVEL_M;
                break;

            case 'q':
            case 'Q':
                $enc->level = QR_ECLEVEL_Q;
                break;

            case 'h':
            case 'H':
                $enc->level = QR_ECLEVEL_H;
                break;

            default:
                $enc->level = QR_ECLEVEL_L;
        }

        return $enc;
    }


    public function encodeRAW(string $intext, string|false $outfile = false): array
    {
        $code = new QRcode();

        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString(
                $intext,
                $this->version,
                $this->level,
                $this->hint,
                $this->casesensitive
            );
        }

        return $code->data;
    }


    public function encode(string $intext, string|false $outfile = false): array|null
    {
        $code = new QRcode();

        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString(
                $intext,
                $this->version,
                $this->level,
                $this->hint,
                $this->casesensitive
            );
        }

        QRtools::markTime('after_encode');

        if (!$outfile) {
            return QRtools::binarize($code->data);
        }

        file_put_contents($outfile, join("\n", QRtools::binarize($code->data)));
    }


    public function encodePNG(
        string $intext,
        string|false $outfile = false,
        bool $saveandprint = false
    ): void {
        try {
            ob_start();
            $tab = $this->encode($intext);
            $err = ob_get_clean();

            if ($err !== '') {
                QRtools::log($outfile, $err);
            }

            $maxSize = (int)(QR_PNG_MAXIMUM_SIZE / (count($tab) + 2 * $this->margin));

            QRimage::png(
                $tab,
                $outfile,
                min(max(1, $this->size), $maxSize),
                $this->margin,
                $saveandprint
            );

        } catch (Exception $e) {
            QRtools::log($outfile, $e->getMessage());
        }
    }
}

