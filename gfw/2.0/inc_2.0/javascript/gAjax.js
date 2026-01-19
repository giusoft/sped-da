function carrega(n){

    //Exibe o texto carregando no div conteúdo
    var conteudo=document.getElementById("conteudo")
    conteudo.innerHTML='<div class="carregando">carregando...</div>'

    //Guarda a página escolhida na variável atual
    atual=n

    //Abre a url
    xmlhttp.open("GET", "funcoes.php?n="+n,true);

    //Executada quando o navegador obtiver o código
    xmlhttp.onreadystatechange=function() {

        if (xmlhttp.readyState==4){

            //Lê o texto
            var texto=xmlhttp.responseText

            //Desfaz o urlencode
            texto=texto.replace(/\+/g," ")
            texto=unescape(texto)

            //Exibe o texto no div conteúdo
            var conteudo=document.getElementById("conteudo")
            conteudo.innerHTML=texto

            //Obtém os links do menu
            var menu=document.getElementById("menu")
            var links=menu.getElementsByTagName("a")

            //Limpa as classes do menu
            for(var i=0;i<links.length;i++)
                links[i].className=""

            //Marca o selecionado
            links[atual-1].className="selected"
        }
    }
    xmlhttp.send(null)
}
