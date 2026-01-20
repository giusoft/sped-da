<?php

if ($_SESSION['edicaoAtiva'] == 1) {
	$html .= $o->msgTitle("Edição de páginas do Website");
	$html .= "A edição de páginas foi desativada, retornando ao uso normal do sistema.<br>";
	$html .= "Caso deseje editar novamente as páginas do Website, clique na opção ".$o->button("{icon: pencil; caption: Editar páginas; size: small; href: ".$o->page."}")." do menu <b>Tecnologia</b>.";
	$_SESSION['edicaoAtiva'] = 0;
} else {
	$html .= $o->msgTitle("Edição de páginas do Website");
	$html .= "A partir de agora, você poderá editar o conteúdo das páginas editáveis do Website.<br>";
	$html .= "Para fazer isto, basta clicar no link da página desejada no menu e clicar no botão ".$o->button("{icon: pencil; caption: Editar; size: small}")."<br><br>";
	$html .= "Para retornar ao sistema, clique no ícone ".$o->button("{icon: gear; size: small;}")." no menu e em seguida, na opção ".$o->button("{icon: sign-out; caption: Retornar ao sistema; size: small; href: ".$o->page."}");

	$_SESSION['edicaoAtiva'] = 1;
}
