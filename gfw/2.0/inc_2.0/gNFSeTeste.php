<?
include_once "gNFSe.php";
class NotaFiscal extends gNFSe {
	private $rsItem=""; // record set contendo todos itens
	private $rsNota=""; // record set contendo todas notas
	protected $rsRps=""; // record set contendo todos Rps

	/**
	 * @author
	 * @version	1.0
	 * @return
	 */
	function obtemEntidade($id) {
		$sql="SELECT
				j.razao_social,
				j.cnpj,
				j.insc_estadual,
				j.insc_municipal,
				e.endereco AS logradouro,
				e.numero,
				e.complemento,
				e.bairro,
				c.ibge AS cod_cidade,
				c.descricao as cidade,
				c.sigla As sigla_cidade,
				'IE' AS IE
				FROM geral_pessoas p
				INNER JOIN geral_pessoas_juridicas j ON p.id=j.id_geral_pessoas
				LEFT JOIN geral_pessoas_enderecos e ON (p.id=e.id_geral_pessoas AND e.aplicacao='faturamento')
				LEFT JOIN geral_cidades c ON e.id_geral_cidades=c.id
				WHERE p.id='$id' ";
		$rs=gQuery($sql);
		return $rs->fields;
	}

	/** Obtem todos items de uma nota
	 * @author	giuliano e mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return array
	 */
	function obtemItens($idNf) {
		/*
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
		 'itemListaServico' AS itemListaServico,
		 'issRetido' AS issRetido,
		 nop.codigo AS cfop
		 FROM fin_notas_emitidas_itens fni
		 INNER JOIN com_produtos i ON i.id=fni.id_com_produtos
		 LEFT JOIN geral_unidades gu ON fni.unidade=gu.descricao
		 LEFT JOIN fin_titulos tit ON fni.id_fin_titulos=tit.id
		 LEFT JOIN fin_notas_emitidas fn ON fni.id_fin_notas=fn.id
		 LEFT JOIN fin_natureza_operacao nop ON nop.id=fn.id_fin_natureza_operacao
		 WHERE fni.id_fin_notas='$idNf'
		 ORDER BY fni.item ASC";
		 //echo $sql."<br />";// exit;
		 */

		// Consulta que agrupa serviços iguais (consolida informações)
		// TODO: posteriormente desenvolver verificação do município aonde o serviço foi prestado. Para cada município deve ser emitido um novo Rps
		$sql="SELECT
				i.id AS itemCod,
				i.nome,
				fni.id_fin_notas,
				fni.iss,
				nop.codigo AS cfop,
				'ipi' AS ipi,
				'cofins' AS cofins,
				'itemListaServico' AS itemListaServico,
				'issRetido' AS issRetido,
				SUM(fni.icms) AS icms,
				SUM(fni.iss) AS iss,
				SUM(fni.quantidade) As quantidade,
				SUM(fni.valor_unitario) AS valor_unitario
				FROM fin_notas_emitidas_itens fni
				INNER JOIN com_produtos i ON i.id=fni.id_com_produtos
				LEFT JOIN geral_unidades gu ON fni.unidade=gu.descricao
				LEFT JOIN fin_titulos tit ON fni.id_fin_titulos=tit.id
				LEFT JOIN fin_notas_emitidas fn ON fni.id_fin_notas=fn.id
				LEFT JOIN fin_natureza_operacao nop ON nop.id=fn.id_fin_natureza_operacao
				WHERE fni.id_fin_notas IN ('$idNf')
				GROUP BY i.id,i.nome,fni.id_fin_notas,fni.iss,fni.icms,nop.codigo";

		$rs=gQuery($sql);
		$this->rsItem=$rs;
	}

