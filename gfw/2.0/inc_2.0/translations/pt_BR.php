<?
$gLngs="";
$gLngs["welcome"]=array("Bem-vindo","Seja bem-vindo !");
$gLngs["screenshots"]=array("Screenshots","Screenshots");
$gLngs["documentation"]=array("Documentação","Documentação");
$gLngs["help"]=array("Ajuda","Ajuda");
$gLngs["changelog"]=array("Changelog","Changelog");
$gLngs["downloads"]=array("Downloads","Downloads");
$gLngs["faq"]=array("FAQ","Perguntas e Respostas");
$gLngs["forum"]=array("Fórum","Fórum");
$gLngs["links"]=array("Links","Links");
$gLngs["menu"]=array("MENU","Opções do Sistema");
$gLngs["logout"]=array("SAIR","Finalizar o uso do sistema");

//message
$gLngs["password"]=array("Senha","Senha de acesso");
$gLngs["plate"]=array("Placa","Placa do veículo");
$gLngs["email"]=array("Email","E-mail");
$gLngs["true"]=array("X","Verdadeiro");
$gLngs["false"]=array("&nbsp","Falso");
$gLngs["yes"]=array("Sim","Positivo");
$gLngs["no"]=array("Não","Negativo");
$gLngs["sum"]=array("Soma","Somatório");
$gLngs["max"]=array("Máx","Máximo");
$gLngs["min"]=array("Mín","Mínimo");
$gLngs["count"]=array("TTL","Contagem");
$gLngs["avg"]=array("Média","Média");
$gLngs["confirm"]=array("Confirmar","Confirma operação ?");
$gLngs["save"]=array("Salvar","Salvar");
$gLngs["send"]=array("Enviar","Enviar");
$gLngs["cancel"]=array("Cancelar","Cancelar");
$gLngs["back"]=array("Voltar","Retornar à página anterior");
$gLngs["reset"]=array("Limpar","Re-iniciar");
$gLngs["hide"]=array("Ocultar","Ocultar");
$gLngs["advanced"]=array("Avançado","Mais opções...");
$gLngs["confirm_delete"]=array("Exclui ?","Confirma exclusão ?");
$gLngs["sourcecode"]=array("Fonte:","Código fonte:");
$gLngs["options"]=array("Opções","Opções");
$gLngs["print_date"]=array("Data: ","Impresso em: ");
$gLngs["denied"]=array("Acesso negado","Tentativa de acesso a registro inválido.");
$gLngs["invert"]=array("Inverter","Inverter seleção");
$gLngs["selected"]=array("Seleção:","Itens selecionados: ");
$gLngs["select_item_first"]=array("Selecione primeiro","Selecione os itens primeiro!");

//message

//report
$gLngs["page_no"]=array("Pág.: ","Página: ");
$gLngs["print_date"]=array("Impresso: ","Impresso em: ");
//report

//errors
$gLngs["unknow"]=array("","Erro desconhecido");
$gLngs["filenotfound"]=array("","Arquivo não encontrado");
$gLngs["database"]=array("","Erro no banco de dados");
$gLngs["invalid_date"]=array("Data inválida","Data inválida! O valor atual será substituído pela data de hoje...");
$gLngs["num"]=array("","Número inválido");
$gLngs["id_not_found"]=array("","Campo ID não encontrado nesta tabela do banco de dados!");
$gLngs["nothing_to_show"]=array("","Nenhuma opção disponível");
$gLngs["permission_denied"]=array("","Acesso não permitido");
$gLngs["session_expired"]=array("Acesso negado","Tempo de inatividade alcançado ou tentativa de violação de segurança.<br>Sua sessão expirou.<br><b>Efetue o login novamente.</b>");
//errors

//includes
if(gVar("global.nome")=="renco")$gLngs["gselect"]=array("0","* A Informar");
elseif(gVar("global.nome")=="iplasnor")$gLngs["gselect"]=array("0","* Diversos");
else $gLngs["gselect"]=array("0","* Indiferente");
$gLngs["gform_ate"]=array("&nbsp;até&nbsp;","&nbsp;até&nbsp;");
$gLngs["gform"]=array("Formulário","Formulário de Entrada de Dados");
$gLngs["gfilter"]=array("Filtros","Opções de filtro para amostragem de dados");
$gLngs["options"]=array("Opções","Opções");
$gLngs["register_new"]=array("Novo","Novo Registro");
$gLngs["register_edit"]=array("Editar","Editar registro");
$gLngs["register_list"]=array("Listar","Listar registros");
$gLngs["register_select"]=array("Selecionar","Selecionar registro");
$gLngs["register_delete"]=array("Apagar","Apagar registro");
$gLngs["register_copy"]=array("Copiar","Criar um novo registro com base no atual");
$gLngs["register_refresh"]=array("Atualizar","Atualizar registro");
$gLngs["register_showall"]=array("Tudo","Mostrar tudo");
$gLngs["register_export"]=array("Exportar","Exportar para");
//includes

//hint
$gLngs["hintdate"]=array("Digite uma data","Digite uma data no formato: ");
$gLngs["hintdatetime"]=array("Digite uma data e hora","Digite uma data e hora no formato: ");
$gLngs["hintnum"]=array("Digite um número","Digite um número sem o dígito separador de milhares");
$gLngs["hintcheck"]=array("Marque a caixa para opção afirmativa","Marque a caixa para opção afirmativa");
$gLngs["hinttext"]=array("Digite um texto","Digite o texto com o máximo de ");
$gLngs["hintchars"]=array("caracteres","caracteres");
$gLngs["hintselectcode"]=array("Digite o código (id) ou clique na interrogação","Digite o código (id) ou clique na interrogação para pesquisar");
$gLngs["hintpassword"]=array("Digite a senha no primeiro campo e sua confirmação no segundo","Digite a senha no primeiro campo e sua confirmação no segundo");
//hint

// geral

$gLngs["clique aqui para salvar este cadastro"]="Click here to save this data";

//geral

// Ofertas

$gLngs["nenhum produto selecionado"]="<b>Você não possui nenhum pedido.</b><br><br>Para criar um pedido, selecione o produto desejado utilizando a ferramenta de busca ao lado direito, para em seguida clicar sobre o ícone do carrinho de compras.<br><br>Será criado um pedido para cada fornecedor e forma de pagamento selecionados.";

// relacionamentos

$gLngs["física"]="Cadastro de pessoa física";
$gLngs["jurídica"]="Cadastro de pessoa jurídica";
$gLngs["dimensao_imagem"]="A imagem deve ter no máximo:<br><ul><li>Dimensão com 320x240 pixels<li>Tamanho 100Kbytes";
$gLngs["explicacao_convite"]="Este procedimento enviará por e-mail, um convite para fazer parte do Alitem, e, assim que este contato efetuar o login pela primeira vez, não será mais permitido realizar alterações nestes dados.";
?>
