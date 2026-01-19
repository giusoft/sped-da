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
# Deve ser feito manualmente:
# Editar pes_setores para colocar a sigla correta dos setores (ex: Global é GEX ao invés de EXP)
# Editar desp_contador para colocar os números das referências
# Editar desp_configuracao para definir o comportamento do sistema
# Editar desp_conf_emails para definir o comportamento do sistema para envio de emails
# ==========================================================

