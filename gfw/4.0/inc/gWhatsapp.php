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

    function __construct($token = "", $phone_number_id = "") {
        if ($token == ""){
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
     * @param array $botoes Botões do template
     * @return string Status da mensagem
     */
    function SendTemplate($template, $numero_destino, $parametros,$botoes=[]) {

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

        if($botoes){
            $componentButtons = [];
            foreach ($botoes as $value) {
                $componentButtons[] = [
                    'type' => 'text',
                    'text' => $value
                ];
            }

            $payload['template']['components'][]=['type' => 'button','sub_type' => 'url', 'index' => '0', 'parameters'=> $componentButtons];
        }
       
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
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);        
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
    
        // echo "Requisição:<br><pre>";
        // print_r($payload);
        // echo "</pre>";
    
        // echo "Resposta:<br><pre>";
        // print_r(json_decode($resposta, true));
        // echo "</pre>";

        $data = json_decode($resposta, true);
        if ($data === null) {
            return ["ok" => false, "error" => "JSON inválido", "raw" => $resposta, "http" => $http];
        }
    
        // Resposta típica imediata do WhatsApp Cloud API
        if (isset($data['messages'][0]['id'])) {
            return ["ok" => true, "id" => $data['messages'][0]['id'], "http" => $http, "raw" => $data];
        }
    
        // Erro da API
        if (isset($data['error'])) {
            return ["ok" => false, "error" => $data['error'], "http" => $http];
        }
    
        return ["ok" => false, "error" => "Resposta inesperada", "raw" => $data, "http" => $http, "errno" => $errno, "error" => $error];
    }

    /** 
     * Envia uma mensagem de texto (tem que ter recebido uma mensagem do destinatário antes para poder enviar uma mensagem de texto)
     * @param string $numero_destino Número de telefone do destinatário
     * @param string $mensagem Mensagem de texto
     * @return string Status da mensagem
     */
    function SendText($numero_destino, $mensagem){
        $url = "https://graph.facebook.com/v18.0/".$this->phone_number_id."/messages";
    
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
    
        if (curl_errno($ch)) {
            echo "Erro na requisição: " . curl_error($ch);
        } else {
            // echo "Resposta:\n$resposta";
            print_r($resposta);
        }
    
        curl_close($ch);
    }
    
}

/**
* Administração / gerenciar templates
* https://business.facebook.com/wa/manage
* Modelos de mensagens atuais:
#servico

Ola!
Esta mensagem é pra informá-lo que que possui um serviço contratado {{servico}}.

Você poderá receber informações adicionais a qualquer instante.
Mensagem automática. Favor não responder.

---

#info
Temos novas informações sobre seu serviço contratado:

{{dados}}
Mensagem automática. Favor não responder.

---

#aulas_hoje
Informação sobre serviço(s)
Novas informações sobre seu serviço contratado:

{{informacoes}}
Mensagem automática. Favor não responder.

---

#aulas_teleaulas
Aulas remotas de hoje!
Esta mensagem é pra informá-lo que tem aulas agendadas pra hoje:

{{aulas}}

Acesse a aula pelo link: https://webcfc.com.br/aluno

Aprendendo a usar a plataforma: https://www.youtube.com/watch?v=IFnI03v3H9g
Mensagem automática. Favor não responder.

*/