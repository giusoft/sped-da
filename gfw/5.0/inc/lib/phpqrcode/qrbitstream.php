<?php
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


    public static function newFromNum(int $bits, int $num): self
    {
        $bstream = new QRbitstream();
        $bstream->allocate($bits);

        $mask = 1 << ($bits - 1);

        for ($i = 0; $i < $bits; $i++) {
            $bstream->data[$i] = ($num & $mask) ? 1 : 0;
            $mask >>= 1;
        }

        return $bstream;
    }


    public static function newFromBytes(int $size, array $data): self
    {
        $bstream = new QRbitstream();
        $bstream->allocate($size * 8);

        $p = 0;

        for ($i = 0; $i < $size; $i++) {
            $mask = 0x80;
            for ($j = 0; $j < 8; $j++) {
                $bstream->data[$p] = ($data[$i] & $mask) ? 1 : 0;
                $p++;
                $mask >>= 1;
            }
        }

        return $bstream;
    }


    public function append(QRbitstream $arg): int
    {
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

        $b = QRbitstream::newFromNum($bits, $num);
        if (is_null($b)) {
            return -1;
        }

        return $this->append($b);
    }


    public function appendBytes(int $size, array $data): int
    {
        if ($size === 0) {
            return 0;
        }

        $b = QRbitstream::newFromBytes($size, $data);
        if (is_null($b)) {
            return -1;
        }

        return $this->append($b);
    }


    public function toByte(): array
    {
        $size = $this->size();

        if ($size === 0) {
            return [];
        }

        $data  = array_fill(0, (int)(($size + 7) / 8), 0);
        $bytes = (int)($size / 8);

        $p = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $v = 0;
            for ($j = 0; $j < 8; $j++) {
                $v = ($v << 1) | $this->data[$p];
                $p++;
            }

            $data[$i] = $v;
        }

        if ($size & 7) {
            $v = 0;
            for ($j = 0; $j < ($size & 7); $j++) {
                $v = $v << 1;
                $v |= $this->data[$p];
                $p++;
            }
            $data[$bytes] = $v;
        }

        return $data;
    }
}

