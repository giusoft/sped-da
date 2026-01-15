# Script para importacão do Siscop para ESiscop v3.2
# ==========================================================
# Geral
use esiscop32;
ALTER TABLE com_contratos ENGINE = InnoDB;
ALTER TABLE geral_acessos ENGINE = InnoDB;
ALTER TABLE geral_online ENGINE = InnoDB;
ALTER TABLE geral_agenda ENGINE = InnoDB;
ALTER TABLE geral_arquivos ENGINE = InnoDB;
ALTER TABLE geral_noticias ENGINE = InnoDB;
ALTER TABLE geral_tarefas ENGINE = InnoDB;
ALTER TABLE geral_procedimentos ENGINE = InnoDB;
ALTER TABLE geral_pessoas ENGINE = InnoDB;
ALTER TABLE geral_pessoas_fisicas ENGINE = InnoDB;
ALTER TABLE geral_pessoas_juridicas ENGINE = InnoDB;
ALTER TABLE geral_pessoas_enderecos ENGINE = InnoDB;

ALTER TABLE pes_funcionalismo ENGINE = InnoDB;
ALTER TABLE pes_funcionalismo_funcoes ENGINE = InnoDB;
ALTER TABLE pes_funcionalismo_reajustes ENGINE = InnoDB;
ALTER TABLE pes_funcionalismo_setores ENGINE = InnoDB;

ALTER TABLE desp_movope ENGINE = InnoDB;
ALTER TABLE desp_movlan ENGINE = InnoDB;
ALTER TABLE desp_movati ENGINE = InnoDB;
ALTER TABLE desp_movser ENGINE = InnoDB;
ALTER TABLE desp_movdoc ENGINE = InnoDB;
ALTER TABLE desp_nr_form_a ENGINE = InnoDB;
ALTER TABLE desp_status ENGINE = InnoDB;
ALTER TABLE desp_taxas ENGINE = InnoDB;
ALTER TABLE desp_favorecidos ENGINE = InnoDB;
ALTER TABLE desp_faturamento ENGINE = InnoDB;
ALTER TABLE desp_lancamentos ENGINE = InnoDB;
ALTER TABLE desp_transportes ENGINE = InnoDB;

ALTER TABLE qualidade ENGINE = InnoDB;
ALTER TABLE qualidade_pesquisas ENGINE = InnoDB;
ALTER TABLE qualidade_email_setor ENGINE = InnoDB;
ALTER TABLE qualidade_rnc ENGINE = InnoDB;
ALTER TABLE qualidade_rrc ENGINE = InnoDB;
ALTER TABLE qualidade_status_relatorios ENGINE = InnoDB;

ALTER table transp_act ENGINE = InnoDB;
ALTER table transp_act_conhecimento ENGINE = InnoDB;
ALTER table transp_adiantamento ENGINE = InnoDB;
ALTER table transp_conhecimento ENGINE = InnoDB;
ALTER table transp_desconto ENGINE = InnoDB;
ALTER table transp_fatura ENGINE = InnoDB;
ALTER table transp_fatura_sem_conhec ENGINE = InnoDB;
ALTER table transp_frete_carreteiro ENGINE = InnoDB;
ALTER table transp_frete_desconto ENGINE = InnoDB;
ALTER table transp_ocorrencias ENGINE = InnoDB;
ALTER table transp_os ENGINE = InnoDB;
ALTER table transp_os_pecas ENGINE = InnoDB;
ALTER table transp_pneus ENGINE = InnoDB;
ALTER table transp_pneus_movimentos ENGINE = InnoDB;
ALTER table transp_prog_conhec ENGINE = InnoDB;
ALTER table transp_programacao ENGINE = InnoDB;
ALTER table transp_veiculos ENGINE = InnoDB;
alter table com_contatos_telefonicos ENGINE = Innodb;
alter table com_contratos ENGINE = Innodb;
alter table com_ordens ENGINE = Innodb;
alter table com_produtos ENGINE = Innodb;
alter table com_propostas ENGINE = Innodb;
alter table com_propostas_itens ENGINE = Innodb;
alter table com_propostas_padroes ENGINE = Innodb;
alter table com_propostas_padroes_itens ENGINE = Innodb;
alter table com_reunioes ENGINE = Innodb;

