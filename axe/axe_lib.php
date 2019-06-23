<?php
/*
	axe_lib.php
	Define as rotinas gerais do engine axe

	© 2013 Augusto Campos http://augustocampos.net/ (4.05.2013)
	Licensed under the Apache License, Version 2.0 (the "License"); 
	you may not use this file except in compliance with the License. 
	You may obtain a copy of the License at 
	http://www.apache.org/licenses/LICENSE-2.0 

	Unless required by applicable law or agreed to in writing, software 
	distributed under the License is distributed on an "AS IS" BASIS, 
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. 
	See the License for the specific language governing permissions and 
	limitations under the License.

*/


// 		ATENÇÃO: defina os detalhes do seu blog e 
// 		servidor no arquivo axe_config.php, e não aqui.
// 		***** aqui você não precisa editar nada *****




/************************************************************************
				Funções do template do blog
*************************************************************************/

function axe_init() {
	global $blogparmslist;
	global $transfblogparmslist;
	global $blogparms;
	global $axedir;
	global $pluginslist;
	global $configfiledir;
	global $axe_exepath;
	global $adpages;
	
	require $configfiledir."axe_config.php";	

	// carrega módulo exclusivo do Efetividade, quando presente
	if (file_exists($configfiledir."axe_filtraads.php")) {
		include $configfiledir."axe_filtraads.php";
		inicializa_filtro_ads();
	}
		
	// confirma que diretórios do axe_config terminam com / e existem 	
	$axedir=valida_dir($axedir,"axedir");
	$blogparms["THEMESDIR"]=valida_dir($blogparms["THEMESDIR"],"THEMESDIR");
	$blogparms["THEME"]=valida_path($blogparms["THEME"],"THEME");
	$blogparms["PLUGINSDIR"]=valida_dir($blogparms["PLUGINSDIR"],"PLUGINSDIR");
	$blogparms["POSTSDIR"]=valida_dir($blogparms["POSTSDIR"],"POSTSDIR");
	$blogparms["PREVIEWDIR"]=valida_dir($blogparms["PREVIEWDIR"],"PREVIEWDIR");

	// confirma que URLs do axe_config terminam com / 	
	$blogparms["BLOGURL"]=valida_url($blogparms["BLOGURL"],"BLOGURL");
	$blogparms["THEMESPATH"]=valida_url($blogparms["THEMESPATH"],"THEMESPATH");
	$blogparms["PREVIEWSBASEURL"]=valida_url($blogparms["PREVIEWSBASEURL"],"PREVIEWSBASEURL");	

	$blogparms["PAGETITLE"] = $blogparms["BLOGTITLE"];
	$blogparms["PAGEDESC"] = "";
	$blogparms["PAGEICON"] = "";
	$blogparms["PAGEURL"] = "";
	$blogparms["NAVLINKS"] = "";
	$blogparms["BLOGROOT"] = $axedir;	
	$blogparms["BLOGINCS"] = $axe_exepath; // diretório dos executáveis	
	//$blogparms["TEMPLATEDIR"] = valida_dir($axedir.'templates/',"TEMPLATEDIR");
	chdir($blogparms["THEMESDIR"].$blogparms["THEME"]);
	set_include_path(".".PATH_SEPARATOR.$axedir);	
	$blogparms["STAGINGDIR"] = valida_dir($axedir.'staging/',"STAGINGDIR");
	$blogparms["DRAFTSDIR"] = valida_dir($axedir.'drafts/',"DRAFTSDIR");	
	$blogparms["IMPORTDIR"] = $axedir.'import-wp/lastexport/';
	$blogparms["INPUTNAME"] = 'descriptors/';	
	$blogparms["INPUTDIR"] = valida_dir($axedir.$blogparms["INPUTNAME"],"INPUTDIR");
	$blogparms["AXEVERSION"]="Axe 0.98a.3";
	$blogparms["YEAR"]=date("Y");
	$blogparms["LASTBUILDDATE"]=date("r"); // RFC 2822 e.g. Thu, 21 Dec 2000 16:01:07 +0200
	$blogparms["OLDARCHIVES"]=try_file_get_contents("oldarchives.html");
	$blogparms["SEARCH"]=try_file_get_contents("search.php");
	$blogparms["MIDAD"]=try_file_get_contents("midad.php");
	$blogparms["CENTERAD"]=try_file_get_contents("centerad.php");
	$blogparms["TOPAD"]=try_file_get_contents("topad.php");
	$blogparms["RIGHTAD"]=try_file_get_contents("rightad.php");
	$blogparms["OVERTEXTAD"]=try_file_get_contents("bodyad.php");
	if ($blogparms['EXIBIRPOPULARES']===true) {
		$blogparms["POPULARES"]=try_file_get_contents("populares.php");
	} else {
		$blogparms["POPULARES"]="";
	}	
	if (is_readable("menu.php")) $menudata=file("menu.php");
	else $menudata="";
	$blogparms["SIDEBAR"]="";
	$blogparms["MENU"]="";	
	if (strlen($blogparms['PLUGINSDIR'])>0) registra_plugins();			
	// após atribuir todos os itens no array de blogparms, gera a lista de transformação
	carrega_blogparmlist();
	if (!empty($menudata)) {
		foreach($menudata as $linha) {
			$a=explode(';;',substparms($linha));
			$a[1]=trim($a[1]);
			if (substr($linha,0,1)!="-") $blogparms["SIDEBAR"].="<li class=\"link\"><a href=\"".blogparm('BLOGURL').$a[1]."\">$a[0]</a></li>";
			if (substr($linha,0,1)=="-") $blogparms["MENU"].='<option value="" data-link="">&bull;&bull;&bull;</option>';
			else $blogparms["MENU"].="<option value=\"".blogparm('BLOGURL').$a[0]."\" data-link=\"$a[1]\">$a[0]</option>";
		}
		// gera de novo após processar o menu, que pode fazer uso das demais variáveis
		carrega_blogparmlist();
	}	
	carrega_destaques();
	setquadrorecentes();
	$blogparms=aplica_plugins('blogparms',$blogparms);	
}

function carrega_blogparmlist() {
	global $blogparmslist;
	global $transfblogparmslist;
	global $blogparms;
	foreach($blogparms as $nome => $parm) {
  		$transfblogparmslist["%%".$nome."%%"]=$parm;		
	}
}

function valida_dir($dir,$nome) {
	if (0===strlen($dir)) axe_error("Configuração: $nome indefinido ou vazio");
	if (substr($dir,-1)!="/") $dir.="/"; 
	if (!is_dir($dir)) axe_error("Configuração: diretório $dir inexistente");
	if (!is_writable($dir)) axe_error("Configuração: diretório $dir sem permissão de gravação");
	return($dir);	
}

function valida_path($dir,$nome) {
	if (0===strlen($dir)) axe_error("Configuração: $nome indefinido ou vazio");
	if (substr($dir,-1)!="/") $dir.="/"; 
	return($dir);	
}

function valida_url($url,$nome="") {
	if (0===strlen($url)) axe_error("Configuração: $nome indefinido ou vazio");
	if (substr($url,-1)!="/") $url.="/"; 
	return($url);	
}


function setpagetitle($pagetitle) {
	global $blogparms;
	$blogparms["PAGETITLE"] = removequotes($pagetitle);	
	carrega_blogparmlist();
}

function setquadrorecentes() {
	global $blogparms;
	$blogparms["QUADRORECENTES"] = try_file_get_contents(substparms("%%POSTSDIR%%")."recentes.html");	
	carrega_blogparmlist();
}

function setmonthlyindexdate($date) {
	global $blogparms;
	$blogparms["MONTHLYINDEXDATE"] = $date;	
	carrega_blogparmlist();
}

function setmonthlyindextitle($title) {
	global $blogparms;
	$blogparms["MONTHLYINDEXTITLE"] = $title;	
	carrega_blogparmlist();
}


function setnavlinks($navlinks) {
	global $blogparms;
	$blogparms["NAVLINKS"] = $navlinks;	
	carrega_blogparmlist();
}

function setpageicon($icon) {
	global $blogparms;
	$blogparms["PAGEICON"] = $icon;	
	carrega_blogparmlist();
}

function setpageurl($url) {
	global $blogparms;
	$blogparms["PAGEURL"] = $url;	
	carrega_blogparmlist();
}


function setpagedesc($description) {
	global $blogparms;
	$blogparms["PAGEDESC"] = removequotes($description);	
	carrega_blogparmlist();
}


