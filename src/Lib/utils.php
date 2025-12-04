<?php
$AESKEY = "emiteNota";

if (!function_exists('gCleanField')) {
    function gCleanField($valor)
    {
        if (is_array($valor)) {
            foreach ($valor as $key => $val) {
                $valor[$key] = gCleanField($val);
            }
            return $valor;
        }

        if (!is_string($valor)) {
            return $valor;
        }

        if (!check_utf8($valor)) {
            $valor = utf8_encode($valor);
        }

        $valor = str_replace("'", "‘", $valor);
        $valor = str_replace('"', '“', $valor);
        $valor = trim($valor);

        return $valor;
    }
}


if (!function_exists('check_utf8')) {
    function check_utf8($str)
    {
        if (!is_string($str)) {
            return true;
        }

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);

            if ($c <= 128) continue;

            if ($c > 247) return false;
            elseif ($c > 239) $bytes = 4;
            elseif ($c > 223) $bytes = 3;
            elseif ($c > 191) $bytes = 2;
            else return false;

            if (($i + $bytes) > $len) return false;

            while (--$bytes > 0) {
                $b = ord($str[++$i]);
                if ($b < 128 || $b > 191) return false;
            }
        }

        return true;
    }
}


if (!function_exists('emitirErro')) {
    function emitirErro($mensagem, $codigoHttp = 400, $dadosExtras = [])
    {
        $resposta = [
            'sucesso' => false,
            'status' => $codigoHttp,
            'mensagem' => $mensagem,
        ];

        if ($dadosExtras) {
            $resposta['detalhes'] = $dadosExtras;
        }

        finalizarRequisicao($resposta, $codigoHttp);
    }
}


if (!function_exists('emitirSucesso')) {
    function emitirSucesso($mensagem = 'Operacao concluida com sucesso', $codigoHttp = 200, $dados = [])
    {
        $resposta = [
            'sucesso' => true,
            'status' => $codigoHttp,
            'mensagem' => $mensagem,
        ];

        if ($dados) {
            $resposta['detalhes'] = $dados;
        }

        finalizarRequisicao($resposta, $codigoHttp);
    }
}


if (!function_exists('finalizarRequisicao')) {
    function finalizarRequisicao($resposta, $codigoHttp = 200)
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode($resposta);
        exit;
    }
}


if (!function_exists('converterJsonParaUtf8')) {
    function converterJsonParaUtf8(&$data)
    {
        if (is_array($data)) {
            array_walk_recursive($data, function (&$valor) {
                if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                    $detectado = mb_detect_encoding($valor, mb_list_encodings(), true);
                    if ($detectado !== false) {
                        $valor = mb_convert_encoding($valor, 'UTF-8', $detectado);
                    } else {
                        $valor = mb_convert_encoding($valor, 'UTF-8');
                    }
                }
            });
        } elseif (is_string($data) && !mb_check_encoding($data, 'UTF-8')) {
            $detectado = mb_detect_encoding($data, mb_list_encodings(), true);
            $data = mb_convert_encoding($data, 'UTF-8', $detectado ?: 'UTF-8');
        }
    }
}


if (!function_exists('gD')) {
    function gD($t, $exit = 0)
    {
        $backtrace = debug_backtrace();
        echo $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
        echo "<pre>";
        var_dump($t);
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}


if (!function_exists('soNumeros')) {
    function soNumeros($var)
    {
        return(preg_replace('/[^0-9]+/i ', '', $var));
    }
}


if (!function_exists('tirarPontos')) {
    function tirarPontos($dados)
    {
        return(str_replace('/', '', str_replace(")", "", str_replace("(", "", str_replace(" ", "", str_replace(".", "", str_replace("-", "", $dados)))))));
    }
}


if (!function_exists("desencriptar")) {
    function desencriptar($senha)
    {
        global $AESKEY;

        return openssl_decrypt(
            hex2bin($senha),     // Dados criptografados
            'AES-128-CBC',       // Modo de operação AES-128-CBC
            $AESKEY,             // Chave
            OPENSSL_RAW_DATA,    // Retorna os dados crus sem qualquer codificação
            str_repeat("\0", 16) // IV (Vetor de Inicialização)
        );
    }
}


if (!function_exists("formatarDecimal")) {
    function formatarDecimal($valor, $casas = 2)
    {
        return number_format((float) $valor, $casas, '.', '');
    }
}