	/** Obtem todos Rps's prontos
	 * @author	mateus
	 * @version	1.0 02-02-2010
	 * @return
	 */
	function obtemRpss($idNf) {
		// Consulta que agrupa serviços iguais (consolida informações)
		// TODO: posteriormente desenvolver verificação do município aonde o serviço foi prestado. Para cada município deve ser emitido um novo Rps
		$sql="SELECT
				i.id AS itemCod,
				i.nome,
				fni.id_fin_notas,
				fni.iss,
				nop.codigo AS cfop,
				'0.0' AS pis,				
				'0.0' AS ipi,
				'0.0' AS cofins,
				'9' AS itemListaServico,
				'2' AS issRetido,
				SUM(fni.icms) AS icms,
				SUM(fni.iss) AS iss,
				SUM(fni.quantidade) As quantidade,
				SUM(fni.valor_unitario) AS valor_unitario,
				
				nf.data_emissao,
				nf.serie,
				nf.id_geral_pessoas_filial,
				gpj.razao_social AS razaoSocial,
				nf.id_geral_pessoas AS idCliente,
				gpe.endereco,
				gc.descricao AS cidade,
				gc.ibge AS municipioIbge,
				ge.sigla AS estado,
				ge.ibge AS cUF,
				gpa.nome AS pais,
				nf.id AS idNf
				
				FROM fin_notas_emitidas_itens fni
				INNER JOIN com_produtos i ON i.id=fni.id_com_produtos
				LEFT JOIN geral_unidades gu ON fni.unidade=gu.descricao
				LEFT JOIN fin_titulos tit ON fni.id_fin_titulos=tit.id
				LEFT JOIN fin_notas_emitidas fn ON fni.id_fin_notas=fn.id
				LEFT JOIN fin_natureza_operacao nop ON nop.id=fn.id_fin_natureza_operacao

				
				LEFT JOIN fin_notas_emitidas nf ON nf.id=fni.id_fin_notas
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
				LEFT JOIN fin_natureza_operacao nnop ON nnop.id=nf.id_fin_natureza_operacao
				LEFT JOIN fin_formas_pagamento prazo ON prazo.id=nf.id_fin_formas_pagamento
				LEFT JOIN fin_modalidade mo ON mo.id=nf.id_fin_modalidade
				LEFT JOIN fin_portador po ON po.id=nf.id_fin_portador
				
				WHERE nf.id in ('201')
				GROUP BY i.id,i.nome,fni.id_fin_notas,fni.iss,fni.icms,nop.codigo,
				nf.data_emissao,nf.serie,nf.id_geral_pessoas_filial,gpj.razao_social, nf.id_geral_pessoas, gpe.endereco, gc.descricao, gc.ibge, ge.sigla, ge.ibge, gpa.nome, nf.id";

		$rs=gQuery($sql);
		//$this->rsRps=$rs;
		
		return $rs; 
	}

	/** Obtem um Rps
	 * @author	mateus
	 * @version	1.0 02-02-2010
	 * @return
	 */
	function obtemRps() {
		$item=false;
		if (!$this->rsRps->EOF) {
			//echo '<B>'.'NOTA='.$this->rsItem->fields['id_fin_notas'].'---ITEM='.$this->rsItem->fields['itemCod'].' ('.$this->rsItem->fields['nome'].')</B><br/>';
			$rps="";

			$rps['idNf']=$this->rsItem->fields['id_fin_notas']; // id da nota, apenas para controle na depuraï¿½ao...
			$rps['discriminacao']=$this->rsItem->fields['nome'];//
			$rps['itemListaServico']=$this->rsItem->fields['itemCod']; //$this->rsItem->fields['ItemListaServico']; // cod. do produto (id usado no BD) TODO:  retirar codigo de teste

			$rps['valor_unitario']=$this->rsItem->fields['valor_unitario']; //valor total bruto dos produtos ou servicos
			$rps['base_calculo']=$this->rsItem->fields['valor_unitario']; //valor total bruto dos produtos ou servicos
			$rps['desconto']=$this->rsItem->fields['desconto']; // valor do desconto. Nao obrigatorio
			// informacoes necessarias para outras partes do arquivo
			$rps['itemOrdem']=$this->rsItem->fields['itemOrdem']; // ordem do item na nota
			// impostos
			$rps['iss']=$this->rsItem->fields['iss'];
			$rps['icms']=$this->rsItem->fields['icms'];
			$rps['cofins']=$this->rsItem->fields['cofins']; // TODO: nao tem campo na tabela no BD, criar...
			$rps['pis']=$this->rsItem->fields['pis']; // TODO: nao tem campo na tabela no BD, criar...
			$rps['ipi']=$this->rsItem->fields['ipi']; // TODO: nao tem campo na tabela no BD, criar...
			//$mtz['issRetido']=$this->rsItem->fields['issRetido']; // TODO: nao tem campo na tabela no BD, criar...
			//$mtz['CodigoMunicipio']=$this->rsItem->fields['CodigoMunicipio']; // codigo do do municipio provido pelo IBGE TODO: sera o mesmo da nota?
			$rps['teste']='item';
				
			/* analisa item para ver se ele gera um novo RPS
			 * Condições:
			 * 1-Deve ser gerado um RPS para cada serviço (cada codigo) 
			 * 2-Deve ser consolidado os valores de um mesmo serviço (com mesmo codigo) em apenas um RPS, a menos que o mesmo serviço 
			 * 3-Deve ser gerado um RPS para cada municipio, caso os serviços sejam em municipios diferentes
			 */
			$this->item=$rps;

			$this->rsRps->MoveNext();
		}
		return($item);
	}