function substparms($trecho) {
// aplica o template de parâmetros do blog a um texto
	global $transfblogparmslist;
	global $transfpost;
		//	dmsg("--> " .$trecho);

	if (function_exists("filtro_ads")) {
		// filtrar_ads 
		$year=date("Y",strtotime($transfpost["%%POSTTIME%%"]));
		$name=$transfpost["%%POSTNAME%%"];
		if (!filtro_ads($name,$year)) {
			// dmsg($year." ".$name);
			$trecho=preg_replace('/%%(BODY|MID|RIGHT|OVERTEXT)AD%%/',"",$trecho); // remove tags de anuncios
			// dmsg("--> " .$trecho);
			// dmsg($trecho);
		}	
	}

	$trecho=strtr($trecho,$transfblogparmslist);
	return $trecho;
}


function blogparm($parametro)	{
// Retorna um parâmetro sobre o blog
	global $blogparms;
	global $transfpost;
/*
	if (substr($parametro,-2)=="AD") {
		if (function_exists("filtro_ads")) {
			// filtrar_ads 
			$year=date("Y",strtotime($transfpost["%%POSTTIME%%"]));
			$name=$transfpost["%%POSTNAME%%"];
			dmsg($year,$name);
		}
	}	
*/
	$parmsg=$parametro; // para o caso de ter de mostrar na mensagem de erro
	$parametro=strtoupper(preg_replace('/%/','',$parametro));
	if (isset($blogparms[$parametro])) {
		return s_rss($blogparms[$parametro]);
	} else {
		return "Parâmetro desconhecido - $parmsg"; 
	}
}	





/************************************************************************
				Funções do template de posts
*************************************************************************/

function loadpostvars() {
// inicializa as variáveis que descrevem um post
	global $POST;
	global $blogparms;
	unset($GLOBALS['transfpost']);
	global $transfpost;
	if (isset($POST)) {
		foreach($POST as $origem => $parm) {
			$transfpost["%%".$origem."%%"]=$parm;		
		}
		if (isset($transfpost["%%POSTTAGS%%"])) {
			$transfpost["%%POSTTAGS%%"]=preg_replace('/[[:blank:]]*/',"",$transfpost["%%POSTTAGS%%"]).',';
			$transfpost["%%POSTTAGS%%"]=preg_replace('/([^,]*),/',"<a href=\"".blogparm('BLOGURL')."tag-\\1.html\">\\1</a>,",$transfpost["%%POSTTAGS%%"]);
			$transfpost["%%POSTTAGS%%"]=preg_replace('/,$/',"",$transfpost["%%POSTTAGS%%"]);
			$transfpost["%%POSTTAGS%%"]=preg_replace('/,/',", ",$transfpost["%%POSTTAGS%%"]);						
		} else {
			$transfpost["%%POSTTAGS%%"]="";
		}
		$transfpost["%%BODYAD%%"]=try_file_get_contents("bodyad.php");
		if (strlen(trim($transfpost["%%POSTOLDCOMMENTS%%"]))<15) $transfpost["%%POSTOLDCOMMENTS%%"]="Ninguém quer saber!";
		else if (strlen(trim($transfpost["%%POSTOLDCOMMENTS%%"]))>40000) {
			$transfpost["%%POSTOLDCOMMENTS%%"]=substr($transfpost["%%POSTOLDCOMMENTS%%"],0,40000);
			$divpos=strrpos($transfpost["%%POSTOLDCOMMENTS%%"],"</div>");
			$transfpost["%%POSTOLDCOMMENTS%%"]=substr($transfpost["%%POSTOLDCOMMENTS%%"],0,$divpos+5);
		}
		if (!isset($transfpost["%%POSTICON%%"])) {
			if (!isset($transfpost["%%POSTMETAMYSTAMP%%"])) {
				$transfpost["%%POSTICON%%"]=blogparm('BLOGURL').blogparm("THEMESPATH").blogparm("THEME")."posticons/noicon.jpg";
			} else {
				$transfpost["%%POSTICON%%"]=$transfpost["%%POSTMETAMYSTAMP%%"];
				if (substr($transfpost["%%POSTICON%%"],0,5)!="http:") $transfpost["%%POSTICON%%"]="http://static.efetividade.net/img/".$transfpost["%%POSTICON%%"];
			}	
		}
		if (trim(strtoupper($transfpost["%%POSTICON%%"]) != "NEWS")) setpageicon($transfpost["%%POSTICON%%"]);
		else setpageicon(blogparm('BLOGLOGO'));
		if (!isset($transfpost["%%POSTTIME%%"])) {
			$transfpost["%%POSTTIME%%"]=date("r");
		}
		$transfpost["%%POSTDATE%%"]=date("j/m/Y",strtotime($transfpost["%%POSTTIME%%"])); // RFC 2822 e.g. Thu, 21 Dec 2000 16:01:07 +0200
		$transfpost["%%POSTDATEFEED%%"]=date("r",strtotime($transfpost["%%POSTTIME%%"])); // RFC 2822 e.g. Thu, 21 Dec 2000 16:01:07 +0200
		if (blogparm('YEARLY')=="01") {
			$transfpost["%%POSTANOMES%%"]=date("Y/",strtotime($transfpost["%%POSTTIME%%"])).blogparm('YEARLY'); 
		} else {
			$transfpost["%%POSTANOMES%%"]=date("Y/m",strtotime($transfpost["%%POSTTIME%%"])); 
		}	
		if (strlen(blogparm('YEARLY'))=="01") {
			$transfpost["%%POSTNAME%%"]=preg_replace('|(..../)..(/.*)$|',"\\1aBaB\\2",$transfpost["%%POSTNAME%%"]);
			$transfpost["%%POSTNAME%%"]=preg_replace('/aBaB/',blogparm('YEARLY'),$transfpost["%%POSTNAME%%"]);
		}		
		if (!isset($transfpost["%%POSTURL%%"])) {
			$transfpost["%%POSTURL%%"]=$transfpost["%%POSTANOMES%%"]."/".$transfpost["%%POSTNAME%%"].".html";
		} else {
			$caminho=preg_replace('/^https?:\/\/[^\/]*/',"",$transfpost["%%POSTURL%%"]);
			if (substr_count($caminho,'/')<3) $caminho=$transfpost["%%POSTANOMES%%"].$caminho;
			$transfpost["%%POSTURL%%"]=rtrim(ltrim($caminho,'/'),'/').'.html';
		}
		$transfpost["%%POSTURL%%"]=preg_replace('/(....\/..)\/..(\/.*)/',"\\1\\2",$transfpost["%%POSTURL%%"]);
		setpageurl(blogparm('BLOGURL').blogparm('POSTSURLPREFIX').$transfpost["%%POSTURL%%"]);
		$transfpost["%%POSTSHORT%%"]=cutonword(strip_tags(corrigehtml($POST['POSTBODY'])),250);		
		if (empty($transfpost["%%DESC%%"])) {
			setpagedesc($transfpost["%%POSTSHORT%%"]);
		} else {
			setpagedesc($transfpost["%%DESC%%"]);
		}	
		$transfpost["%%POSTFEAT%%"]=buscaparagrafos($POST['POSTBODY'],6,1000);
		$transfpost["%%POSTMID%%"]=trim(cutonword(strip_tags(corrigehtml($POST['POSTBODY'])),350));
		$transfpost["%%POSTMID%%"]=preg_replace('/[[:cntrl:]]/',"",$transfpost["%%POSTMID%%"]);		
		//$transfpost["%%POSTMID%%"]=strip_tags($transfpost["%%POSTMID%%"]);				
		//$transfpost["%%POSTBODY%%"]=paragrafos(corrigehtml($POST['POSTBODY']));		
		$transfpost["%%POSTNEWSBODY%%"]=corrigehtml($POST['POSTBODY']);		
		if (!isset($transfpost['POSTAUTHOR'])) {
			$transfpost['%%POSTAUTHOR%%']=blogparm('BLOGOWNER');
			$transfpost['%%POSTAUTHORURL%%']=blogparm('BLOGOWNERURL');
			$transfpost['%%POSTAUTHORTWITTER%%']=blogparm('BLOGOWNERTWITTER');		
		}
		$transfpost["%%POSTBIO%%"]=authorbio($transfpost['%%POSTAUTHOR%%']);		
		if (!isset($transfpost['POSTTWIT'])) {
			$transfpost['%%POSTTWIT%%']=$transfpost['%%POSTTITLE%%'];
		}
		$transfpost['%%POSTTWIT%%']=$transfpost['%%POSTTWIT%%']." %CR%%CR%".blogparm('BLOGURL').blogparm('POSTSURLPREFIX').$transfpost['%%POSTURL%%'];		
		// %25CR%25 é o mesmo que %CR%, mas url-encoded
		$transfpost['%%POSTTWITTHIS%%']=preg_replace('/%25CR%25/',"\n",'http://twitter.com/home?status='.urlencode(html_entity_decode("☞ ".$transfpost['%%POSTTWIT%%'])."\nvia @".$transfpost['%%POSTAUTHORTWITTER%%']));
		$transfpost['%%POSTSHARETHIS%%']="http://www.facebook.com/sharer/sharer.php?u=".blogparm('BLOGURL').blogparm('POSTSURLPREFIX').$transfpost['%%POSTURL%%'];
		$transfpost['%%DESTAQUES%%']=quadro_destaques();
	} else {
		// se ainda não foi lido nenhum post
		$transfpost["%%POSTTITLE%%"]=blogparm('BLOGTITLE')." ".blogparm('BLOGURL');		
	}
	setnavlinks("");
	$transfpost=aplica_plugins('postvars',$transfpost);	
}


