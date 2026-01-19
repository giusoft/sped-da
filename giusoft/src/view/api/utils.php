<?php
$AESKEY = "gWms";

if (!function_exists('gCleanField')) {
    function gCleanField($valor)
    {
        if (is_array($valor)) {
            foreach ($valor as $key => $val) {
                $valor[$key] = gCleanField($val);
            }
            return $valor;
        }

        if (!check_utf8($valor)) {
            $valor = utf8_encode($valor);
        }

        $valor = str_replace("'", "‘", $valor);
        $valor = str_replace("\"", "“", $valor);
        $valor = trim($valor);

        return $valor;
    }
}


if (!function_exists('check_utf8')) {
    function check_utf8($str)
    {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c <= 128) {
                continue;
            }

            if ($c > 247) {
                return false;
            } elseif ($c > 239) {
                $bytes = 4;
            } elseif ($c > 223) {
                $bytes = 3;
            } elseif ($c > 191) {
                $bytes = 2;
            } else {
                return false;
            }

            if (($i + $bytes) > $len) {
                return false;
            }

            while($bytes > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) {
                    return false;
                }
                $bytes--;
            }
        }
        return true;
    }
}


if (!function_exists("formatarJson")) {
    function formatarJson($data)
    {
        converterJsonParaUtf8($data);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
        );

        if (apiSendoUsadaExternamente()) {
            exit;
        }
    }
}


