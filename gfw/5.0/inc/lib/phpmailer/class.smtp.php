<?php
/*
 * ADAPTER SMTP
 * Mantém compatibilidade caso o sistema antigo tente dar require em 'class.smtp.php'
 */

// 1. Carrega o arquivo novo real
require_once __DIR__ . '/SMTP.php';

// 2. Importa o Namespace novo
use PHPMailer\PHPMailer\SMTP as SMTPNovo;

// 3. Cria o Alias (Apelido)
// Se o sistema antigo tentar usar a classe "SMTP" globalmente, redireciona para a nova
if (!class_exists('SMTP')) {
    class_alias(SMTPNovo::class, 'SMTP');
}