function substpostvars($trecho) {
	global $POST;
	global $transfpost;
	$trecho=preg_replace('/(%%#[^#]*#%%)/',"<!-- \\1 -->",$trecho); // remove itens que os plugins nao processaram
	$trecho=strtr($trecho,$transfpost);
	return $trecho;
}

function authorbio($author) {
	$authorfile=substparms('%%BLOGROOT%%')."authors/".normaliza($author).".html";
	$bio=try_file_get_contents($authorfile);
	if(empty($bio)) $bio="Este artigo foi publicado em ".substpostvars("%%POSTDATE%%")." por $author.";
	return trim($bio);
}


/************************************************************************
				Funções de geração de drafts a partir do staging
*************************************************************************/


function gera_draft($arquivo,$quiet=false) {
// gera um draft a partir de um arquivo de entrada fornecido pelo usuario
	global $MYPOST;
	global $noold;
	global $strict;
	decho("Vou ler o arquivo de entrada: ".blogparm('STAGINGDIR').$arquivo);
	if (!is_file(blogparm('STAGINGDIR').$arquivo)) {
		axe_error('Não existe o arquivo '.blogparm('STAGINGDIR').$arquivo,E_USER_ERROR);
		die;
	} else {	
		$filein=blogparm('STAGINGDIR').$arquivo;
		$linhas=file($filein);
		$linhas=preg_replace("/'/","&#39;",$linhas);
		$linhas=str_replace("$","&#36;",$linhas);
		if (!$strict) {
			$MYPOST['POSTTITLE']=$linhas[0];
			$inicio=1;
			if (strtolower(substr($linhas[1],0,5))=="tags:") {
				$MYPOST['POSTTAGS']=substr($linhas[1],5);
				$inicio=2;
			}
			$MYPOST['POSTTAGS']=preg_replace('/[[:space:]]/',"",$MYPOST['POSTTAGS']);		
		} else {
			// a opção strict não dá nenhum significado inicial às 2 primeiras
			// linhas do arquivo de entrada. Para definir título e tags, precisa
			// referenciar explicitamente @@POSTTITLE: e @@POSTTAGS:
			$inicio=0;	
		}
		$t="";
		for ($i=$inicio; $i < count($linhas); $i++) {
			if (substr($linhas[$i],0,2)=="@@") break;
			$t.=$linhas[$i];
		}
		$MYPOST['POSTBODY']=$t;
		for ($i=$i; $i < count($linhas); $i++) {
			if (substr($linhas[$i],0,13)=="@@OLDCOMMENTS") break;
			$t=$linhas[$i];
			$chave=strtoupper(trim(preg_replace('/^@@([^:]*):.*/',"\\1",$t)));
			$valor=trim(preg_replace('/^@@[^:]*:(.*)/',"\\1",$t));
			$MYPOST[$chave]=$valor;
		}
		$t="";
		if (substr($linhas[$i],0,13)=="@@OLDCOMMENTS") {
			$i++;
			for ($i=$i; $i < count($linhas); $i++) {
				$t.=$linhas[$i];
			}
			$MYPOST['OLDCOMMENTS']=$t;
		}
		$saida="<?php\n";	
		foreach ($MYPOST as $key => $value) {
			if ($key=="POSTBODY") {
				$saida.='$POST["POSTBODY"]= <<< endPBPBPBaxe'."\n";
				$saida.=$value."\n";
				$saida.='endPBPBPBaxe;'."\n";
			} elseif ($key=="OLDCOMMENTS") {
				$saida.='$POST["POSTOLDCOMMENTS"]= <<< endPCPCPCaxe'."\n";
				$saida.=$value."\n";
				$saida.='endPCPCPCaxe;'."\n";		
			} else $saida.="\$POST[$key]='".trim($value)."';\n";	
		}
		$saida.="?>";
		if (isset($MYPOST['POSTTITLE'])) {
			$arquivo=normaliza($MYPOST['POSTTITLE']).".php";		
		}	
		$arquivo=preg_replace('/\.[^.]*$/',".php", $arquivo);
		if (isset($MYPOST['POSTTIME']) && isset($MYPOST['POSTNAME'])) $arquivo="upd_".$arquivo;
		else if (isset($MYPOST['CRON'])) {
			$horario=preg_replace('/[^0-9]/',"-",trim($MYPOST['CRON']));
			$horario=preg_replace('/-+/',"-",$horario);
			dmsg($horario);
			$horario=preg_replace('/^([0-9][-_])/',"0\\1",$horario);
			$horario=preg_replace('/-([0-9][-_])/',"-0\\1",$horario);
			$horario=preg_replace('/-([0-9])$/',"-0\\1",$horario);
			dmsg($horario);
			$arquivo="@".$horario."_".$arquivo;
			$h=explode("-",$horario);
			dmsg("** CRON ** Artigo será agendado para as $h[1]:$h[2] do dia $h[0].");
		} else if (isset($MYPOST['PRI'])) {
			if($MYPOST['PRI']<10) $MYPOST['PRI']=10+$MYPOST['PRI'];
			$arquivo="PRI".$MYPOST['PRI']."_$arquivo";
			dmsg("** PRIORIZAÇÃO ** Artigo recebeu prioridade ".$MYPOST['PRI'].".");			
		}
		gravaarquivo(basename(blogparm('DRAFTSDIR')).'/'.$arquivo,$saida,true);
		if (!$noold) move_oldfile($filein);
		decho("Gravei: ".blogparm('DRAFTSDIR').$arquivo);
		return $arquivo;
	}	
}

function hack_the_header($header) {
// an ugly hack to substitute some header fields on local tests
// see also references to @@PAGEICON@@ on single, indexes and monthly
	global $blogparms;
	if (!empty($blogparms['CSSFIXEDURL'])) {
		$header=preg_replace('/%%BLOGURL%%%%THEMESPATH%%%%THEME%%/','%%CSSFIXEDURL%%',$header);
	}
	return $header;
}

function first_image($body) {
// retorna a URL da primeira imagem referenciada em HTML no $body
    $s="";
	if (preg_match('/<img/i',$body)) {
		$s=substr($body,stripos($body,"<img"));
		$s=substr($s,0,stripos($s,">"));
		$s=preg_replace('/.*src="?([^"[:blank:]]*)"?.*/',"\\1",$s);
	}	
	return $s;
}



/************************************************************************
				Funções de indexação de tags
*************************************************************************/