alter table fin_contratos ENGINE = Innodb;
alter table fin_tipos ENGINE = Innodb;
alter table fin_contas ENGINE = Innodb;
alter table fin_lancamentos ENGINE = Innodb;
alter table fin_parcelas ENGINE = Innodb;
alter table fin_tipos_rateio ENGINE = Innodb;
alter table fin_notas ENGINE = Innodb;
alter table fin_notas_itens ENGINE = Innodb;

alter table est_movimentos ENGINE = Innodb;
alter table est_itens ENGINE = Innodb;

truncate table geral_acessos;
truncate table geral_online;
truncate table geral_agenda;
truncate table geral_arquivos;
truncate table geral_noticias;
truncate table geral_tarefas;
truncate table geral_procedimentos;
delete from geral_pessoas where id>1;
delete from geral_pessoas_fisicas where id_geral_pessoas>1;
delete from geral_pessoas_enderecos where id_geral_pessoas>1;
delete from geral_pessoas_juridicas where id_geral_pessoas>1;
update geral_pessoas set apelido='root',nome='Administrador',senha=PASSWORD('SHarpGif85'),email='' where id=1;
update geral_pessoas_juridicas set razao_social='Empresa Ltda.',cnpj='',insc_municipal='',insc_estadual='' where id_geral_pessoas=1;

# ==========================================================
# Pessoas
truncate table pes_funcionalismo;
truncate table pes_funcionalismo_funcoes;
truncate table pes_funcionalismo_reajustes;
truncate table pes_funcionalismo_setores;

# ==========================================================
# Despachantes 
truncate table desp_movope;
truncate table desp_movlan;
truncate table desp_movati;
truncate table desp_movser;
truncate table desp_movdoc;
truncate table desp_nr_form_a;
truncate table desp_status;
truncate table desp_taxas;
truncate table desp_favorecidos;
truncate table desp_faturamento;
truncate table desp_transportes;
truncate table desp_armadores;
truncate table desp_documentos;
truncate table desp_lancamentos;
truncate table qualidade;
truncate table qualidade_email_setor;
truncate table qualidade_pesquisas;
truncate table qualidade_rnc;
truncate table qualidade_rrc;
truncate table qualidade_status_relatorios;

# ==========================================================
# Transporte
truncate table transp_act;
truncate table transp_act_conhecimento;
truncate table transp_adiantamento;
truncate table transp_conhecimento;
truncate table transp_desconto;
truncate table transp_fatura;
truncate table transp_fatura_sem_conhec;
truncate table transp_frete_carreteiro;
truncate table transp_frete_desconto;
truncate table transp_ocorrencias;
truncate table transp_os;
truncate table transp_os_pecas;
truncate table transp_pneus;
truncate table transp_pneus_movimentos;
truncate table transp_prog_conhec;
truncate table transp_programacao;
truncate table transp_veiculos;

# ==========================================================
# Comercial
truncate table com_contatos_telefonicos;
truncate table com_contratos;
truncate table com_ordens;
truncate table com_produtos;
truncate table com_propostas;
truncate table com_propostas_itens;
truncate table com_propostas_padroes;
truncate table com_propostas_padroes_itens;
truncate table com_reunioes;

# ==========================================================
# Financeiro
truncate table fin_contratos;
#truncate table fin_comissoes;
#truncate table fin_ccustos;
#truncate table fin_tipos;
truncate table fin_contas;
truncate table fin_lancamentos;
truncate table fin_parcelas;
truncate table fin_notas;
truncate table fin_notas_itens;

# ==========================================================
# Administrativo
truncate table est_movimentos;
truncate table est_itens;

# ==========================================================
# Temporárias
truncate table tmp_permissoes;
truncate table tmp_meses;