if (!function_exists("converterJsonParaUtf8")) {
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


if (!function_exists("gD")) {
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


if (!function_exists("decodificarJson")) {
    function decodificarJson($json, $caminhoLog)
    {
        $json = json_decode($json, true);
        $erro = array(
            JSON_ERROR_NONE => 0,
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        )[json_last_error()];

        if ($erro) {
            gLog($erro, 1, $caminhoLog);
            formatarJson($erro);
        }

        return array_map('gCleanField', $json);
    }
}


if (!function_exists("apiSendoUsadaExternamente")) {
    function apiSendoUsadaExternamente()
    {
        return (bool) $_GET['rota'] ?: (bool) $_GET['recurso'];
    }
}


if (!function_exists("obterEnderecoIp")) {
    function obterEnderecoIp()
    {
        return (($_SERVER['REMOTE_ADDR'] == '::1') || ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')) ? gethostbyname(gethostname()) : $_SERVER['REMOTE_ADDR'];
    }
}


if (!function_exists("base64Encode")) {
    function base64Encode($string)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }
}


if (!function_exists("gerarAssinatura")) {
    function gerarAssinatura($privateKey, $data, $hash = false)
    {
        if ($hash === true) {
            $data = hash('sha256', $data, true);
        }

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64Encode($signature);
    }
}


if (!function_exists("montarParametrosIntegracao")) {
    function montarParametrosIntegracao($empresa)
    {
        $uri = $_SERVER['REQUEST_URI'];
        if (stripos($uri, 'teste')) {
            $ambiente = '/teste';
        }

        if (!$empresa) {
            $partesUri = array_filter(explode('/wms/', $uri))[1];
            $empresa = explode('/', $partesUri)[0];
        }

        return [
            "caminhoSetup"   => $_SERVER['DOCUMENT_ROOT'] . "{$ambiente}/wms/{$empresa}/setup.php",
            "idPessoasCriou" => 1,
            "empresa" => $empresa,
            "transacao" => true
        ];
    }
}


if (!function_exists("gVar")) {
    function gVar($par, $new = "")
    {
        global $gLang, $_gVar;

        if ($new <> "") {
            $_gVar[$par] = $new;
        }

        $sai = "";
        $sai = $_gVar[$par];
        return($sai);
    }
}


if (!function_exists("gLog")) {
    function gLog($txt, $erro = 0, $arq = "") {
        global $debug, $DB, $_SESSION;

        // === 1. Definição de constantes e localização ===
        if (!defined('gAPP_FILE')) {
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            define('gAPP_FILE', $isWindows ? "/gApp_" : "/tmp/gApp_");
            setlocale(LC_ALL, $isWindows ? 'POSIX' : 'english');

            $logPath = file_exists("/var/www/log") ? "/var/www/log/" : "/var/log/";
            define('gLogPath', $logPath);
        }

        // === 2. Verificação se o log deve ser ativado ===
        $logAtivo = (
            strpos($_SERVER["HTTP_HOST"], "localhost") !== false ||
            strpos($_SERVER["HTTP_HOST"], "127.0.0.1") !== false ||
            file_exists("/tmp/gLogEnabled") ||
            true // forçado para sempre ativar
        );

        if (!$logAtivo) return;

        // === 3. Arquivos de log e configurações ===
        $logfile    = gLogPath . gVar("global.logfile");
        $sqllogfile = gLogPath . gVar("global.sqllogfile");
        $loglevel   = gVar("global.debug");

        if (!empty($arq)) {
            $logfile = gLogPath . $arq;
        }

        if (empty($logfile)) return;

        // === 4. Preparação do texto ===
        $txt = str_replace(["\n", "\r"], '', $txt);

        // === 5. Códigos de cor ANSI (visuais para terminal) ===
        $colors = [
            'default' => "\033[0;00;33m",
            'info'    => "\033[0;40;37m",
            'error'   => "\033[1;00;31m",
            'line'    => "\033[0;00;36m",
            'reset'   => "\033[0;00;37m"
        ];

        $cpre = $erro ? $colors['error'] : $colors['info'];
        $lpre = $colors['line'];
        $cpos = $colors['reset'];

        // === 6. Rastreio da origem da chamada ===
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        unset($trace[0]); // remove a própria chamada da função

        array_splice($trace, -2); // remove as últimas chamadas irrelevantes

        $deb = array_map(function ($t) {
            return basename($t['file']) . ":" . $t['function'] . ":" . $t['line'];
        }, array_reverse($trace));

        $deb = implode(" => ", $deb);

        // === 7. Detectar se é um SQL ===
        $isSql = stripos($txt, "select") !== false ||
                stripos($txt, "update") !== false ||
                stripos($txt, "insert") !== false ||
                stripos($txt, "delete") !== false;

        // === 8. Endereço IP ===
        $ipAddress = ($_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1')
            ? gethostbyname(gethostname())
            : $_SERVER['REMOTE_ADDR'];

        // === 9. Cabeçalho do log ===
        $logHeader = $colors['default'] . date("y-m-d H:i:s") . " " . $ipAddress;

        // === 10. Montagem da mensagem ===
        if ($isSql) {
            $logMessage = "$logHeader\t{$lpre}{$deb}{$colors['reset']}\t{$cpre}{$txt}{$cpos}";
        } else {
            $logMessage = "$logHeader\tLOG:\t{$lpre}{$deb}{$cpos}\t{$cpre}{$txt}{$cpos}";
        }

        // === 11. Escrita no arquivo ===
        $logMessage = trim(preg_replace('/\s+/', ' ', $logMessage)) . PHP_EOL;

        if ($fp = @fopen($logfile, 'a')) {
            fputs($fp, $logMessage);
            fclose($fp);
        }
    }

}


if (!function_exists("desencriptar")) {
    function desencriptar($nomeCampo) {
        global $AESKEY;

        $ambiente = '';
        if (strpos($empresa, "/teste") !== false) {
            $ambiente = '/teste';
        }

        return 'CAST(AES_DECRYPT(UNHEX(' . $nomeCampo . '),"' . $AESKEY . '") AS CHAR(150))';
    }
}


if (!function_exists("encriptar")) {
    function encriptar($valorEncriptar) {
        global $AESKEY;

        $ambiente = '';
        if (strpos($empresa, "/teste") !== false) {
            $ambiente = '/teste';
        }

        return 'HEX(AES_ENCRYPT(' . $valorEncriptar . ',"' . $AESKEY . '"))';
    }
}


if (!function_exists("corrigirCodificacaoArray")) {
    function corrigirCodificacaoArray($dados) {
        foreach ($dados as $chave => $valor) {
            if (is_array($valor)) {
                $dados[$chave] = corrigirCodificacaoArray($valor);
            } elseif (is_string($valor)) {
                if (!mb_detect_encoding($valor, 'UTF-8', true)) {
                    $dados[$chave] = utf8_encode($valor);
                } else {
                    $dados[$chave] = mb_convert_encoding($valor, 'UTF-8', 'UTF-8');
                }
            }
        }
        return $dados;
    }
}


if (!function_exists("removerAcentos")) {
    function removerAcentos($texto)
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
            'º' => '.', '°' => '.', 'ª' => '.', '&' => 'e'
        ];

        $texto = strtr($texto, $map);
        $texto = preg_replace("/[^a-zA-Z0-9\s\-\.,;:\/]/", "", $texto);

        return trim($texto);
    }
}

if (!function_exists("removerEstranhos")) {
    function removerEstranhos($texto)
    {
        $texto = removerAcentos($texto);
        return preg_replace(
            '/[^a-z0-9\+\-\=\.\,\!\?\:\;\@\%\&\(\)\{\}\<\>\[\]\s\'\$\/]+/i ',
            '',
            $texto
        );
    }
}


if (!function_exists("extrairNumeros")) {
    function extrairNumeros($texto)
    {
        return preg_replace('/[^0-9]+/i ', '', $texto);
    }
}