function cria_tagindex($tag,$force=false) {
// gera os indices para UMA tag
	global $tritem;
	global $sparsedebug;
	$postsfilename=blogparm('INPUTDIR').'tags/'."catalog-$tag.txt";
	$indexfilename=blogparm('POSTSDIR')."tag-$tag.html";
	$gerar=(!is_file($indexfilename));
	if (!$gerar) {
		$gerar= ((filemtime($postsfilename) > filemtime($indexfilename)));
	}
	if ($force || !is_file($indexfilename) || $gerar) {  
		$postsfiles=file($postsfilename,FILE_IGNORE_NEW_LINES);
		sort($postsfiles);
		$contaitems=0;
		$contafeats=0;
		$containdexes=0;
		for ($i=count($postsfiles)-1; $i >=0; $i--) {
			if (0 == $contaitems % blogparm('NUMPOSTSCOVER')) {
				setpagetitle("Tag: ".$tag." - ".blogparm('%%BLOGTITLE%%'));
				$pageurl=blogparm('BLOGURL').preg_replace('/'.basename(blogparm('POSTSDIR')).'\//',"/",$nome);
				setpageurl($pageurl);			
				$item=cover_header('tags');
				$item.="<h1 style=\"margin-top:25px;\">Artigos marcados com a tag $tag</h1>";
			}	
			$fpost=$postsfiles[$i];
			unset($GLOBALS['POST']);
			global $POST;
			//$k=preg_split('/;;/',$fpost);
			//$myimportfile=blogparm('INPUTDIR').'posts/'.$k[1];
			//include $myimportfile;
			loadpostvars_fromcatalog($fpost);		
			if(strtoupper($POST['POSTICON'])=="NEWS") {
				$item.=cover_news('tags');
			}	
			else if ($contafeats<blogparm('NUMFEATSCOVER')) { 
				$item.=cover_feature('tags');
				$contafeats++;
			}	
			else $item.=cover_post('tags');
			$contaitems++;
			if ((0 == $contaitems % blogparm('NUMPOSTSCOVER')) || ($i==0)) {
				$numindex=$containdexes+1;
				$previndex=$numindex-1;
				$proxindex=$numindex+1;
				$prevlink='tag-'.$tag."-".$previndex.".html";
				$proxlink='tag-'.$tag."-".$proxindex.".html";
				if ($containdexes==1) $prevlink='tag-'.$tag.".html";
				$navlinks="";
				if ($i>0) $navlinks='<span class="next"><a href="'.blogparm('BLOGURL').$proxlink.'" rel="next">&larr; Mais antigos</a></span>';
				if($containdexes>0) $navlinks.='<span class="prev"><a href="'.blogparm('BLOGURL').$prevlink.'" rel="prev">Mais recentes &rarr;</a></span>';
				setnavlinks($navlinks);
				$item.=cover_footer('tags');
				$nome="tag-".$tag."-".$numindex.".html";
				if ($containdexes==0) $nome=basename(blogparm('POSTSDIR')).'/'."tag-".$tag.".html";
				$pageurl=blogparm('BLOGURL').preg_replace('/'.basename(blogparm('POSTSDIR')).'\//',"/",$nome);
				setpageurl($pageurl);
				$sparsedebug=1;			
				gravaarquivonaraiz($nome,$item);
				$sparsedebug=0;			
				$containdexes++;
				$item="";
			}	
		}
	}	
	return $item;
}

function rebuild_tags($force=false) {
// reconstroi todos os indices de tags que estiverem desatualizados
	$tagfilelist=glob(blogparm('INPUTDIR').'tags/catalog-*.txt');
	for ($i=count($tagfilelist)-1; $i >=0; $i--) {
		$fpost=$tagfilelist[$i];
		$tag=preg_replace('/.*catalog-([^.]*)\.txt/',"\\1",$fpost);
		cria_tagindex($tag,$force);
	}
}


/************************************************************************
				Funções de formatação e tratamento de strings
*************************************************************************/

function corrigehtml($trecho) {
	$transf = array("<i>" => "<em>", "</i>" => "</em>", 
			"<*>" => "<span class=\"marker\">", "</*>" => "</span>", 
			"<b>" => "<strong>", "</b>" => "</strong>",
			"<tt>" => "<code>", "</tt>" => "</code>",
			"<pre>" => "<pre><code>", "</pre>" => "</code></pre>" );
	$trecho=strtr($trecho,$transf);
	$trecho=preg_replace('/\[adsense\]/',substpostvars("%%BODYAD%%"),$trecho);
	$trecho=preg_replace('/\[\/img\]/',"",$trecho);
	$trecho=preg_replace('/\[(img[^]]*)]/',"<\\1><br>",$trecho);
	// rodapés com [@@rod:xxxxtextoxxxx]
	while (preg_match('/\[@@rod:[^]]*\]/',$trecho)) {
		//inneficcient as hell
		$contarod++;
		$rod_ref=$contarod."_".normaliza(substpostvars("%%POSTTITLE%%"));
		preg_match('/\[@@rod:([^\]]*)\]/',$trecho,$m);
		$rodapes[$rod_ref]=$m[1];
		$trecho=preg_replace('/\[@@rod:[^\]]*\]/',"<sup><a title=\"".removequotes(strip_tags($m[1]))."\" name=\"ret-$rod_ref\"class=rodape_link href=\"#$rod_ref\">$contarod</a></sup>",$trecho,1);		
	}
	$contarod=0;
	if (count($rodapes)>0) {
		$trecho.="<div id=\"rodapes\"><div id=\"rodapes_halfline\">&nbsp;</div><ol>";
		foreach ($rodapes as $key => $value) {
			$contarod++;
			$trecho.="\n<li><a class=rodape_numero name=\"$key\">&nbsp;</a>$value <a href=\"#ret-$key\">↩</a></p>";
		}
		$trecho.="\n</ol></div><!--id-rodapes-->";
	}
	
		
	// chame aqui algum filtro externo desejado (exemplo: MarkDown)
	return $trecho;
}

function removequotes($s) {
	return preg_replace('/"/',"&quot;",$s);
}

function button($texto,$url) {
	return("<a href=\"$url\" class=\"button\">$texto</a>");
}



function buscaparagrafos($texto,$quantos=3,$limite=1000) {
		//trim(cutonword(strip_tags(corrigehtml($POST['POSTBODY'])),350));
		//:cntrl:
	$texto=strip_tags(corrigehtml($texto));
	$texto=substr($texto,0,$limite);
	$texto=preg_replace('/\n(\n)+/',"\n\n",$texto);
	$pars=explode("\n\n",$texto);
	$texto="";
	for ($i=0;$i<=$quantos;$i++) {
		if ($i < count($pars)-1) $texto.="<p>".$pars[$i]."\n\n";
	}	
	$postbutton="(<a href=\"".blogparm('POSTSURLPREFIX').substpostvars('%%POSTURL%%')."\">&hellip;</a>) ".button("leia mais",blogparm('POSTSURLPREFIX').substpostvars('%%POSTURL%%'));
	return $texto.$postbutton;
}



function insereanunciocentral($texto) {
		//trim(cutonword(strip_tags(corrigehtml($POST['POSTBODY'])),350));
		//:cntrl:
		// if defined... blogparm('CENTERAD')

  global $blogparms;
  if (blogparm('CENTERAD')!="") {	
	$pars=explode("\n",$texto);
	$cp=count($pars);
	if ($cp>50) {
	    // primeiro so conta
		$i=20;
		$contaparstotal=0;
		while ($i<$cp-10) {
		      $i++;
		  if ((substr($pars[$i],0,3)=="<p>") || (substr($pars[$i],0,4)=="<li>")) {
		      $contaparstotal++;
		  }
		}
		// agora modifica
		$i=20;
		$contapars=0;
		$inseriu=0;
		while (($i<$cp-10) && ($inseriu==0)) {
		      $i++;
		  if ((substr($pars[$i],0,3)=="<p>") || (substr($pars[$i],0,4)=="<li>")) {
		      $contapars++;
			  // tanto o p quanto o li contam, mas só o p recebe a inserção
			  if (($contapars>=14) && ($contapars<$contaparstotal-5) && (substr($pars[$i],0,3)=="<p>")) {
				$inseriu=1;
				$pars[$i]="<!-- CENTERAD $i -->\n".$blogparms['CENTERAD'].$pars[$i];		
			  }
		  }
		}
	}
	$texto=implode("\n",$pars);
/*	
	for ($i=0;$i<=$quantos;$i++) {
		if ($i < count($pars)-1) $texto.="<p>".$pars[$i]."\n\n";
	}	
	$postbutton="(<a href=\"".blogparm('POSTSURLPREFIX').substpostvars('%%POSTURL%%')."\">&hellip;</a>) ".button("leia mais",blogparm('POSTSURLPREFIX').substpostvars('%%POSTURL%%'));
*/	
  }
  return $texto;
}




function cutonword($trecho,$len) {
// corta uma string na primeira frase concluída após o 80º caracter --
// ou no limite de uma palavra, dentro de um tamanho máximo
	$trecho.=" ";
	if(strlen($trecho)<=$len) return $trecho;
	$trecho=strip_tags(preg_replace('/[[:cntrl:]]/'," ",$trecho));
	$trecho=preg_replace('/[[:blank:]][^[:blank:]]+$/','',substr($trecho,0,$len-5));
	$trecho=preg_replace('/([.?!])[^.?!]*$/','\\1',$trecho);
	$postbutton="";
	if ($len <= 250) {
		$trecho=preg_replace('/(.{79}[^.?!]*[.?!]) .*$/','\1',substr($trecho,0,$len-5));
		//$postbutton=" (...)";
	}	
	else $postbutton="(<a href=\"".blogparm('POSTSURLPREFIX').substpostvars('%%POSTURL%%')."\">&hellip;</a>) ".button("leia mais",blogparm('POSTSURLPREFIX').substpostvars('%%POSTURL%%'));
	$trecho=preg_replace('/[[:cntrl:]]/',"",$trecho);
	return trim($trecho.$postbutton);
}