	/** Obtem um (proximo) item de uma nota. A cada chamada a este metodo, seria retornado o item subsequente, sendo o 1ï¿½ na 1ï¿½ chamada
	 * @author	giuliano e mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return array
	 */
	function obtemItem() {
		$item=false;
		if (!$this->rsItem->EOF) {
			//echo '<B>'.'NOTA='.$this->rsItem->fields['id_fin_notas'].'---ITEM='.$this->rsItem->fields['itemCod'].' ('.$this->rsItem->fields['nome'].')</B><br/>';
			$item="";

			$item['idNf']=$this->rsItem->fields['id_fin_notas']; // id da nota, apenas para controle na depuraï¿½ao...
			$item['discriminacao']=$this->rsItem->fields['nome'];//
			$item['itemListaServico']=$this->rsItem->fields['itemCod']; //$this->rsItem->fields['ItemListaServico']; // cod. do produto (id usado no BD) TODO:  retirar codigo de teste

			$item['valor_unitario']=$this->rsItem->fields['valor_unitario']; //valor total bruto dos produtos ou servicos
			$item['base_calculo']=$this->rsItem->fields['valor_unitario']; //valor total bruto dos produtos ou servicos
			$item['desconto']=$this->rsItem->fields['desconto']; // valor do desconto. Nao obrigatorio
			// informacoes necessarias para outras partes do arquivo
			$item['itemOrdem']=$this->rsItem->fields['itemOrdem']; // ordem do item na nota
			// impostos
			$item['iss']=$this->rsItem->fields['iss'];
			$item['icms']=$this->rsItem->fields['icms'];
			$item['cofins']=$this->rsItem->fields['cofins']; // TODO: nao tem campo na tabela no BD, criar...
			$item['pis']=$this->rsItem->fields['pis']; // TODO: nao tem campo na tabela no BD, criar...
			$item['ipi']=$this->rsItem->fields['ipi']; // TODO: nao tem campo na tabela no BD, criar...
			//$mtz['issRetido']=$this->rsItem->fields['issRetido']; // TODO: nao tem campo na tabela no BD, criar...
			//$mtz['CodigoMunicipio']=$this->rsItem->fields['CodigoMunicipio']; // codigo do do municipio provido pelo IBGE TODO: sera o mesmo da nota?
			$item['teste']='item';
				
			/* analisa item para ver se ele gera um novo RPS
			 * Condições:
			 * 1-Deve ser gerado um RPS para cada serviço (cada codigo) 
			 * 2-Deve ser consolidado os valores de um mesmo serviço (com mesmo codigo) em apenas um RPS
			 * 3-Deve ser gerado um RPS para cada municipio, caso os serviços sejam em municipios diferentes
			 */
				
				
				
				
				
				
				
				
			$this->item=$item;

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

	/** Obtem notas de acordo comum filtro que limita a quantidade TODO: implementar suporte ao filtro
	 * @author	mateus
	 * @version	1.0 28-10-2009 09:47
	 */
	public function obtemNotas() {

		$sql="SELECT
				gpj.razao_social AS razaoSocial,
				nf.id_geral_pessoas AS idCliente,
				gpe.endereco,
				gc.descricao AS cidade,
				gc.ibge AS cMunFG,
				ge.sigla AS estado,
				ge.ibge AS cUF,
				gpa.nome AS pais,
				nf.id AS idNf,
				nf.*,
				nop.codigo AS cfop
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
				AND nf.id in (201,202)
				ORDER BY nf.id ASC";
		//AND (nf.data_emissao>='".gDBDate($periodo1)." 00:00:00.0' AND nf.data_emissao<='".gDBDate($periodo2)." 00:00:00.0')
		//echo $sql; //exit;
		$rs=gQuery($sql);
		$this->rsNota=$rs;
	}

	/** Obtem uma (proxima) nota. A cada chamada a este mï¿½todo, serï¿½ retornado a nota subsequente, sendo o 1ï¿½ na 1ï¿½ chamada
	 * @author	mateus
	 * @version	1.0 28-10-2009 09:47
	 * @return String $nota
	 */
	public function obtemNota() {
		$nota=false;
		if (!$this->rsNota->EOF) {
			$nota="";
			$nota['id']=$this->rsNota->fields['idNf'];
			$nota['numero']=$this->rsNota->fields['numero'];
			$nota['id_geral_pessoas_filial']=$this->rsNota->fields['id_geral_pessoas_filial'];
			$nota['idCliente']=$this->rsNota->fields['idCliente'];
			$nota['uf']=$this->rsNota->fields['uf'];
			$nota['serie']=$this->rsNota->fields['serie'];
			$nota['dataEmissao']=$this->rsNota->fields['data_emissao'];
			$nota['cfop']=$this->rsNota->fields['cfop'];
			$nota['OptanteSimplesNacional']=2; // 1=sim 2=nao TODO: verificar com Nali
			$nota['IncentivadorCultural']=2; // 1=sim 2=nao
			$nota['Status']=1; // 1=normal 2=cancelado
			//$nota['teste']='teste fixo';
			//echo 'idCliente='.$nota['idCliente'].'<br>'; exit; // Debug
			$this->rsNota->MoveNext();
		}
		return($nota);
	}

	/** Obtem lote incrementando o ultimo ID do lote encontrado no BD
	 * @author	mateus
	 * @version	1.0 04-11-2009
	 * @return
	 */
	public function obtemLote() {
		$sql="SELECT TOP 1 id FROM fin_notas_lote ORDER BY id DESC";
		/*
		 $rs=gQuery($sql);
		 $lote=$rs->fields['id'];
		 $proximoLote=$lote+1;
		 */
		$proximoLote=2; // TODO: remover codigo de teste (geraï¿½ao de lote)

		return $proximoLote;
	}
}
?>