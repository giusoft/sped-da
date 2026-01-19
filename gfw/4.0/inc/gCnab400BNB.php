<?php
class gCnab400
{
    public $bancoCodigo     = "004"; // BNB
    public $bancoNome       = "B. DO NORDESTE";
    public $empresaNome     = "";
    public $usuarioCodigo   = "";
    public $agencia         = "";
    public $conta           = "";
    public $contaDigito     = "";

    public $header          = [];
    public $transacoes      = [];
    public $trailer         = "";

    public $txt             = "";

    public function __construct($params=[])
    {
        $this->agencia      = $params['agencia'];
        $this->conta        = $params['conta'];
        $this->contaDigito  = $params['contaDigito'];
        $this->empresaNome  = $params['empresaNome'];
        
    }

    public function header()
    {
        $header = [];
        $header[] = $this->pad("0",1,"N"); // Código do Registro. = 0
        $header[] = $this->pad("1",1,"N"); //Identificação do Arquivo Remessa. = 1
        $header[] = $this->pad("REMESSA",7,"A"); //Identificação por Extenso. = REMESSA
        $header[] = $this->pad("01",2,"N"); // Código de Serviço = 01
        $header[] = $this->pad("COBRANCA",15,"A"); // Literal de Serviços. = COBRANCA
        $header[] = $this->pad($this->agencia,4,"N"); // Agência Cedente. = Cód. da Agência da empresa
        $header[] = $this->pad("0",2,"N"); //Filler = 0
        $header[] = $this->pad($this->conta,7,"N"); // Conta do Cliente = Conta do cliente
        $header[] = $this->pad($this->contaDigito,1,"N"); //Dígito da Conta = Dígito da Conta
        $header[] = $this->pad("",6,"A"); //Filler = Brancos
        $header[] = $this->pad($this->empresaNome,30,"A"); //Nome do Cliente = Nome da empresa
        $header[] = $this->pad($this->bancoCodigo,3,"N"); //Número do Banco = 004
        $header[] = $this->pad($this->bancoNome,15,"A"); //Nome do Banco = B. DO NORDESTE
        $header[] = $this->pad(date("dmy"),6,"D"); // Data de Gravação do Arquivo. = Dia, Mês e Ano (formato "DDMMAA").
        $header[] = $this->pad($this->usuarioCodigo,3,"A"); // Código do Usuário. = Código da caixa postal no Sistema EDI (Fornecido pelo Banco). Se transmissão pela Nexxera Informar Brancos.
        $header[] = $this->pad("",291,"A"); // Filler = Brancos
        $header[] = $this->pad(1,6,"N"); // Seqüencial do Registro. = 000001 Incrementar os próximos registro em 1 até o trailer.
        $this->header = $header;
        // echo "<pre>";
        // var_dump($header);exit;
    }

    // $dados=[
    //     'taxaMulta' => 0,
    //     'numeroControle' => 0,
    //      'dataDescontoAte'=> 0
    // ];
    public function addTransacoes($dados)
    {
        $transacao=[];
        $transacao[] = $this->pad('1',1,"N"); // Código do registro = 1
        $transacao[] = $this->pad(' ',16,"A"); // Filler = Brancos
        $transacao[] = $this->pad($this->agencia,4,"N"); // Agência Cedente. = Cód. da Agência da empresa
        $transacao[] = $this->pad(0,2,"N"); // FIller = 0
        $transacao[] = $this->pad($this->conta,7,"N"); // Conta do Cliente = Conta do cliente
        $transacao[] = $this->pad($this->contaDigito,1,"N"); //Dígito da Conta = Dígito da Conta
        $transacao[] = $this->pad($dados['taxaMulta'],2,"N"); // Percentual Multa por atraso
        $transacao[] = $this->pad('',4,"A"); // Filler = Brancos
        $transacao[] = $this->pad($dados['numeroControle'],25,"A"); // Número Controle = Nº de Controle do Título do Cliente. (Controle da empresa)
        $transacao[] = $this->pad($this->apenasNumeros($dados['numeroControle']),7,"N"); //Nosso Número = Nosso Número se o boleto for emitido pelo Cliente. Caso contrário zeros.
        $transacao[] = $this->pad(modulo_11($this->apenasNumeros($dados['numeroControle'])),1,"N"); // DV do Nosso Número calculado pelo módulo 11. (vide nota n. 1).
        $transacao[] = $this->pad(0,10,"N"); // Número do Contrato para cobrança caucionada/vinculada. Preencher com zeros para cobrança simples.
        $transacao[] = $this->pad($dados['dataDescontoAte'],6,"A"); // Data do Segundo Desconto no formato “ddmmaa” se houver. Caso contrário, preencher com zeros. (Caso preenchido deve-se informar o item seguinte).

    }