function s_rss($saida)	 {
// Sanitiza uma string de saída para o feed rss
	$saida=htmlentities($saida, ENT_QUOTES|ENT_DISALLOWED|ENT_XML1, 'UTF-8');
	return $saida;
}	


function normaliza($string,$extra="") {
// tks allixsenos 
    $table = array(
        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', ' '=>'-'
    );   
    $string=strtolower(strtr(trim($string), $table));
    $string=preg_replace('/&#[^;]{1,4};/',"",$string);
    $expr="/[^-A-Za-z0-9$extra]/";
    $string=preg_replace($expr,"",$string);
    $string=preg_replace('/-+/',"-",$string);
    return $string;
}


function preg_quebras($quebras) {
// chamada pela função paragrafos()
	$conv=str_replace("\n", "<xXzZ />", $quebras[0]);
	return($conv);
}

function paragrafos($trecho) {
// insere <p> onde houver dupla quebra.
// Tks André Tobias && http://ma.tt/2003/01/updated-autop/
	if ( (blogparm('BREAKLINES') === false) || (trim($trecho) === '') ) return '';
	$trecho = $trecho . "\n";
	$blocos_pre = array();
	// remove o conteúdo de todos os blocos <pre> e guarda num array, para reinserir depois
	if ( strpos($trecho, '<pre') !== false ) {
		$trecho_sub = explode( '</pre>', $trecho );
		$par_end = array_pop($trecho_sub);
		$trecho = '';
		$i = 0;
		foreach ( $trecho_sub as $trecho_part ) {
			$start = strpos($trecho_part, '<pre');
			if ( $start === false ) {
				$trecho .= $trecho_part;
				continue;
			}
			$name = "<pre bloco-$i></pre>";
			$blocos_pre[$name] = substr( $trecho_part, $start ) . '</pre>';
			$trecho .= substr( $trecho_part, 0, $start ) . $name;
			$i++;
		}
		$trecho .= $par_end;
	}
	$pipe="|";	
	// troca quebras sucessivas em HTML por ASCII
	$trecho = preg_replace('|<br />\s*<br />|', "\n\n", $trecho);
	// insere uma quebra antes de cada bloco, 2 quebras depois
	$bloqueiras="p;ul;ol;li;dd;dt;dl;div;hr;blockquote;h1;h2;h3;h4;h5;h6;pre;fieldset;form;option;select;map;area;table;tr;td;thead;tfoot;caption;tbody;math;style";
	$bloqueiras="(?:".strtr($bloqueiras,";",$pipe).")";
	$bigblocks='form|div|address';
	$smallblocks='pre|p|ul|ol|li|td|div|dl|dd|dt|th';
	$trecho = preg_replace('!(<' . $bloqueiras . '[^>]*>)!', "\n$1", $trecho);
	$trecho = preg_replace('!(</' . $bloqueiras . '>)!', "$1\n\n", $trecho);
	// substitui quebras MS-DOS por quebras UNIX, trata quebras múltiplas (2 ou +)	
	$trecho = str_replace(array("\r\n", "\r"), "\n", $trecho); 
	$trecho = preg_replace("/\n\n+/", "\n\n", $trecho); 

	// divide os parágrafos (.*\n\n) em um array
	$trechos = preg_split('/\n\s*\n/', $trecho, -1, PREG_SPLIT_NO_EMPTY);
	$trecho = '';

	// marca todo paragrafo como <p>.*</p>	
	foreach ( $trechos as $paragrafo )
		$trecho .= '<p>' . trim($paragrafo, "\n") . "</p>\n";

	// remove parágrafos vazios
	$trecho = preg_replace('|<p>\s*</p>|', '', $trecho);
	
	// casos especiais em que o <p> precisa ser movido ou removido
	$trecho = preg_replace(';<p>([^<]+)</('.$bigblocks.')>;',"<p>$1</p></$2>", $trecho);
	$trecho = preg_replace(";<p>(<li.+?)</p>;","$1",$trecho);
	$trecho = preg_replace(';<p><blockquote([^>]*)>;i',"<blockquote$1><p>",$trecho);
	$trecho = str_replace('</blockquote></p>','</p></blockquote>', $trecho);
	$trecho = preg_replace(';<p>\s*(</?'.$bloqueiras.'[^>]*>)\s*</p>;',"$1",$trecho);
	$trecho = preg_replace(';<p>\s*(</?'.$bloqueiras.'[^>]*>);',"$1",$trecho);
	$trecho = preg_replace(';(</?'.$bloqueiras.'[^>]*>)\s*</p>;',"$1", $trecho);
	// remonta o trecho
	$trecho = preg_replace_callback(';<(script|style).*?<\/\\1>;s','preg_quebras',$trecho);
	$trecho = preg_replace(';(?<!<br />)\s*\n;',"<br />\n",$trecho); 
	$trecho = str_replace('<xXzZ />',"\n", $trecho);
	$trecho = preg_replace(';(</?'.$bloqueiras.'[^>]*>)\s*<br />;',"$1",$trecho);
	$trecho = preg_replace(';<br />(\s*</?(?:'.$smallblocks.')[^>]*>);','$1',$trecho);
	$trecho = preg_replace( ";\n</p>$;",'</p>', $trecho );
	if (0<count($blocos_pre) )
		$trecho = str_replace(array_keys($blocos_pre), array_values($blocos_pre), $trecho);
	return $trecho;
}


/************************************************************************
				Funções de agendamento e priorização de posts
*************************************************************************/

function verifica_agendamentos() {
// verifica se no draftsdir tem algum artigo agendado para ser postado hoje, e
// se o horário dele já chegou. Se sim, retorna o nome do PRIMEIRO 
// artigo nesta situação.
	$arquivos=glob(blogparm('DRAFTSDIR')."@*.php");
	if (isset($arquivos[0])) {
		$horario=preg_split('/[-_]/',basename($arquivos[0]));
		$horario[0]=preg_replace('/@/',"",$horario[0]);
		if ((0+date("j")) == (0+$horario[0])) {
			if (((0+date("G")) > (0+$horario[1])) || (((0+date("G")) >= (0+$horario[1])) && ((0+date("i")) >= (0+$horario[2])))) {
				return(basename($arquivos[0]));
			}
		}
	}
}

function simula_prioridades($hora,$intervalo) {
// mostra em que horários seriam postados cada um dos posts priorizados 
// presentes no DRAFTSDIR, a partir da $hora, a cada $intervalo minutos.
	$minutos=0;
	$arquivos=glob(blogparm('DRAFTSDIR')."PRI??_*.php");
	foreach ($arquivos as $item) {
		$h=$hora+floor($minutos/60);
		$m=$minutos % 60;
		echo sprintf("%02d:%02d %-60s\n",$h,$m,$item);
		$minutos += $intervalo;
	}
}

function verifica_prioritarios() {
// verifica se no draftsdir tem algum artigo com prioridade definida para ser postado
// e retorna o nome do PRIMEIRO (ou seja, o de menor prioridade) artigo nesta situação.
	$arquivos=glob(blogparm('DRAFTSDIR')."PRI??_*.php");
	if (isset($arquivos[0])) return(basename($arquivos[0]));
}


function agenda_post($dia,$hora,$minuto,$post) {
// agenda um draft para ir ao ar no dia e horário mencionado
	$arquivo=blogparm('DRAFTSDIR').$post;
	if (!is_file($arquivo)) {
		axe_error('Não existe o arquivo '.$arquivo,E_USER_ERROR);
		die;
	}
	$novonome=blogparm('DRAFTSDIR')."@$dia-$hora-$minuto_$post";
	rename($arquivo,$novonome);
	dmsg("Agendei post para dia $dia ($hora:$minuto): $post");
}


/************************************************************************
				Funções de notificação
*************************************************************************/

function verifica_notificacoes() {
// verifica se no draftsdir tem alguma notificacao agendada para ser postada hoje, e
// se o horário dela já chegou. Se sim, imprime na saída padrão o conteúdo da PRIMEIRA 
// notificação nesta situação.
	$arquivos=glob(blogparm('DRAFTSDIR')."N@*.txt");
	if (isset($arquivos[0])) {
		$horario=preg_split('/[-_]/',basename($arquivos[0]));
		$horario[0]=preg_replace('/N@/',"",$horario[0]);
		//echo date("j")." ".date("G")." ".date("i")."\n";
		//echo "$horario[0] $horario[1] $horario[2]\n";
		if ((0+date("j")) == (0+$horario[0])) {
			if (((0+date("G")) > (0+$horario[1])) || (((0+date("G")) >= (0+$horario[1])) && ((0+date("i")) >= (0+$horario[2])))) {
				echo("@@NOTIFY:".trim(file_get_contents($arquivos[0])));
				rename($arquivos[0],blogparm('DRAFTSDIR').'old/'.basename($arquivos[0]));
			}
		}
	}
}

