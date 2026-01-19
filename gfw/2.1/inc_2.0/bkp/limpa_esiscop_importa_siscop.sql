# Script para importacão do Siscop para ESiscop v3.2
# ==========================================================
# Geral
use esiscop32;
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
update geral_pessoas set apelido='root',nome='Administrador',senha=PASSWORD('SHarpGif85'),cpf='',rg='',email='';
update geral_empresas set nome='Empresa',razao_social='Empresa Ltda.',cpf='',cnpj='',insc_municipal='',insc_estadual='',telefone='',fax='';

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
#truncate table desp_armadores;
#truncate table desp_documentos;
#truncate table desp_lancamentos;
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
truncate table fin_parcelas_rateios;
truncate table fin_tipos_rateio;
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
# Importacão de dados do siscop antigo  
# ==========================================================
use siscop;
# ==========================================================
# Clientes
insert into esiscop32.geral_pessoas select 1000+convert(Codigo,unsigned),1,'','',0,0,'J',1,0,0,0,0,Nome,Nome,password('123mudar'),E_Mail,'',Telefone_1,'',Fax_1,'Ativo',Data_Cadastro,'0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00',1,'123' FROM basicas order by Codigo;
insert into esiscop32.geral_pessoas_juridicas select 0,1000+convert(Codigo,unsigned),1,Razao_Social,CGC,Insc_Municipal,Insc_Estadual,WEB_Site,0,0 from basicas;
insert into esiscop32.geral_pessoas_enderecos select 0,1000+convert(Codigo,unsigned),'Faturamento',Endereco,'',concat(Cidade,'/',Estado),bairro,0,0,1,CEP from basicas;
# ==========================================================
# Despachantes
insert into esiscop32.desp_contador select 0,Ano,Tipo,Contador from contador order by Ano,Tipo;
insert into esiscop32.desp_documentos select 0,Codigo,Descricao from documen order by Codigo;
insert into esiscop32.desp_lancamentos select 0,Codigo,Descricao,Tipo,CPMF,Historico,Cheque from lancam order by Codigo;
insert into esiscop32.desp_favorecidos select 0,Codigo,Descricao from favorecidos order by Codigo;

insert into esiscop32.desp_movope
insert into esiscop32.desp_movlan
insert into esiscop32.desp_movdoc select 0,Referencia,Codigo,Quantidade,Cancelado from movdoc order by Referencia;
insert into esiscop32.desp_movser select 0,Referencia,Codigo,Quantidade,Valor,'0000-00-00',1,Cancelado from movser order by Referencia;
insert into esiscop32.desp_movati select 0,referencia,data_criacao,usuario,hora,descricao from status order by referencia;
insert into esiscop32.desp_nr_form_a select 0,Referencia,Nr_Form_A from nr_form_a order by Referencia;

# ==========================================================
# Deve ser feito manualmente:
# Editar pes_setores para colocar a sigla correta dos setores (ex: Global é GEX ao invés de EXP)
# Editar desp_contador para colocar os números das referências
# Editar desp_configuracao para definir o comportamento do sistema
# Editar desp_conf_emails para definir o comportamento do sistema para envio de emails
# Cadastrar os funcionários (usuários)
# ==========================================================

