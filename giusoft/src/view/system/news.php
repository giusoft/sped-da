<?
/*
$sql="SELECT * FROM gfw_posts WHERE id>76";
$rs=dbQuery($sql);
foreach ($rs as $row)
{
	//<img src="http://www.giusoft.com.br/gadmin/files/uploads/2013/03/statussefaz01-143x300.png" alt="statussefaz01" class="alignnone size-large wp-image-651">
	$content=$row['content'];
	$title=$row['title'];
	$p=strpos($content,'<img src=');
	if ($p!==false)
	{
		$p2=strpos($content,'"',$p+11);
		$p3=strpos($content,'>',$p+11);
		$img=substr($content,$p+10,$p2-$p-10);
		$img=str_replace('http://www.giusoft.com.br/gadmin/','', $img);
		$content=substr($content,0,$p).'{{'.$img.' '.$title.'}}'.substr($content,$p3+1);
		//echo "<br>".$content."<hr>";

		$content = preg_replace_callback('/(http:\/\/youtu\.be[\w\.\/\?\-\=]+[^\n <])/', 'embraceLink', $content);
		$content = preg_replace_callback('/(http:\/\/www\.youtube\.com[\w\.\/\?\-\=]+[^\n <])/', 'embraceLink', $content);
		$content = preg_replace_callback('/(https:\/\/www\.youtube\.com[\w\.\/\?\-\=]+[^\n <])/', 'embraceLink', $content);
		$content = preg_replace_callback('/(http:\/\/www\.youtube\.com\/embbed[\w\.\/\?\-\=]+[^\n <])/', 'embraceLink', $content);
		$content = preg_replace_callback('/(https:\/\/www\.youtube\.com\/embbed[\w\.\/\?\-\=]+[^\n <])/', 'embraceLink', $content);
		$content = preg_replace_callback('/(http:\/\/vimeo\.com[\w\.\/\?\-\=]+[^\n <])/', 'embraceLink', $content);
		//$content = preg_replace('/(http:\/\/[\w\.\/\?\-\=]+[^\n <])/', '<a href="$1" rel="external" target="_new">$1</a>', $content);

		$sql="UPDATE gfw_posts SET content='$content' WHERE id=".$row['id'];
		dbQuery($sql);
	}
}
*/

$html = $o->msgTitle("Notícias");
$html.= '<form role="form" class="form-inline" action="'.$o->page.'">';
$html.= '<div class="form-group">';
if ($usrId>0)
	$html.=$o->button("{title: Sumário dos artigos; style: default; size: small; url: index.php?g=index&pp=start". "}");
$html.='<input class="form-control input-sm" style="width: 200px" placeholder="'.gT('Procurar por').'" name="gSearch" type="text" value="'.$search.'"> ';
//$html.='<input type="submit" class="btn btn-default btn-sm">';
$html.='<button type="submit" class="btn btn-default btn-sm"><span class="fal fa-search"></span></button>';
$html.='</form>';
$html.= '</div>'.$o->n.$o->n;
$html.= gPosts($o,"{tags: notícias news; expand: true}");


function embraceLink($match)
{
	$sai='{{'.$match.'}}';
}
?>