function sched_notify_post() {
// agenda as notificações de UM post
	global $notifyparms;
	if (isset($notifyparms['NOTIFYCMD'])) {
		$hoje=date("j");
		$novo="";
		$amanha=date("j",time()+86400);
		$i=0;
		$q=0;
		$h=date("G")+0;
		$m=date("i")+0;
		$txt=substpostvars('%%POSTTWIT%%');
		foreach($notifyparms['NOTIFYTIMES'] as $twh) {		
			$hh=0+floor($twh/100);
			$hm=0+$twh%100;
			// if (($hh>$h) || (($hh==$h) && ($hm>=$m))) {
			if (1==1) {
				$q++;
				// novo esquema multilinhas, não usa mais os "notifymsgs" do arquivo conf
				sched_one_notification($amanha,$hh,$hm,"%S$q%$txt");
				// sched_one_notification($hoje,$hh,$hm,"%S$q%$txt");
				/*
				if ($novo=="") {
					$novo="Novo post: ";
					sched_one_notification($hoje,$hh,$hm,"$novo$txt");
				}
				else {
					sched_one_notification($hoje,$hh,$hm,$notifyparms['NOTIFYMSGS'][$i]."$txt");
				}	
				*/
			}
			$i++;
		}
	}
}

function sched_one_notification($dia,$hora,$minuto,$texto) {
// agenda UMA notificacao para ir ao ar no dia e horário mencionado
	$notifyid=normaliza(substr($texto,0,30));
	$dia=sprintf("%02d",$dia);
	$hora=sprintf("%02d",$hora);
	$minuto=sprintf("%02d",$minuto);
	$novonome=blogparm('DRAFTSDIR')."N@$dia-$hora-".$minuto."_$notifyid.txt";
	file_put_contents($novonome,$texto);	
	dmsg("Agendei notificação para dia $dia ($hora:$minuto): $texto");
}


function do_notify_post($texto) {
// executa uma notificação de post - esta função nunca é chamada na configuração default do Axe
	global $notifyparms;
	if (isset($notifyparms['NOTIFYCMD'])) {	
		system(escapeshellcmd($notifyparms['NOTIFYCMD']."'$texto'"));
	}
}




/************************************************************************
				Funções do quadro de destaques
*************************************************************************/

function carrega_destaques() {
// lê e ordena aleatoriamente o catálogo da tag destaques
	global $DESTAQUE;
	$catdestaque=blogparm('INPUTDIR')."tags/catalog-destaques.txt";
	if(is_file($catdestaque)) {
		$DESTAQUE=file($catdestaque);	
		foreach($DESTAQUE as $k => $v) {
			$DESTAQUE[$k]=rand(0,10000).";;".$v;
		}	
	}
}

function quadro_destaques() {
	global $DESTAQUE;
	$r=""; //count($DESTAQUE);
	if(count($DESTAQUE)>6) {
		$randkeys=array_rand($DESTAQUE,3);
		$inicio=0; // rand(0,count($DESTAQUE)-3);
		$r='<div class="quadrodestaque">';
		for($i=$inicio;$i<=$inicio+2;$i++) {
			$l=preg_split('/;;/',$DESTAQUE[$randkeys[$i]]);
			$l[2]=preg_replace('/\.php$/',".html",$l[2]);
			$linha='<div class=umdestaque><a href="'.blogparm('BLOGURL').$l[2].'">';
			$linha.='<img style="width:100%" src="'.$l[6].'"><div style="legendadestaque">'.$l[4].'</div></a></div>';			
		  	$r.=$linha;
		}
		$r.='</div>';
	}
	return $r;
}



/************************************************************************
				Funções de tratamento de arquivos
*************************************************************************/

function try_file_get_contents($file) {
	$s="";
	if (is_readable($file)) $s=file_get_contents($file);
	return $s;
}


function criasymlink($nome,$numero) {
// cria um symlink numérico pros posts importados do WordPress
	$dir=blogparm('POSTSDIR')."links/";
	$link=$dir.'link-'.$numero.".html";
	if (is_link($link)) {
    	unlink($link);
	}
    symlink(blogparm('POSTSDIR').$nome, $link);
    decho("Criei o link $link para $nome"); 
    $redir=$dir.'post-'.$numero.".php";
    $linha='<?php header("HTTP/1.1 301 Moved Permanently"); Header("Location: '.blogparm('BLOGURL').$nome.'"); ?>'; 
    file_put_contents($redir, $linha, LOCK_EX);
    decho("Criei o redirect $redir");     // para ".blogparm('BLOGURL')."$nome"); 
}

function apagasymlink($nome,$numero) {
// apaga symlink numérico de post importado do WordPress (ao removê-lo)
	$dir=blogparm('POSTSDIR')."links/";
	$link=$dir.'link-'.$numero.".html";
	if (is_link($link)) {
    	unlink($link);
    	decho("Apaguei o link $link de $nome"); 
	}
    $redir=$dir.'post-'.$numero.".php";
	if (is_link($redir)) {
    	unlink($redir);
    	decho("Apaguei o redirect $redir de $nome"); 
	}
	
}

function move_oldfile($fullpath) {
// cria um symlink numérico pros posts importados do WordPress
	global $quiet;
	$dest=preg_replace('/\/([^\/]+)$/',"/old/\\1",$fullpath);
	rename($fullpath, $dest);
	decho("Renomeei o arquivo de entrada para $dest");
}


function gravaarquivonaraiz($nome,$conteudo) {
// grava um arquivo atomicamente na raiz do blog (para indices, tags, feed)
	global $quiet;
	global $sparsedebug;
	static $contamensagens;
	$gravoumesmo=false;
	$dir=blogparm('POSTSDIR');
	$nome=basename($nome);
	$gravar=true;
	$fp = fopen($dir.$nome.".temp", 'w');
	if ($fp) {
		if (fwrite($fp, $conteudo)) {
			if (fclose($fp)) {
				if (rename($dir.$nome.".temp", $dir.$nome)) {
					$gravoumesmo=true;
					$mostramensagem=!$quiet;
					if ($sparsedebug==1) {
						if (($contamensagens % 10)>0) $mostramensagem=false;
						$contamensagens++;
					}	
					if ($mostramensagem) decho("Gravei o arquivo ".$nome);
				} else axe_error('Não consegui renomear '.$nome,E_USER_ERROR);	
			} else axe_error('Não consegui fechar '.$nome,E_USER_ERROR);
		} else axe_error('Não consegui gravar '.$nome,E_USER_ERROR);	
	} else axe_error('Não consegui abrir para gravar '.$nome,E_USER_ERROR);	
	return($gravoumesmo);
}

function grava_postsrecentes($postsrecentes) {
// cria um quadro de posts recentes para uso em temas - #2014
	//$postsrecentes="<div id=quadropostsrecentes><ul>\n".$postsrecentes."</ul></div>\n";
	gravaarquivonaraiz("recentes.html",$postsrecentes);
	setquadrorecentes($postsrecentes);
}


function gravaarquivohtml($nome,$conteudo,$force = false,$preview=false) {
// grava um arquivo atomicamente, para uso no postsdir e previewdir
	global $quiet;
	global $sparsedebug;
	static $contamensagens;
	$gravoumesmo=false;
	$nome=preg_replace('/%[0-9A-Fa-f][0-9A-Fa-f]/',"",$nome);
	if ($preview) $fullnome=blogparm('PREVIEWDIR').$nome;
	else $fullnome=blogparm('POSTSDIR').$nome;
	$dir=dirname($fullnome);
	if (!is_dir($dir)) {
		if (mkdir($dir,0755,true)) {
			decho('Criei diretório '.$dir);
		} else axe_error('Não consegui criar dir '.$dir,E_USER_ERROR);	
	}	
	$gravar=true;
	// verifica se existe o mesmo arquivo nos fontes, e se é mais novo.
	// sem espertezas booleanas, para ficar mais clara a condição.
	if (!($force || $preview)) {
		$possivelfonte=preg_replace('/\.html$/',".php",blogparm('INPUTDIR').'posts/'.$nome);
		if (is_file($possivelfonte)) {
			$tempofonte=filectime($possivelfonte);
			if ($tempofonte) {
				if (is_file($fullnome)) {
					$tempodestino=filectime($fullnome);
					if ($tempodestino) {
						if ($tempodestino > $tempofonte) {
							$gravar=false;
							//decho('Nao precisei regravar '.$fullnome);
						}
					}
				}
			}	
		}
	}	
	if ($gravar || $force) {	
		$fp = fopen($fullnome.".temp", 'w');
		if ($fp) {
			if (fwrite($fp, $conteudo)) {
				if (fclose($fp)) {
					if (rename($fullnome.".temp", $fullnome)) {
						decho('Gravei '.$fullnome);
						//if ($force) echo "\nGravado: ".blogparm('BLOGROOT').$fullnome;
						$gravoumesmo=true;
						$mostramensagem=!$quiet;
						if ($sparsedebug==1) {
							if (($contamensagens % 10)>0) $mostramensagem=false;
							$contamensagens++;
						}	
						if ($mostramensagem) decho("Gravei o arquivo ".$fullnome);
					} else axe_error('Não consegui renomear: '.$fullnome,E_USER_ERROR);	
				} else axe_error('Não consegui fechar '.$fullnome,E_USER_ERROR);
			} else axe_error('Não consegui gravar '.$fullnome,E_USER_ERROR);	
		} else axe_error('Não consegui abrir para gravar '.$fullnome,E_USER_ERROR);	
	}	
	return($gravoumesmo);
}


