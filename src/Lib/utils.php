<?php
$AESKEY = "emiteNota";

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
            $resposta['dados'] = $dados;
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
    function desencriptar($nomeCampo) {
        global $AESKEY;

        return 'CAST(AES_DECRYPT(UNHEX(' . $nomeCampo . '),"' . $AESKEY . '") AS CHAR(150))';
    }
}


if (!function_exists("encriptar")) {
    function encriptar($valorEncriptar) {
        global $AESKEY;

        return 'HEX(AES_ENCRYPT(' . $valorEncriptar . ',"' . $AESKEY . '"))';
    }
}