    public function trailer()
    {

    }
    
    public function geraArquivo(){
        $this->header();
        $txt = implode("",$this->header);

        return $txt;
    }

    public function pad ($valor,$tamanho,$tipo)
    {
        $sai='';
        switch ($tipo) {
            case 'A':
                $stringLimpa = strtoupper($this->tiraAcentos($valor));
                $sai = substr($stringLimpa,0,$tamanho);
                $sai = str_pad($sai,$tamanho,chr(32),STR_PAD_RIGHT);
            break;
            
            case 'D':
                $stringLimpa = strtoupper($this->apenasNumeros($valor));
                $sai = substr($stringLimpa,0,$tamanho);
                $sai = str_pad($sai,$tamanho,0,STR_PAD_LEFT);
            break;

            case 'N':
                $stringLimpa = strtoupper($this->apenasNumeros($valor));
                $sai = substr($stringLimpa,0,$tamanho);
                $sai = str_pad($sai,$tamanho,0,STR_PAD_LEFT);
            break;
        }
        return $sai;
    }

    public static function tiraAcentos($string)
    {
        $normalizeChars = array(
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ä' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'Eth',
            'Ñ' => 'N', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ŕ' => 'R',

            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ä' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'eth',
            'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ŕ' => 'r', 'ÿ' => 'y',

            'ß' => 'sz', 'þ' => 'thorn', 'º' => '', 'ª' => '', '°' => '',
        );

        //return preg_replace('/[^0-9a-zA-Z !+=*\-,.;:%@]/', '', strtr($string, $normalizeChars));
        return preg_replace('/[^0-9a-zA-Z ]/', '', strtr($string, $normalizeChars));
    }

    public static function apenasNumeros($string)
    {
        return preg_replace('/[^[:digit:]]/', '', $string);
    }

    public function modulo_11($num, $base=9, $r=0)
    {
        $soma = 0;
        $fator = 2;

        /* Separacao dos numeros */
        for ($i = strlen($num); $i > 0; $i--) {
            // pega cada numero isoladamente
            $numeros[$i] = substr($num,$i-1,1);
            // Efetua multiplicacao do numero pelo falor
            $parcial[$i] = $numeros[$i] * $fator;
            // Soma dos digitos
            $soma += $parcial[$i];
            if ($fator == $base) {
                // restaura fator de multiplicacao para 2 
                $fator = 1;
            }
            $fator++;
        }

        /* Calculo do modulo 11 */
        if ($r == 0) {
            $soma *= 10;
            $digito = $soma % 11;
            if ($digito == 10) {
                $digito = 0;
            }
            return $digito;
        } elseif ($r == 1){
            $resto = $soma % 11;
            return $resto;
        }
    }
    
}

$params=[
    'agencia'       => '0092',
    'conta'         => '14083', // 140839
    'contaDigito'   => '9',
    'empresaNome'   => 'Centro de Formação de Condutores Camila Ltda Me'

];
$c = new gCnab400($params);
$dados=[
        'taxaMulta' => 0,
        'numeroControle' => 0,
         'dataDescontoAte'=> 0
    ];
$c->addTransacoes($dados);
$txt = $c->geraArquivo();
echo "<textarea>$txt</textarea>";
exit;
?>