function gravaarquivo($nome,$conteudo,$force = false) {
// grava um arquivo atomicamente dentro do blogroot
	global $quiet;
	global $sparsedebug;
	static $contamensagens;
	$nome=preg_replace('/%[0-9A-Fa-f][0-9A-Fa-f]/',"",$nome);	
	$gravoumesmo=false;
	$dir=dirname($nome);
	if (!is_dir(blogparm('BLOGROOT').$dir)) {
		if (mkdir(blogparm('BLOGROOT').$dir,0755,true)) {
			decho('Criei diretório '.blogparm('BLOGROOT').$dir);
		} else axe_error('Não consegui criar dir '.blogparm('BLOGROOT').$dir,E_USER_ERROR);	
	}	
	$gravar=true;
	// verifica se existe o mesmo arquivo nos fontes, e se é mais novo.
	// sem espertezas booleanas, para ficar mais clara a condição.
	if (!$force) {
		$possivelfonte=preg_replace('/\.html$/',".php",blogparm('INPUTDIR').$nome);
		if (is_file($possivelfonte)) {
			$tempofonte=filectime($possivelfonte);
			if ($tempofonte) {
				if (is_file(blogparm('BLOGROOT').$nome)) {
					$tempodestino=filectime(blogparm('BLOGROOT').$nome);
					if ($tempodestino) {
						if ($tempodestino > $tempofonte) {
							$gravar=false;
							//decho('Nao precisei regravar '.blogparm('BLOGROOT').$nome);
						}
					}
				}
			}	
		} else axe_error("Não encontrei $possivelfonte"); 	
	}	
	if ($gravar || $force) {	
		$fp = fopen(blogparm('BLOGROOT').$nome.".temp", 'w');
		if ($fp) {
			if (fwrite($fp, $conteudo)) {
				if (fclose($fp)) {
					if (rename(blogparm('BLOGROOT').$nome.".temp", blogparm('BLOGROOT').$nome)) {
						decho('Gravei '.blogparm('BLOGROOT').$nome);
						//if ($force) echo "\nGravado: ".blogparm('BLOGROOT').$nome;
						$gravoumesmo=true;
						$mostramensagem=!$quiet;
						if ($sparsedebug==1) {
							if (($contamensagens % 10)>0) $mostramensagem=false;
							$contamensagens++;
						}	
						if ($mostramensagem) decho("Gravei o arquivo ".blogparm('BLOGROOT').$nome);
					} else axe_error('Não consegui renomear '.blogparm('BLOGROOT').$nome,E_USER_ERROR);	
				} else axe_error('Não consegui fechar '.blogparm('BLOGROOT').$nome,E_USER_ERROR);
			} else axe_error('Não consegui gravar '.blogparm('BLOGROOT').$nome,E_USER_ERROR);	
		} else axe_error('Não consegui abrir para gravar '.blogparm('BLOGROOT').$nome,E_USER_ERROR);	
	}	
	return($gravoumesmo);
}


function gravadescriptor($nome,$conteudo,$force=false) {
// grava um arquivo atomicamente, para uso no postsdir e previewdir
	global $quiet;
	global $sparsedebug;
	static $contamensagens;
	$gravoumesmo=false;
	$fullnome=blogparm('INPUTDIR').'posts/'.$nome;
	$dir=dirname($fullnome);
	if (!is_dir($dir)) {
		if (mkdir($dir,0755,true)) {
			decho('Criei diretório '.$dir);
		} else axe_error('Não consegui criar dir '.$dir,E_USER_ERROR);	
	}	
	$gravar=true;
	// verifica se existe o mesmo arquivo nos fontes, e se é mais novo.
	// sem espertezas booleanas, para ficar mais clara a condição.
	if (!($force || $preview)) {
		$possivelfonte=preg_replace('/\.html$/',".php",blogparm('INPUTDIR').'posts/'.$nome);
		if (is_file($possivelfonte)) {
			$tempofonte=filectime($possivelfonte);
			if ($tempofonte) {
				if (is_file($fullnome)) {
					$tempodestino=filectime($fullnome);
					if ($tempodestino) {
						if ($tempodestino > $tempofonte) {
							$gravar=false;
							//decho('Nao precisei regravar '.$fullnome);
						}
					}
				}
			}	
		} else axe_error("Não encontrei $possivelfonte"); 	
	}	
	if ($gravar || $force) {	
		$fp = fopen($fullnome.".temp", 'w');
		if ($fp) {
			if (fwrite($fp, $conteudo)) {
				if (fclose($fp)) {
					if (rename($fullnome.".temp", $fullnome)) {
						decho('Gravei '.$fullnome);
						//if ($force) echo "\nGravado: ".blogparm('BLOGROOT').$fullnome;
						$gravoumesmo=true;
						$mostramensagem=!$quiet;
						if ($sparsedebug==1) {
							if (($contamensagens % 10)>0) $mostramensagem=false;
							$contamensagens++;
						}	
						if ($mostramensagem) decho("Gravei o arquivo ".$fullnome);
					} else axe_error('Não consegui renomear: '.$fullnome,E_USER_ERROR);	
				} else axe_error('Não consegui fechar '.$fullnome,E_USER_ERROR);
			} else axe_error('Não consegui gravar '.$fullnome,E_USER_ERROR);	
		} else axe_error('Não consegui abrir para gravar '.$fullnome,E_USER_ERROR);	
	}	
	return($gravoumesmo);
}


function lista_arquivos_processaveis($titulo,$fullpath,$comando="") {
	if (strlen($comando)>1) $comando.=" ";
	echo "Informe um nome de arquivo como parâmetro.\n\n";
	echo "$titulo\n";
	$arqs=scandir($fullpath);
	foreach ($arqs as $k) {
		if ((substr($k,0,1) != ".") && (substr($k,-1,1) != "~") && ($k != "old")) echo "$comando$k\n"; 		 
	}
	echo "--fim\n";
}


/************************************************************************
				Funções do catálogo de posts
*************************************************************************/

function loadpostvars_fromcatalog($fpost) {
// carrega as variáveis do template de posts diretamente a partir do catálogo
	global $POST;
	$k=preg_split('/;;/',$fpost);	
	$POST['POSTNAME']=preg_replace('/.*\/([^.]*)\.php.*/',"\\1",$k[1]);
	$POST['POSTTIME']=$k[2];
	$POST['POSTTITLE']=$k[3];
	$POST['POSTBODY']=$k[4];
	//print_r($POST);
	if (strlen($k[5])>=4) $POST['POSTICON']=$k[5];
	loadpostvars();	
	//decho($transfpost["%%POSTMID%%"]);	
		
}

function catalog_add($nome,$conteudo) {
// adiciona uma entrada a um dos catalogos de posts na pasta descriptors
	$file = blogparm('INPUTDIR')."$nome";	
	$conteudo=preg_replace('/[[<[:cntrl:]]]/',"",$conteudo);
	file_put_contents($file, $conteudo, FILE_APPEND | LOCK_EX);
	decho("Adicionei um post ao catálogo blogparm('BLOGROOT').$nome;");
}

function catalog_replace($nome,$conteudo) {
// substitui uma entrada em um dos catalogos de posts na pasta descriptors
	global $sparsedebug;
	$s=$sparsedebug;
	$sparsedebug=0;
	$conteudo=preg_replace('/[[<[:cntrl:]]]/',"",$conteudo);
	$k=preg_split('/;;/',$conteudo);
	$caminho=$k[1];
	catalog_del($caminho,$nome);
	$file = blogparm('INPUTDIR')."$nome";	
	file_put_contents($file, $conteudo, FILE_APPEND | LOCK_EX);
	decho("Adicionei um post ao catálogo blogparm('BLOGROOT').$nome;");
	$sparsedebug=$s;
}

