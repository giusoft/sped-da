<?php

declare(strict_types=1);

/**
 * @author Raj Trivedi (India), 2009-10-14
 * @modify Taylor Lopes (Brazil), 2012-04-06
 * @update Updated for PHP 8.4 compatibility and PSR-12 standards, 2025-01-01
 */
class barCodeGenrator
{
    private string $file;
    private int $into;
    private array $digitArray = [
        0 => "00110",
        1 => "10001",
        2 => "01001",
        3 => "11000",
        4 => "00101",
        5 => "10100",
        6 => "01100",
        7 => "00011",
        8 => "10010",
        9 => "01010",
    ];

    public function __construct(
        string $value,
        int $into = 1,
        string $filename = 'barcode.gif',
        int $width_bar = 300,
        int $height_bar = 65,
        bool $show_codebar = false
    ) {
        $this->into = $into;
        $this->file = $filename;

        // Pré-computar array de dígitos
        for ($count1 = 9; $count1 >= 0; $count1--) {
            for ($count2 = 9; $count2 >= 0; $count2--) {
                $count = ($count1 * 10) + $count2;
                $text = "";

                for ($i = 1; $i < 6; $i++) {
                    $text .= substr($this->digitArray[$count1], ($i - 1), 1) .
                             substr($this->digitArray[$count2], ($i - 1), 1);
                }

                $this->digitArray[$count] = $text;
            }
        }

        $height_bar_max = $height_bar;
        $width_bar_max = $width_bar;

        $img = imagecreate($width_bar_max, $height_bar_max);

        if ($img === false) {
            throw new RuntimeException('Falha ao criar imagem');
        }

        if ($show_codebar) {
            $height_bar -= 25;
        }

        $cl_black = imagecolorallocate($img, 0, 0, 0);
        $cl_white = imagecolorallocate($img, 255, 255, 255);

        if ($cl_black === false || $cl_white === false) {
            throw new RuntimeException('Falha ao alocar cores');
        }

        // Preencher fundo branco
        imagefilledrectangle($img, 0, 0, $width_bar_max, $height_bar_max, $cl_white);

        // Barras iniciais
        imagefilledrectangle($img, 5, 5, 5, $height_bar, $cl_black);
        imagefilledrectangle($img, 6, 5, 6, $height_bar, $cl_white);
        imagefilledrectangle($img, 7, 5, 7, $height_bar, $cl_black);
        imagefilledrectangle($img, 8, 5, 8, $height_bar, $cl_white);

        $thin = 1;
        $wide = str_contains(strtoupper($_SERVER['SERVER_SOFTWARE'] ?? ''), "WIN32") ? 3 : 2.72;
        $pos = 9;
        $text = $value;

        // Adicionar zero à esquerda se necessário
        if ((strlen($text) % 2) !== 0) {
            $text = "0" . $text;
        }

        // Gerar barras do código
        while (strlen($text) > 0) {
            $i = (int) round((float) $this->jskLeft($text, 2));
            $text = $this->jskRight($text, strlen($text) - 2);

            $f = $this->digitArray[$i];

            for ($i = 1; $i < 11; $i += 2) {
                $f1 = substr($f, ($i - 1), 1) === "0" ? $thin : $wide;
                imagefilledrectangle(
                    $img,
                    $pos,
                    5,
                    (int) ($pos - 1 + $f1),
                    $height_bar,
                    $cl_black
                );
                $pos = $pos + $f1;

                $f2 = substr($f, $i, 1) === "0" ? $thin : $wide;
                imagefilledrectangle(
                    $img,
                    $pos,
                    5,
                    (int) ($pos - 1 + $f2),
                    $height_bar,
                    $cl_white
                );
                $pos = $pos + $f2;
            }
        }

        // Barras finais
        imagefilledrectangle(
            $img,
            $pos,
            5,
            (int) ($pos - 1 + $wide),
            $height_bar,
            $cl_black
        );
        $pos = $pos + $wide;

        imagefilledrectangle(
            $img,
            $pos,
            5,
            (int) ($pos - 1 + $thin),
            $height_bar,
            $cl_white
        );
        $pos = $pos + $thin;

        imagefilledrectangle(
            $img,
            $pos,
            5,
            (int) ($pos - 1 + $thin),
            $height_bar,
            $cl_black
        );

        // Adicionar texto se solicitado
        if ($show_codebar) {
            imagestring($img, 5, 0, $height_bar + 5, " " . $value, $cl_black);
        }

        $this->putImg($img);
    }


    public function jskLeft(string $input, int $comp): string
    {
        return substr($input, 0, $comp);
    }


    public function jskRight(string $input, int $comp): string
    {
        return substr($input, strlen($input) - $comp, $comp);
    }


    public function putImg(\GdImage $image): void
    {
        if ($this->into) {
            imagegif($image, $this->file);
        } else {
            header("Content-type: image/gif");
            imagegif($image);
        }

        imagedestroy($image);
    }
}
