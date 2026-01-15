<?php
/*
 * ADAPTER DE COMPATIBILIDADE
 * Este arquivo engana o sistema antigo, carregando a biblioteca nova (PHP 8.4 ready)
 * mas mantendo o nome antigo da classe.
 */

// 1. Carrega os arquivos da nova versão que você já colocou na pasta
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

// 2. Importa os Namespaces (o jeito novo de chamar)
use PHPMailer\PHPMailer\PHPMailer as PHPMailerNovo;
use PHPMailer\PHPMailer\Exception as ExceptionNova;
use PHPMailer\PHPMailer\SMTP as SMTPNovo;

// 3. Cria o apelido (Alias)
// Quando seu sistema fizer "new PHPMailer()", o PHP vai usar o "PHPMailerNovo"
if (!class_exists('PHPMailer')) {
    class_alias(PHPMailerNovo::class, 'PHPMailer');
}

// Opcional: Garante compatibilidade para tratamento de erros antigos
if (!class_exists('phpmailerException')) {
    class_alias(ExceptionNova::class, 'phpmailerException');
}