function catalog_del($caminho,$catname) {
// apaga um post com este nome que exista no catálogo mencionado
	if (!is_readable(blogparm('INPUTDIR').$catname)) {
		axe_warning("catalog_del: não existe o arquivo ".blogparm('INPUTDIR').$catname);
		return;
	}
	$catalogo=file(blogparm('INPUTDIR').$catname);
	$nome=preg_replace('/\.html$/',".php",$caminho);
	if (substr($nome,-4)!=".php") $nome=$nome.".php";
	foreach($catalogo as $k => $v) {
		if (strpos($v,$caminho)) {
			decho("Apagando a linha $k em $catname: $v");
			unset($catalogo[$k]);
		}
	}
	file_put_contents(blogparm('INPUTDIR').$catname,$catalogo,LOCK_EX);	
}

function already_exists($name) {
// verifica se um post com o mesmo nome já existe no catálogo principal
	if (!is_readable(blogparm('INPUTDIR')."posts/catalog.txt")) return false;
	$catalogo=file(blogparm('INPUTDIR')."posts/catalog.txt");
	$busca="/\/$name.php/";
	$match=preg_grep($busca,$catalogo);
	return(count($match)>0);
}


function rebuild_catalogs() {
	// utilitario para recriar os catalogos de posts e de tags caso sejam corrompidos
	$raiz=blogparm('INPUTDIR')."posts/";
	$raiztags=blogparm('INPUTDIR')."tags/";
	dmsg("Reconstruindo catálogos em $raiz");
	foreach(glob($raiz."????",GLOB_ONLYDIR) as $ano) {
		dmsg("Posts do diretório $ano");
		foreach(glob($ano."/??",GLOB_ONLYDIR) as $mes) {
			dmsg("	$mes");
			foreach(glob($mes."/*.php") as $arq) {
				dmsg("		$arq");
				include $arq;
				$short=cutonword(strip_tags(corrigehtml($POST['POSTBODY'])),250);
				$short=preg_replace('/[[:cntrl:]]/',"",$short);
				if (blogparm('YEARLY')=="01") {
					$anomes=date("Y/",strtotime($POST["POSTTIME"]))."01/";
				} else {
					$anomes=date("Y/m/",strtotime($POST["POSTTIME"])); 
				}	
				$linha=date("Y/m/d-H:i:s",strtotime($POST["POSTTIME"])).";;"; 
				$linha.=$anomes.trim($POST["POSTNAME"]).".php;;".$POST["POSTTIME"].";;".$POST["POSTTITLE"].";;";
				$linha.=$short.";;".$POST["POSTICON"].";;".time()."\n";
				dmsg($linha);
				// grava no catálogo geral
				if (!isset($iabertos["catalog.txt"])) {
					$maincat=fopen($raiz."catalog.txt","w");
					$iabertos["catalog.txt"]=$maincat;
				}
				fputs($maincat,$linha);				
				// grava no catalogo do mês
				if (!isset($iabertos[$anomes."catalog.txt"])) {
					$xcat=fopen($raiz.$anomes."catalog.txt","w");
					$iabertos[$anomes."catalog.txt"]=$xcat;
				}
				dmsg("Gravando: ".$raiz.$anomes."catalog.txt");
				fputs($iabertos[$anomes."catalog.txt"],$linha);				

				// tags				
				$tags=$POST["POSTTAGS"];
				$tags=preg_replace('/[[:space:]]/',"",$tags);
				foreach(explode(",",$tags) as $tag) {
					if(0<strlen($tag)) {
						dmsg("tag: $tag");
						if (!isset($iabertos["tags/catalog-$tag.txt"])) {
							$xcat=fopen($raiztags."catalog-$tag.txt","w");
							$iabertos["tags/catalog-$tag.txt"]=$xcat;
						}
						fputs($iabertos["tags/catalog-$tag.txt"],$linha);								
					}
				}
			}
		}
	}				
	foreach($iabertos as $k=>$m) {
		dmsg("Fechando: $k");
		fclose($m);
	}
}




/************************************************************************
				Funções de sitemap
*************************************************************************/

function sitemap_entry($loc,$last="",$pri="0.5") {
	$s="		<url>\n";
	$s.="			<loc>$loc</loc>\n";
	if ($last) {
		$last=preg_replace('/\//',"-",$last);
		$s.="			<lastmod>$last</lastmod>\n";
	}	
	if ($pri)  $s.="			<priority>$pri</priority>\n";
	$s.='		</url>'."\n";		
	return $s;
}

function sitemap() {
	global $blogparms;
	$postsfiles=file(blogparm('INPUTDIR').'posts/'."catalog.txt",FILE_IGNORE_NEW_LINES);
	sort($postsfiles);
	$contaitems=0;
	$item='<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	for ($i=count($postsfiles)-1; $i >=0; $i--) {
		$fpost=$postsfiles[$i];
		unset($GLOBALS['POST']);
		global $POST;
		$k=preg_split('/;;/',$fpost);
		if (count($k)<=5) {
			axe_warning("Linha incompleta no catalog.txt: $fpost");
			continue; // pula essa linha no loop 'for'
		}
		if (blogparm('YEARLY')=="01") {
			$k[1]=preg_replace('/(....\/)..(\/.*)$/',"\\1aBaB\\2",$k[1]);
			$k[1]=preg_replace('/aBaB/',blogparm('YEARLY'),$k[1]);
		}				
		loadpostvars_fromcatalog($fpost);		
		$k[1]=preg_replace('/.php$/',".html",$k[1]);
		$item.=sitemap_entry(blogparm('BLOGURL').$k[1],substr($k[0],0,10),"0.7");
		$contaitems++;
	}
	$item.=sitemap_entry(blogparm('BLOGURL')."index.html","","0.7");
	$item.=sitemap_entry(blogparm('BLOGURL')."archive.html","","0.5");
	foreach (glob(blogparm("POSTSDIR")."{index,archive,tag}-*.html",GLOB_BRACE) as $filename) {
	   	//echo "<a href=\"".blogparm('BLOGURL').basename($filename)."\">".$filename."</a><br/>\n";
		$item.=sitemap_entry(blogparm('BLOGURL').basename($filename),"","0.2");
	}
	$item.='	</urlset>';
	gravaarquivonaraiz("sitemap-axe.xml",$item);
	return $item;
}


/************************************************************************
				Funções de plugins
*************************************************************************/

function registra_plugins() {
	global $pluginlist;
	$files=glob(blogparm('PLUGINSDIR').'*.php');
	if (!empty($files)) {
		foreach ($files as $filename) {
			// decho("Carregando o plugin ".basename($filename)); 
			include_once($filename);
			$pluginlist[]=preg_replace('/([^_.]*)(_.*)?\.php$/', "\\1", basename($filename));
		}
	}	
}

function aplica_plugins($tipo, $conteudo='', $p1='', $p2='', $p3='', $p4='') {
	global $pluginlist;
	$retorno=$conteudo;
	if (isset($pluginlist)) {
		foreach ($pluginlist as $plugin) {
			$funcao=$plugin.'_'.$tipo;
			if (function_exists($funcao)) {
				$retorno=$funcao($conteudo,$p1,$p2,$p3,$p4); 
			}	
		}
	}
	return $retorno;
}


/************************************************************************
				Funções de debug e mensagens
*************************************************************************/
	
function decho($texto,$level=E_USER_NOTICE) {
	global $quiet;
	global $verbose;
	static $contadecho;
	static $contadechoprint;
	if ($contadecho%3==0) {
		$helice=array("/","-","\\",'|');
		$csi=chr(27).'[';
		if (!$quiet) {
			if ($verbose) {
				echo("[axe] $texto\n");
			} else {
				$texto=substr($texto,0,70);
				echo($csi."0G".$csi."K".$helice[$contadechoprint % 4]." [axe] $texto");
				$contadechoprint++;
			}	
		}
	}	
	$contadecho++;
}

function dmsg($texto) {
	global $quiet;
	if (!$quiet) {
		$csi=chr(27).'[';
	
		echo("\r".$csi."K[axe] $texto\n");
	}	
}

function axe_error($texto,$level=E_USER_ERROR) {
	debug_print_backtrace();
	echo("\n[axe] Interrompendo a operação. Falha registrada: $texto\n");	
	die(5);
}
	
function axe_warning($texto) {
	debug_print_backtrace();
	echo("\n[axe] AVISO: $texto\n");	
}

axe_init();
?>
