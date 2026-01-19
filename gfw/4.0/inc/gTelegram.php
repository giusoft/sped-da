<?

class Telegram
{
     public $token;
     public $chatIds;
     public $mensagem;


     public function __construct($token)
     {
          if (function_exists('gVar')) {
               if (gVar("telegram.token")) {
                    $token = $token ?: gVar("telegram.token");
               }
          }

          $this->token = $token ? : "5227819307:AAG7Wi2_Yd-pN5pvvRYPzzratZ9C84aW8Yo";
     }


     public function carregarConfiguracoesRequisicao($chatId)
     {
          return array(
               'http' => array(
                    'method'  => 'POST',
                    'content' => json_encode(
                         array(
                              'chat_id' => $chatId,
                              'text'    => $this->mensagem,
                              'parse_mode' => 'html',
                              'disable_web_page_preview' => 'true'
                         )
                    ),
                    'header'  =>  'Content-Type: application/json\r\n' 
                                   . 'Accept: application/json\r\n'
               )
          );
     }


     public function enviar($chatId, $mensagem)
     {
          if (!is_array($chatId)) {
               $chatId= array($chatId);
          }

          $this->chatIds = $chatId;
          $this->mensagem = $mensagem;
          foreach ($this->chatIds as $chatId) {
               $context  = stream_context_create($this->carregarConfiguracoesRequisicao($chatId));
               $enviarMensagem = file_get_contents('https://api.telegram.org/bot'
                    .$this->token.'/sendMessage', false, $context
               );
          }
     }
}
