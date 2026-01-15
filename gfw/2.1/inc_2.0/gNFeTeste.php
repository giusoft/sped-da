<?
class NotaFiscal extends gNFe {
	private $rsItem=""; // record set contendo todos itens
	private $rsNota=""; // record set contendo todas notas
	private $rsEntidade; // record set contendo dados do emitente, incluindo endereço

	/**
	 * @author
	 * @version	1.0
	 * @return
	 */
	function obtemEntidade($id) {
		$id=2593;// TODO: retirar este teste
		$sql="SELECT
				j.razao_social,
				e.endereco AS logradouro,
				e.numero,
				e.complemento,
				e.bairro,
				c.ibge AS cod_cidade,
				c.descricao as cidade,
				c.sigla As sigla_u,
				'IE' AS IE
				FROM geral_pessoas p
				INNER JOIN geral_pessoas_juridicas j ON p.id=j.id_geral_pessoas
				LEFT JOIN geral_pessoas_enderecos e ON (p.id=e.id_geral_pessoas AND e.aplicacao='faturamento')
				LEFT JOIN geral_cidades c ON e.id_geral_cidades=c.id
				WHERE p.id='$id' ";
		$rs=gQuery($sql);
		$this->rsEntidade=$rs->fields;
		return $rs->fields;
	}

	/**
	 * @author
	 * @version	1.0
	 * @return
	 */
	function obtemEmitente() {
		return ($this->obtemEntidade($_POST['idEntidade']));
	}

	/**
	 * @author
	 * @version	1.0
	 * @return
	 */
	function obtemDestinatario() {
		return ($this->obtemEntidade($_POST['idDestinatario']));

	}

	/** Obtem todos items de uma nota
	 * @author	giuliano e mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return array
	 */
	function obtemItens($idNf) {
	// Seleciona os itens da nota
	// OBS.: criado campo "codigo" na tabela fin_natureza_operacao
		$sql="SELECT
				fni.id_fin_notas,
				i.id AS itemCod,
				i.nome,
				fni.quantidade,
				fni.valor_unitario,
				fni.item As itemOrdem,
				fni.unidade,
				fni.desconto,
				fni.iss,
				fni.icms,
				'ipi' AS ipi,
				'icms' AS icms,
				'cofins' AS cofins,
				nop.codigo AS cfop
				FROM fin_notas_emitidas_itens fni
				INNER JOIN com_produtos i ON i.id=fni.id_com_produtos
				LEFT JOIN geral_unidades gu ON fni.unidade=gu.descricao
				LEFT JOIN fin_titulos tit ON fni.id_fin_titulos=tit.id
				LEFT JOIN fin_notas_emitidas fn ON fni.id_fin_notas=fn.id
				LEFT JOIN fin_natureza_operacao nop ON nop.id=fn.id_fin_natureza_operacao
				WHERE fni.id_fin_notas='$idNf'
				ORDER BY fni.item ASC";
		//echo $sql;
		$rs=gQuery($sql);

		$this->rsItem=$rs;
	}

	/** Obtem um (próximo) item de uma nota. A cada chamada a este método, será retornado o item subsequente, sendo o 1º na 1ª chamada
	 * @author	giuliano e mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return array
	 */
	function obtemItem() {
		$item=false;
		if (!$this->rsItem->EOF) {
			$item="";

			$item['idNf']=$this->rsItem->fields['id_fin_notas']; // id da nota, apenas para controle na depuraçao...
			//
			$item['cProd']=$this->rsItem->fields['itemCod']; // cod. do produto (id usado no BD)
			$item['cEAN']=""; // GTIN (Global Trade Item Number) do produto, antigo codigo EAN ou codigo de barras. Nao obrigatorio cason tenha
			$item['xProd']=$this->rsItem->fields['nome']; // descricao do produto ou sevico
			$item['NCM']=""; // codigo NCM. Nao obrigatorio.
			$item['EXTIPI']=""; // EXTIPI. Nao obrigatorio.
			$item['genero']=""; // genero do produto ou servico. Nao obrigatorio.
			$item['CFOP']=$this->rsItem->fields['cfop']; // natureza de operacao
			$item['uCom']=$this->rsItem->fields['unidade']; // unidade comercial
			$item['qCom']=$this->rsItem->fields['quantidade']; // quantidade comercial
			$item['vUnCom']=$this->rsItem->fields['valor_unitario']; // valor unitario de comercializacao
			$item['vProd']=$this->rsItem->fields['valor_unitario']; //valor total bruto dos produtos ou servicos
			$item['cEANTrib']=""; // GTIN (Global Trade Item Number) da unidade tributavel, antigo codigo EAN ou codigo de barras. Nao obrigatorio cason tenha
			$item['uTrib']=$this->rsItem->fields['unidade'];
			$item['vUnTrib']=$this->rsItem->fields['valor_unitario']; // valor unitario de tributacao
			$item['vFrete']=""; // valor total do frete. Nao obrigatorio
			$item['vSeg']=""; // valor total do seguro. Nao obrigatorio
			$item['vDesc']=$this->rsItem->fields['desconto']; // valor do desconto. Nao obrigatorio
			// informacoes necessarias para outras partes do arquivo
			$item['itemOrdem']=$this->rsItem->fields['itemOrdem']; // ordem do item na nota
			// impostos
			$item['iss']=$this->rsItem->fields['iss'];
			$item['icms']=$this->rsItem->fields['icms'];
			$item['cofins']=$this->rsItem->fields['cofins']; // TODO: nao tem campo na tabela no BD, criar...
			$item['pis']=$this->rsItem->fields['pis']; // TODO: nao tem campo na tabela no BD, criar...
			$item['ipi']=$this->rsItem->fields['ipi']; // TODO: nao tem campo na tabela no BD, criar...

			$this->rsItem->MoveNext();
		}
		return($item);
	}


