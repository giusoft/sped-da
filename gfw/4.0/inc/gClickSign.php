<?php
ini_set('post_max_size', '60M');
/**
 * Classe responsável pela integração com a ClickSign
 */
date_default_timezone_set("America/Bahia");
 class gClickSign{

    private $token = ['production' => "2cb70227-4820-4263-81fc-833f66fdd453", 'sandbox' => '1d4105aa-ca06-44aa-a6a9-5b1fc61dbfd2'];

    private $hashHmac = "e1cdfae470ea272ffdcedd418a1dfc08";

    private $url = "";

    private $endPoints=['production' => 'https://app.clicksign.com' , 'sandbox' => 'https://sandbox.clicksign.com'];

    public $errors = array();
    public $environment;

    function __construct($environment='sandbox')
    {
	$environment=$environment == "" ? 'production':$environment;
        $this->url =  $this->endPoints[$environment];
        $this->hash =  $this->hashHmac;
        $this->environment = $environment;
    }

    function getHash() {
        return $this->hash;
    }

    /**
     * Metódo responsável por enviar a requisição
     */
    function sendRequest($body,$method,$endPoint,$formData=[]){
        if(substr($endPoint, -1) == '&'){
            $url = $this->url.$endPoint."access_token=".$this->token[$this->environment];
        }else {
            $url = $this->url.$endPoint."?access_token=".$this->token[$this->environment];
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT =>  10,
            CURLOPT_RETURNTRANSFER => true
        ));
        $curl_headers=['Accept: application/json'];
        switch ($method) {
            case 'POST':
                curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
                curl_setopt($curl,CURLOPT_POST,true);
                if(count($formData)){
                    $curl_headers=['Content-Type: multipart/form-data'];
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $formData);
                }
                else{
                    $curl_headers=['Content-Type:  application/json'];
                }
            break;

            case 'PATCH':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $curl_headers=['Content-Type:  application/json'];
            break;
            
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                $curl_headers=['Content-Type:  application/json'];
            break;

            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                $curl_headers=['Content-Type:  application/json'];
            break;
        }
        curl_setopt($curl,CURLOPT_HTTPHEADER,$curl_headers);
        $response = curl_exec($curl);
        return json_decode($response,true);
    }

    /**
     * Requisição para a criação de um documento. Nesta requisição, você envia um arquivo PDF ou Microsoft Word em formato base64.
     * PATCH: /api/v1/documents
     * Metódo: POST
     * {
     *   "document": {
     *       "path": "/Contrato de Prestação de Serviços-123.pdf",
     *       "content_base64": "data:application/pdf;base64,...",
     *       "deadline_at": "2020-01-05T14:30:59-03:00",
     *       "auto_close": true,
     *       "locale": "pt-BR",
     *       "sequence_enabled": false,
     *       "block_after_refusal": true
     *   }
     *   }
     */
    public function createDocument($name,$base64file,$deadLine="",$autoClose=false){

        if($deadLine == "")
            $deadLine = date('c', strtotime("+30 days",strtotime(date('c'))));
        $data=array();
        $data['document']['path'] = "/".$name;
        $data['document']['content_base64'] = $base64file;
        $data['document']['deadline_at'] = $deadLine;
        $data['document']['auto_close'] = $autoClose;
        $data['document']['locale'] = "pt-BR";
        $data['document']['sequence_enabled']= false;
        $data['document']['remind_interval'] = 2;
        $data['document']['block_after_refusal'] = true;

        $response = $this->sendRequest(json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"POST","/api/v1/documents");

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }
        
        return $response;

    }

    /**
     * Requisição para criação de modelo que poderá ser usado para geração de documentos
     * PATCH /api/v2/templates?access_token={{access_token}}
     * Method: POST
     * 
     */
    public function createModel($name,$curlFile){
        $formData=['template[name]' => $name, "template[content]"=>$curlFile];
        return $this->sendRequest("","POST",'/api/v2/templates',$formData);
    }

    /**
     * Requisição para a criação de um documento através de Modelos. Nessas requisições, você cadastra um modelo para a criação de um documento, enviando apenas um JSON com metadados.
     * PATCH /api/v1/templates/:key/documents
     * Method: POST
     * {
     *   "document": {
     *       "path": "/Modelos/Teste-123.docx",
     *       "template": {
     *       "data": {
     *           "Company Name": "Clicksign Gestão de Documentos S.A.",
     *           "Address": "R. Teodoro Sampaio 2767, 10° andar",
     *           "Phone": "(11) 3145-2570",
     *           "Website": "https://www.clicksign.com"
     *          }
     *        }
     *      }
     *  }
     */
    public function createDocumentFromModel($docName,$modelKey,$macros){
        $data=[];
        $data['document']['path']=$docName;
        $data['document']['template']['data']=$macros;
        $dataSend=str_replace(array("\\"),"",json_encode($data,JSON_UNESCAPED_SLASHES));
        return $this->sendRequest($dataSend,"POST","/api/v1/templates/".$modelKey."/documents");
    }
    /**
     * Requisição para a visualização dos metadados dos documentos na Clicksign. É por meio dessas requisições que você consulta o status, o link de download do documento e os signatários.
     * PATCH /api/v1/documents/:key
     * Method: GET
     */
    public function viewDocument($documentKey){
        return $this->sendRequest("","GET","/api/v1/documents/".$documentKey);
    }
    /**
     * Requisição para a visualização de todos os documento.
     */
    public function viewAllDocument($pageNumber = 1){
        return $this->sendRequest("","GET","/api/v1/documents?page=".$pageNumber."&");
    }
    /**
     * Requisição para alterar as configurações de data-limite para assinatura, idioma e finalização automática do documento.
     */
    public function configDocument($documentKey,$deadLine="",$autoClose=false){

        if($deadLine == "")
            $deadLine = date('c', strtotime("+30 days",strtotime(date('c'))));
        $data=array();
        $data['deadline_at'] = $deadLine;
        $data['auto_close'] = $autoClose;
        $data['locale'] = "pt-BR";
        $data['sequence_enabled']= false;
        $data['remind_interval'] = 2;
        $data['block_after_refusal'] = true;
        
        $response = $this->sendRequest(json_encode($data),"PATCH","/api/v1/documents/".$documentKey);

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }
        
        return $response;
    }

    /**
     *  Requisição para finalização de um documento que já possui ao menos uma assinatura.
     */
    public function finalizeDocument($documentKey){
        $response = $this->sendRequest(json_encode($data),"PATCH","/api/v1/documents/".$documentKey."/finish");

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }

        return $response;
    }

    /**
     * Requisição para cancelamento de um documento.
     * PATCH: /api/v1/documents/CHAVE_DO_DOCUMENTO/cancel
     */
    public function cancelDocument($documentKey){
        return $this->sendRequest("","PATCH","/api/v1/documents/".$documentKey."/cancel");
    }

    /**
     * Requisição para duplicar um documento que já foi finalizado na Clicksign. O resultado é um novo documento com base no arquivo assinado anteriormente.
     */
    public function duplicateDocument($documentKey){
        $response = $this->sendRequest("","POST","/api/v1/documents/".$documentKey."/duplicate");

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }

        return $response;
    }

    /**
     * Requisição para excluir um documento na Clicksign. O resultado é a remoção do documento na conta.
     */
    public function deleteDocument($documentKey){
        $response = $this->sendRequest('',"DELETE","/api/v1/documents/".$documentKey);

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }

        return $response;
    }

    /**
     *  Requisição para criação de um signatário para assinatura de um documento.
     * PATCH: /api/v1/signers
     * Metódo: POST
     * {
     *   "signer": {
     *       "email": "fulano@example.com",
     *       "phone_number": "11999999999",
     *       "auths": [
     *       "email"
     *       ],
     *       "name": "Marcos Zumba",
     *       "documentation": "123.321.123-40",
     *       "birthday": "1983-03-31",
     *       "has_documentation": true,
     *       "selfie_enabled": false,
     *       "handwritten_enabled": false,
     *       "location_required_enabled": false,
     *       "official_document_enabled": false,
     *       "liveness_enabled": false,
     *       "facial_biometrics_enabled": false
     *      }
     *   }
     */
    public function createSignatory($name,$email,$phone,$documentation,$birthday,$auths=['email']){
        $data = array();
        $data['signer']['email']                        = $email;
        $data['signer']['phone_number']                 = $phone;
        $data['signer']['auths']                        = $auths;
        $data['signer']['name']                         = $name;
        $data['signer']['documentation']                = $this->formatCnpjCpf($documentation);
        $data['signer']['birthday']                     = $birthday;
        $data['signer']['has_documentation']            = true;
        $data['signer']['selfie_enabled']               = false;
        $data['signer']['handwritten_enabled']          = false;
        $data['signer']['location_required_enabled']    = false;
        $data['signer']['official_document_enabled']    = false;
        $data['signer']['liveness_enabled']             = false;
        $data['signer']['facial_biometrics_enabled']    = false;
        return $this->sendRequest(json_encode($data),"POST","/api/v1/signers");

    }

    /**
     * Requisição para visualização dos atributos do signatário. Através dessa requisição você consulta os dados atribuídos a cada signatário como, nome, email, CPF, data de nascimento e tipo de autenticação.
     */
    public function viewSignatory($signatoryKey){
        return $this->sendRequest("","GET","/api/v1/signers/".$signatoryKey);
    }

    /**
     * Requisição para adicionar um signatário a um documento.
     * PATCH: /api/v1/lists
     * Metódo: POST
     * {
     *   "list": {
     *       "document_key": "27b02527-a576-46ee-b01c-bb4e694036c4",
     *       "signer_key": "79301388-9567-4320-90ce-9e6f60e70d28",
     *       "sign_as": "sign",
     *       "refusable": true,
     *       "group": 1,
     *       "message": "Prezado João,\nPor favor assine o documento.\n\nQualquer dúvida estou à disposição.\n\nAtenciosamente,\nGuilherme Alvez"
     *   }
     * }
     */
    public function addSignatoryToDocument($documentKey,$signerKey,$signAs,$message){
        $data=array();
        $data['list']['document_key']   = $documentKey;
        $data['list']['signer_key']     = $signerKey;
        $data['list']['sign_as']        = $signAs;
        $data['list']['refusable']      = true;
        //$data['list']['group']          = 1;
        $data['list']['message']        = $message;

        return $this->sendRequest(json_encode($data),"POST","/api/v1/lists");
    }

    /**
     * Requisição para remover um signatário do documento.
     */
    public function deleteSignatoryFromDocument($listKey){
        
        $response = $this->sendRequest('',"DELETE","/api/v1/lists/".$listKey);

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }

        return $response;
    }

    /**
     * Requisição para excluir um signatário definitivamente.
     */
    public function deleteSignatory($signatoryKey){
        $response = $this->sendRequest('',"DELETE","/api/v1/signers/".$signatoryKey);

        if ($response['errors']){
            foreach($response['errors'] as $errors) {
                $this->errors[] = $errors;
            }
        }

        return $response;
    }

    /**
     * Requisição para enviar mensagem por e-mail para um signatário solicitando assinatura de um documento ou de um lote
     * PATCH: /api/v1/notifications
     * Metódo: POST
     * {
     *   "request_signature_key": "0d5a9615-2bb8-3a23-6584-33ff436bb990",
     *   "message": "Prezado João,\nPor favor assine o documento.\n\nQualquer dúvida estou à disposição.\n\nAtenciosamente,\nGuilherme Alvez",
     *   "url": "https://www.example.com/abc"
     * }
     */
    public function requestSignByEmail($documentKey,$message){
        $message = $message=="" ? "Segue documento para assinatura" : $message;
        $data=["request_signature_key" => $documentKey,"message" => $message];
        return $this->sendRequest(json_encode($data),"POST","/api/v1/notifications");
    }

    /**
     * Retorna a extenção baseado no mime type passado
     */
    public function getExtension($mimeType){
        //echo $mimeType;exit;
        $mimeTypes = array(
            'txt' => 'text/plain',
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            
        );
        $ext = array_search($mimeType,$mimeTypes);
        if($ext)
            return $ext;
        else
            return false;
    }

    public function formatCnpjCpf($value)
    {
      $CPF_LENGTH = 11;
      $cnpj_cpf = preg_replace("/\D/", '', $value);
      
      if (strlen($cnpj_cpf) === $CPF_LENGTH) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
      } 
      
      return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }
 }
?>
