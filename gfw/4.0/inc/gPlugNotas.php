<?php

class gNotas
{


  private $baseUrl;
  private $key;
  private $homologation;

  function __construct($production = false, $homologation = true)
  {

    $this->homologation = $homologation;
    $this->production = $production;

    if ($production) {
      $this->baseUrl = 'https://api.plugnotas.com.br';
      $this->key = "2da392a6-79d2-4304-a8b7-959572c7e44d";
    } else {
      $this->baseUrl = 'https://api.sandbox.plugnotas.com.br';
      $this->key = "2da392a6-79d2-4304-a8b7-959572c7e44d";
    }
  }

  public function connecta($method, $endpoint, $header = NULL, $body = NULL)
  {
    $url = $this->baseUrl . $endpoint;

    $header[] = 'accept: application/json';
    $header[] = 'Content-Type: application/json';
    $header[] = 'x-api-key: ' . $this->key;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    if ($method == 'POST') {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      // var_dump($body);
    }

    $result = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($result);


    echo "<pre>___________________URL_______________________</br></br>";
    var_dump($url) . "</br>";
    echo "__________________TOKEN______________________</br></br>";
    var_dump($this->key);
    echo "_________________REQUEST_____________________</br></br>";
    var_dump($body);
    echo "__________________RESULT_____________________</br></br>";
    var_dump($result);


    return ($result);
  }

  /**
   * Lista todos os certificados vinculados ao TOKEN de sua organização.
   */
  public function consultaCertificados()
  {
    $header = [];
    return  $this->connecta('GET', '/certificado', $header);
  }

  /**
   * Adiciona uma empresa.
   */
  public function cadastraEmpresa($body)
  {
    if (!$this->homologation) {
      $body['nfse']['config']['producao'] = true;
    }


    $body = json_encode($body);
    $header = [];
    return  $this->connecta('POST', '/empresa', $header, $body);
  }

  /**
   * Lista todos os Empresa vinculados ao TOKEN de sua organização.
   */
  public function consultaEmpresa($cnpj)
  {
    return  $this->connecta('GET', '/empresa/' . $cnpj);
  }

  /**
   * Lista todos os Empresa vinculados ao TOKEN de sua organização.
   */
  public function cadastraNota($body)
  {
    $body = json_encode($body);
    $header = [];
    return  $this->connecta('POST', '/empresa', $header, $body);
  }

  /**
   * Consulta Nota.
   */
  public function consultaNota($id)
  {
    return  $this->connecta('GET', '/nfse/' . $id);
  }

  /**
   * Consulta Nota.
   */
  public function consultaNotaPDF($id)
  {
    return  $this->connecta('GET', '/nfse/pdf/' . $id);
  }

  /**
   * Consulta Nota.
   */
  public function consultaNotaXML($id)
  {
    return  $this->connecta('GET', '/nfse/xml/' . $id);
  }
}

$notas = new gNotas(true);
$empresa = [
  "cpfCnpj" => "06472331502",
  "inscricaoMunicipal" => "1233211233",
  "inscricaoEstadual" => "1233211233",
  "razaoSocial" => "Tecnospeed S/A",
  "nomeFantasia" => "Tecnospeed",
  "certificado" => "5af59d271f6e8f409178fbf3",
  "simplesNacional" => true,
  "regimeTributario" => 1,
  "incentivoFiscal" => true,
  "incentivadorCultural" => true,
  "regimeTributarioEspecial" => 5,
  "endereco" => [
    "tipoLogradouro" => "TESTE",
    "logradouro" => "TETES",
    "numero" => "882",
    "complemento" => "98 andar",
    "tipoBairro" => "Zona",
    "bairro" => "Zona 01",
    "codigoPais" => "321",
    "descricaoPais" => "Belgium",
    "codigoCidade" => "4512510",
    "descricaoCidade" => "Bruxelas",
    "estado" => "BA",
    "cep" => "84521365"
  ],
  "telefone" => [
    "ddd" => "44",
    "numero" => "3037-9500"
  ],
  "email" => "empresa@plugnotas.com.br",
  "nfse" => [
    "ativo" => true,
    "tipoContrato" => 0,
    "config" => [
      "producao" => false,
      "rps" => [
        "serie" => "RPS",
        "numero" => 1,
        "lote" => 1
      ],
      "prefeitura" => [
        "login" => "teste",
        "senha" => "teste123"
      ],
      "email" => [
        "envio" => true
      ]
    ]
  ],
  "nfe" => [
    "ativo" => true,
    "tipoContrato" => 0,
    "config" => [
      "producao" => false,
      "impressaoFcp" => true,
      "impressaoPartilha" => "true",
      "serie" => 1,
      "numero" => 1
    ]
  ]
];
// var_dump($empresa['nfse']['config']);
$notas->cadastraEmpresa($empresa);
        
/* {
     "cpfCnpj": "08187168000160",
     "inscricaoMunicipal": "8214100099",
     "inscricaoEstadual": "1234567850",
     "razaoSocial": "Tecnospeed S/A",
     "nomeFantasia": "Tecnospeed",
     "certificado": "5af59d271f6e8f409178fbf3",
     "simplesNacional": true,
     "regimeTributario": 1,
     "incentivoFiscal": true,
     "incentivadorCultural": true,
     "regimeTributarioEspecial": 5,
     "endereco": {
       "tipoLogradouro": "Avenida",
       "logradouro": "Duque de Caxias",
       "numero": "882",
       "complemento": "17 andar",
       "tipoBairro": "Zona",
       "bairro": "Zona 01",
       "codigoPais": "1058",
       "descricaoPais": "Brasil",
       "codigoCidade": "4115200",
       "descricaoCidade": "Maringá",
       "estado": "PR",
       "cep": "87020-025"
     },
     "telefone": {
       "ddd": "44",
       "numero": "3037-9500"
     },
     "email": "empresa@plugnotas.com.br",
     "nfse": {
       "ativo": true,
       "tipoContrato": 0,
       "config": {
         "producao": true,
         "rps": {
           "serie": "RPS",
           "numero": 1,
           "lote": 1
         },
         "prefeitura": {
           "login": "teste",
           "senha": "teste123"
         },
         "email": {
           "envio": true
         }
       }
     },
     "nfe": {
       "ativo": true,
       "tipoContrato": 0,
       "config": {
         "producao": true,
         "impressaoFcp": true,
         "impressaoPartilha": "true",
         "serie": 1,
         "numero": 1
       }
     }
   }
*/