# ==========================================================
# Importacão de dados do siscop antigo  (2.0)
#
# Esta versão tem a estrutura de clientes e usuários totalmente diferente, mas
# movope, movlan, e outras mantêm estrutura parecida.
# ==========================================================
use siscop;
# ==========================================================
# Empresas
insert into esiscop32.geral_pessoas select 1000+convert(Codigo,unsigned),1,'','',0,0,'J',1,0,0,0,0,Nome,Nome,password('123mudar'),E_Mail,'',Telefone_1,'',Fax_1,'Ativo',Data_Cadastro,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'123' FROM basicas order by Codigo;
insert into esiscop32.geral_pessoas_juridicas select 0,1000+convert(Codigo,unsigned),1,Razao_Social,CGC,Insc_Municipal,Insc_Estadual,WEB_Site,0,0 from basicas;
insert into esiscop32.geral_pessoas_enderecos select 0,1000+convert(Codigo,unsigned),'Faturamento',Endereco,'',concat(Cidade,'/',Estado),bairro,0,0,1,CEP from basicas;
# ==========================================================
# Despachantes
insert into esiscop32.desp_contador select 0,Ano,Tipo,Contador from contador order by Ano,Tipo;
insert into esiscop32.desp_documentos select 0,Codigo,Descricao from documen order by Codigo;
insert into esiscop32.desp_lancamentos select 0,Codigo,Descricao,Tipo,CPMF,Historico,Cheque from lancam order by Codigo;
insert into esiscop32.desp_favorecidos select 0,Codigo,Descricao from favorecidos order by Codigo;
#navios
insert into esiscop32.desp_movope 
insert into esiscop32.desp_movlan
insert into esiscop32.desp_movdoc select 0,Referencia,Codigo,Quantidade,Cancelado from movdoc order by Referencia;
insert into esiscop32.desp_movser select 0,Referencia,Codigo,Quantidade,Valor,'0000-00-00',1,Cancelado from movser order by Referencia;
insert into esiscop32.desp_movati select 0,referencia,data_criacao,usuario,hora,descricao from status order by referencia;
insert into esiscop32.desp_nr_form_a select 0,Referencia,Nr_Form_A from nr_form_a order by Referencia;
# ==========================================================
# Importacão de dados do siscop antigo  (3.0)
#
# Esta versão diferencia-se da 3.2 somente pela tabela geral_empresas que não existe mais
# ==========================================================
use erp_siscop;
# ==========================================================
# Usuários
insert into esiscop32.geral_pessoas select id,1000+id_geral_empresas,'','',0,0,'F',0,0,0,0,1,nome,apelido,senha,email,'',telefone_residencial,'',fax,situacao,now(),'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'123' from geral_pessoas where id>1;
insert into esiscop32.geral_pessoas_fisicas select 0,id,'0000-00-00',telefone_comercial,telefone_celular,telefone_residencial,cpf,rg,contato_principal,0,0,0 from geral_pessoas where id>1;
insert into esiscop32.geral_pessoas_enderecos select 0,id,'Correspondência',endereco,'',complemento,bairro,id_geral_cidades,id_geral_estados,1,cep from geral_pessoas where id>1;
insert into esiscop32.pes_funcionalismo select id,id_geral_pessoas,'',data_admissao,data_final_da_experiencia,data_demissao,1,1,tipo_sanguineo,alergia,avaliacao_psicologica, avaliacao_tecnica, avaliacao_redacao, salario_base,tipo_contrato,id_fin_bancos, agencia, conta_corrente, trienio, filhos, dependentes, transportes_por_dia, now() from pes_funcionalismo;
# Empresas
insert into esiscop32.geral_pessoas select 1000+id,1,'','',0,0,'J',cliente,fornecedor,0,concorrente,0,nome,nome,PASSWORD('123mudar'),email,'',telefone,'',fax,situacao,now(),'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,password('123') from geral_empresas;
insert into esiscop32.geral_pessoas_juridicas select 0,1000+id,1,razao_social,cnpj,insc_municipal,insc_estadual,site,0,0 from erp_siscop.geral_empresas;
insert into esiscop32.geral_pessoas_enderecos select 0,1000+id_geral_empresas,'Faturamento',endereco,'',complemento,bairro,id_geral_cidades,id_geral_estados,1,cep from geral_empresas_enderecos;
# Comercial
insert into esiscop32.com_produtos select 0,id_pes_setores,id_com_produtos_categorias,nome,descricao,valor,valor_minimo,valor_maximo,unidade,prazo,id_geral_pessoas_criou,id_geral_pessoas_alterou,id_geral_pessoas_cancelou,data_criacao,data_alteracao,data_cancelamento,produto,servico from com_produtos;
insert into esiscop32.com_contratos select 0,0,id_com_propostas,id_geral_empresas_contratante+1000,id_geral_pessoas_contratante,1,id_geral_pessoas_contratado,id_geral_pessoas_ass1,id_geral_pessoas_ass2,data_inicio,data_final,data_cancelamento,dia_vencimento,valor_mensal,multa,juros,'Prestação de serviços','',observacoes from com_contratos; 
# Despachantes
insert into esiscop32.desp_conf_emails select * from desp_conf_emails;
insert into esiscop32.desp_configuracao select * from desp_configuracao;
insert into esiscop32.desp_contador select * from desp_contador;
insert into esiscop32.desp_documentos select * from desp_documentos;
insert into esiscop32.desp_faturamento select * from desp_faturamento;
insert into esiscop32.desp_favorecidos select * from desp_favorecidos;
insert into esiscop32.desp_lancamentos select * from desp_lancamentos;
insert into esiscop32.desp_movati select id,referencia,data_criacao,usuario,hora,descricao from desp_movati;
insert into esiscop32.desp_movdoc select * from desp_movdoc;
insert into esiscop32.desp_movser select id,referencia,id_com_produtos,quantidade,valor,'0000-00-00',1,cancelado from desp_movser;
insert into esiscop32.desp_nr_form_a select * from desp_nr_form_a;
insert into esiscop32.desp_taxas select * from desp_taxas;
insert into esiscop32.desp_transportes select * from desp_transportes;
insert into esiscop32.desp_movlan select id,referencia, id_desp_lancamentos, data,hora, data_pagamento,valor, historico,favorecido,cancelado,motivo, criado_por,alterado_por,cancelado_por, autorizado,atualizado, pagar, cheque, cheque_emitido,cheque_numero,cpmf,despesa,data_sda, data_efetivacao from desp_movlan;
insert into esiscop32.desp_movope select id,idctrl,referencia,ref_externa,contato,data_inicio,hora_inicio,situacao,data_situacao, id_geral_empresas+1000, shipper, consignee, id_desp_transportes,navio_viagem, eta_navio, armador, booking, origem_destino, porto_transbordo, voo, entrega_docs_originais, valor_delivery, house, RV, canal, container_selo, mercadoria, volumes, unidade, peso_liquido, peso_bruto, ncm, valor_cif, moeda, chegada_mercadoria, chegada_documento, conhecimento, data_conhecimento, data_desembaraco, re,dde,dsi, dse,deadline, di, data_di,li, armazem, vencimento_armazenagem, vencimento_murrage, entrega_container, ajustes, criado_por,alterado_por, cancelado_por, id_pes_setores, nota_fiscal, vencimento, emissao, observacoes, saldo, irf, iss, valor_lei10833, '', '', num_invoice, incoterm from desp_movope;
insert into esiscop32.desp_armadores select distinct 0,'',armador from desp_movope order by armador;
# ==========================================================
# Deve ser feito manualmente:
# Editar pes_setores para colocar a sigla correta dos setores (ex: Global é GEX ao invés de EXP)
# Editar desp_contador para colocar os números das referências
# Editar desp_configuracao para definir o comportamento do sistema
# Editar desp_conf_emails para definir o comportamento do sistema para envio de emails
# Cadastrar os funcionários (usuários)
# ==========================================================

