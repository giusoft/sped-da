<?php

$html = $o->msgTitle("Notícias");

$html .= '<form role="form" class="form-inline" action="'.$o->page.'">';
$html .= '<div class="form-group">';

if ($usrId > 0) {
    $html .= $o->button("{title: Sumário dos artigos; style: default; size: small; url: index.php?g=index&pp=start". "}");
}

$html .= '<input class="form-control input-sm" style="width: 200px" placeholder="'.gT('Procurar por').'" name="gSearch" type="text" value="' . $search . '"> ';
$html .= '<button type="submit" class="btn btn-default btn-sm"><span class="fal fa-search"></span></button>';
$html .= '</form>';
$html .=  '</div>'.$o->n.$o->n;
$html .=  gPosts($o,"{tags: notícias news; expand: true}");

function embraceLink($match)
{
	$sai='{{'.$match.'}}';
}
