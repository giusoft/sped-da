<?php

$debug = false; // pra não gerar Log do gBrowser

$sp["gfw_locales"]="SELECT id,idd, name FROM gfw_locales ORDER BY locale='pt_BR' DESC";
$sp["gfw_pages"]="SELECT DISTINCT keyword, title FROM gfw_pages";
$sp["gfw_home"]="SELECT DISTINCT * FROM gfw_home ORDER BY active DESC, id";
$sp["gfw_links"]="SELECT * FROM gfw_links ORDER BY keyword ";
$sp["gfw_themes"]="SELECT name,description FROM gfw_themes ORDER BY description";
$sp["gfw_permissions"]="SELECT DISTINCT * FROM gfw_permissions ORDER BY active DESC, name";
$sp["gfw_permissions_links"]="SELECT DISTINCT * FROM gfw_permissions_links ";
$sp["gfw_permissions_users"]="SELECT DISTINCT * FROM gfw_permissions_users ";
$sp["parametros"]="SELECT * FROM parametros ORDER BY chave";

$sp["combo_gfw_permissions"]="SELECT id,name FROM gfw_permissions ORDER BY name";

// Pessoas
$sp['combo_pessoas']="SELECT id, apelido FROM pessoas WHERE tipo = 'F' AND situacao = 'Ativo' AND apelido <> '' ORDER BY apelido";
$sp['combo_funcionarios']="SELECT id, apelido FROM pessoas WHERE tipo = 'F' AND situacao = 'Ativo' AND funcionario = 1 AND cliente = 0 AND apelido <> '' ORDER BY apelido";
$sp['combo_clientes'] = "SELECT id, apelido FROM pessoas WHERE situacao = 'Ativo' AND (cliente = 1) AND apelido <> '' ORDER BY apelido";
$sp['combo_cliente_final'] = "
      SELECT pessoas.id,
            pessoas.apelido  descricao
      FROM pessoas
      JOIN pessoas_juridicas ON pessoas_juridicas.id_pessoas = pessoas.id
      WHERE pessoas.situacao = 'Ativo'
            AND pessoas.cliente_final = 1
            AND pessoas.apelido <> ''
      ORDER BY pessoas.apelido";

$sp['combo_fornecedores']="SELECT id,apelido FROM pessoas WHERE situacao='Ativo' AND fornecedor = 1 ORDER BY nome";
$sp['combo_motoristas']="SELECT id,apelido FROM pessoas WHERE tipo='F' AND situacao='Ativo' AND motorista=1 AND apelido <> '' ORDER BY nome";
$sp['combo_empresas']="SELECT id,apelido FROM pessoas WHERE tipo='J' AND situacao='Ativo' AND apelido <> '' ORDER BY apelido";
$sp['combo_transportadora']="SELECT id,apelido FROM pessoas WHERE situacao='Ativo' AND transportadora=1 ORDER BY nome";
// $sp["combo_setores"]="SELECT id,descricao FROM setores ORDER BY descricao";
// Endereços
$sp["combo_cidades"]="SELECT id,descricao FROM enderecos_cidades ORDER BY descricao";
$sp["combo_estados"]="SELECT id,descricao FROM enderecos_estados ORDER BY descricao";
$sp["combo_paises"]="SELECT id,nome FROM enderecos_paises ORDER BY nome like '%Brasil%' DESC,nome";

$sp["enderecos_cidades"]="SELECT id,id_enderecos_estados,descricao,sigla,codigo_ibge FROM enderecos_cidades ORDER BY descricao";

$sp["enderecos_paises"] = "SELECT id, nome, codigo AS codigo_ibge, codigo_area, sigla FROM enderecos_paises";
$sp["combo_grupos"]="SELECT id,descricao FROM grupos ORDER BY descricao";

$sp["combo_grupos_proprietario"]="SELECT id, descricao
						 FROM grupos G ORDER BY descricao;
						 INNER JOIN itens I ON G.id = I.id_grupos
						 INNER JOIN itens_skus SK ON SK.id_itens = I.id
						 WHERE I.ativo='1' AND SK.ativo='1' SK.codigo <> '' AND I.id_pessoas_proprietario='{$usrId}'
						 GROUP BY id, descricao";

$sp["combo_unidades"]="SELECT id, IF((descricao IS NULL OR descricao = ''), sigla, CONCAT(sigla, ' - ', descricao)) as descricao FROM unidades ORDER BY descricao";
$sp["combo_tipos"]="SELECT id,descricao FROM tipos ORDER BY descricao";

