<?php
/**
 * Classe para enviar mensagens via WhatsApp
 * @author Giuliano
 * @version 1.0
 * @package WhatsApp
 * @copyright 2025
 */
class WhatsApp {
    public $token;
    public $phone_number_id;

    function __construct($token = "", $phone_number_id = "")
    {
        if ($token == "") {
            $token = gVar("whatsapp.token");
            $phone_number_id = gVar("whatsapp.phone_number_id");
        }
        $this->token = $token;
        $this->phone_number_id = $phone_number_id;
    }


    /**
     * Envia uma mensagem usando template
     * @param string $template Nome do template
     * @param string $numero_destino Número de telefone do destinatário
     * @param array $parametros Parâmetros do template
     * @return string Status da mensagem
     */
    function SendTemplate($template, $numero_destino, $parametros)
    {
        $url = "https://graph.facebook.com/v18.0/$this->phone_number_id/messages";

        // Monta os parâmetros do corpo da mensagem
        $componentParams = [];
        foreach ($parametros as $key => $value) {
            $componentParams[] = [
                'type' => 'text',
                'parameter_name' => $key,
                'text' => $value
            ];
        }

        // Monta o payload
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $numero_destino,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => [ 'code' => 'pt_BR' ],
                'components' => [[
                    'type' => 'body',
                    'parameters' => $componentParams
                ]]
            ]
        ];

        $headers = [
            "Authorization: Bearer $this->token",
            "Content-Type: application/json"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $resposta = curl_exec($ch);
        curl_close($ch);

        if (isset($resposta['messages'][0]['message_status'])){
            return $resposta['messages'][0]['message_status'];
        }

        return false;

    }

    /**
     * Envia uma mensagem de texto (tem que ter recebido uma mensagem do destinatário antes para poder enviar uma mensagem de texto)
     * @param string $numero_destino Número de telefone do destinatário
     * @param string $mensagem Mensagem de texto
     * @return string Status da mensagem
     */
    function SendText($numero_destino, $mensagem): void
    {
        $url = "https://graph.facebook.com/v18.0/$this->phone_number_id/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $numero_destino,
            'type' => 'text',
            'text' => [ 'body' => $mensagem ]
        ];

        $headers = [
            "Authorization: Bearer $this->token",
            "Content-Type: application/json"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $resposta = curl_exec($ch);

        if (curl_errno($ch) !== 0) {
            echo "Erro na requisição: " . curl_error($ch);
        } else {
            // echo "Resposta:\n$resposta";
            print_r($resposta);
        }

        curl_close($ch);
    }

}