	/** Gera chave. Sera estendido na classe filha
	 * @author	mateus
	 * @version	1.0 29-10-2009 09:47
	 */
	protected function obtemChave($idNf) {
	// consulta
		$sql="SELECT
				gpj.cnpj,
				nf.id_geral_pessoas as idCliente,
				ge.ibge AS cUF,
				nf.id AS idNf
				FROM fin_notas_emitidas nf
				LEFT JOIN geral_pessoas_juridicas gpj ON gpj.id_geral_pessoas=nf.id_geral_pessoas_filial
				LEFT JOIN geral_pessoas gp ON gp.id=nf.id_geral_pessoas
				LEFT JOIN geral_pessoas_enderecos gpe ON
				(
					nf.id_geral_pessoas=gpe.id_geral_pessoas
					AND ( gpe.aplicacao='Faturamento' or gpe.aplicacao is null)
				)
				LEFT JOIN geral_estados ge ON gpe.id_geral_estados=ge.id
				WHERE nf.id=13892";

		$rs=gQuery($sql);
		return $rs->fields;
	}

	/** Obtem notas de um determinado intervalo
	 * @author	mateus
	 * @version	1.0 28-10-2009 09:47
	 * @return
	 */
	public function obtemNotas() {

		$sql="SELECT
				gpj.razao_social AS razaoSocial,
				nf.id_geral_pessoas as idCliente,
				gpe.endereco,
				gc.descricao AS cidade,
				gc.ibge AS cMunFG,
				ge.sigla AS estado,
				ge.ibge AS cUF,
				gpa.nome AS pais,
				nf.id AS idNf,
				nf.*
				FROM fin_notas_emitidas nf
				LEFT JOIN geral_pessoas_juridicas gpj ON gpj.id_geral_pessoas=nf.id_geral_pessoas
				LEFT JOIN geral_pessoas gp ON gp.id=nf.id_geral_pessoas
				LEFT JOIN geral_pessoas_enderecos gpe ON
				(
					nf.id_geral_pessoas=gpe.id_geral_pessoas
					AND ( gpe.aplicacao='Faturamento' or gpe.aplicacao is null)
				)
				LEFT JOIN geral_cidades gc ON gpe.id_geral_cidades=gc.id
				LEFT JOIN geral_estados ge ON gpe.id_geral_estados=ge.id
				LEFT JOIN geral_paises gpa ON gpe.id_geral_paises=gpa.id
				LEFT JOIN geral_pessoas_juridicas gpj2 ON gpj2.id_geral_pessoas=nf.id_geral_pessoas_filial
				LEFT JOIN fin_natureza_operacao nop ON nop.id=nf.id_fin_natureza_operacao
				LEFT JOIN fin_formas_pagamento prazo ON prazo.id=nf.id_fin_formas_pagamento
				LEFT JOIN fin_modalidade mo ON mo.id=nf.id_fin_modalidade
				LEFT JOIN fin_portador po ON po.id=nf.id_fin_portador
				WHERE nf.cancelada=0
				AND nf.data_emissao IS NOT NULL
				AND nf.id in (201,202,205)
				ORDER BY nf.id ASC";
		//AND (nf.data_emissao>='".gDBDate($periodo1)." 00:00:00.0' AND nf.data_emissao<='".gDBDate($periodo2)." 00:00:00.0')
		$rs=gQuery($sql);
		$this->rsNota=$rs;
	}

	/** Obtem uma (proxima) nota. A cada chamada a este método, será retornado a nota subsequente, sendo o 1º na 1ª chamada
	 * @author	mateus
	 * @version	1.0 28-10-2009 09:47
	 * @return
	 */
	public function obtemNota() {
		$nota=false;
		if (!$this->rsNota->EOF) {
			$nota="";
			$nota['id']=$this->rsNota->fields['idNf'];
			//echo 'notaid='.$nota['id'].'<br>';// Debug
			$this->rsNota->MoveNext();
		}
		return($nota);
	}

	/** Obtem lote incrementando o ultimo ID do lote encontrado no BD
	 * @author	mateus
	 * @version	1.0 04-11-2009
	 * @return
	 */
	function obtemLote() {
		$sql="SELECT TOP 1 id FROM fin_notas_lote ORDER BY id DESC";
		/*
		$rs=gQuery($sql);
		$lote=$rs->fields['id'];
		$proximoLote=$lote+1;
		*/
		$proximoLote=1; // TODO: REMOVER .... TESTE

		return $proximoLote;
	}
}
?>