$sp["combo_itens_por_proprietario"]="SELECT ISK.id,
			            CONCAT(CONCAT_WS(' • ',ISK.codigo, I.nome,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
			            FROM itens I
			            LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
			            LEFT JOIN unidades U on U.id = ISK.id_unidades
			            WHERE
                                    I.ativo=1 AND ISK.ativo=1 AND I.apto=1 AND I.id_pessoas_proprietario='{$usrId}' AND ISK.codigo <> ''
			            GROUP BY ISK.id, I.nome";
$sp["combo_notas"] = "SELECT id, numero FROM notas";

$sp["combo_cfop"] = "SELECT id, CONCAT(codigo,' - ',descricao) descricao FROM cfops WHERE codigo ORDER BY codigo";
$sp["combo_cfop_entrada"] = "SELECT id, CONCAT(codigo,' - ',descricao) descricao FROM cfops WHERE SUBSTR(codigo, 1, 1) IN ('1','2','3') ORDER BY codigo";
$sp["combo_cfop_saida"] = "SELECT id, CONCAT(codigo,' - ',descricao) descricao FROM cfops WHERE SUBSTR(codigo, 1, 1) IN ('5','6','7') ORDER BY codigo";
$sp["combo_icms_cst"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_icms_cst WHERE ativo = 1 ORDER BY codigo";
$sp["combo_imp_icms_origem"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_icms_origem WHERE ativo = 1 ORDER BY codigo";
$sp["combo_imp_icms_mod"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_icms_mod WHERE ativo = 1 ORDER BY codigo";
$sp["combo_imp_ipi_cst"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_ipi_cst WHERE ativo = 1 ORDER BY codigo";
$sp["combo_imp_pis_cst"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_pis_cst WHERE ativo = 1 ORDER BY codigo";
$sp["combo_imp_cofins_cst"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_cofins_cst WHERE ativo = 1 ORDER BY codigo";
$sp["combo_imp_ibs_cbs_cst"] = "SELECT id, CONCAT(codigo,' - ',descricao) FROM imp_ibs_cbs_cst WHERE ativo = 1 ORDER BY codigo";
$sp["combo_informacoes_nfe"] = "SELECT id, descricao FROM nfe_informacoes ORDER BY descricao ASC";
$sp['combo_tipos_ocorrencias']="SELECT id,descricao FROM tipos_ocorrencias WHERE ativo=1 ORDER BY id";

// Estoque
$sp["filial"]="SELECT * FROM filial ORDER BY descricao";
$sp["combo_filial"]="SELECT id,descricao FROM filial ORDER BY descricao";
$sp["cfops"]="SELECT * FROM cfops WHERE codigo ORDER BY codigo";
// $sp["setores"]="SELECT * FROM setores ORDER BY descricao";
$sp["grupos"]="SELECT id,descricao FROM grupos ORDER BY descricao";
$sp["unidades"]="SELECT * FROM unidades ORDER BY descricao";
$sp["tipos"]="SELECT id,descricao FROM tipos ORDER BY descricao";
$sp["itens"]="SELECT id,descricao FROM itens ORDER BY descricao";
$sp["itens_aptos"]="SELECT id,descricao FROM itens WHERE apto=1 AND ativo=1 ORDER BY descricao";
$sp["importacoes"]="SELECT * FROM importacoes ORDER BY descricao";
$sp["importacoes_cabecalho"]="SELECT * FROM importacoes_cabecalho ORDER BY ordem";
$sp["importacoes_registros"]="SELECT * FROM importacoes_registros ORDER BY ordem";
$sp["nfe_informacoes"]="SELECT * FROM nfe_informacoes ORDER BY descricao";
$sp["imp_icms_cst"] = "SELECT * FROM imp_icms_cst";
$sp["imp_ipi_cst"] = "SELECT * FROM imp_ipi_cst";
$sp["imp_pis_cst"] = "SELECT * FROM imp_pis_cst";
$sp["imp_cofins_cst"] = "SELECT * FROM imp_cofins_cst";
$sp["imp_ibs_cbs_cst"] = "SELECT * FROM imp_ibs_cbs_cst";
$sp["cclasstrib_ibs_cbs"] = "SELECT * FROM cclasstrib_ibs_cbs";

gLoadDBCache();