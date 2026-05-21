<?php
/*
	gera um arquivo HTML contendo um post

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


function single_header($contaitems=1000) {
// processa o cabeçalho
	global $blogparmslist;
	global $trheader;
	global $POST;
	global $ISINDEX;
	$trheader=hack_the_header(file_get_contents("header.php",true));	
	$ISINDEX=false;
	setpagetitle(substpostvars('%%POSTTITLE%%')); 	// .' - '.blogparm('%%BLOGTITLE%%'));
	$tr_header=aplica_plugins('post',$trheader,'header',$contaitems);
	$header=substpostvars(substparms($trheader)); 
	return $header;
}	


function single_body($contaitems=1000) {
// gera o corpo do post
	global $tritem;
	global $POST;
	global $ISINDEX;
	$tritem		=file_get_contents("single-body.php",true);
	$ISINDEX=false;
	$tritem=aplica_plugins('post',$tritem,'body',$contaitems);	
	$item=substpostvars(substparms(corrigehtml($tritem)));
	return $item;
}

function single_coverpreview($contaitems=1000) {
// gera preview dos elementos do post para a capa
	global $tritempreview;
	global $POST;
	global $ISINDEX;
	$tritempreview	=try_file_get_contents("single-body-preview.php",true);
	$ISINDEX=false;
	$tritempreview=aplica_plugins('post',$tritempreview,'coverpreview',$contaitems);
	$item=substpostvars(substparms(corrigehtml($tritempreview)));
	return $item;
}


function single_footer($contaitems=1000) {
// processa o rodapé
	global $blogparmslist;
	global $trfooter;
	global $POST;
	global $ISINDEX;
	$trfooter	=file_get_contents("footer.php",true);
	$ISINDEX=false;
	$trfooter=aplica_plugins('post',$trfooter,'footer',$contaitems);
	$footer=substpostvars(substparms($trfooter));	
	return $footer;
}	

function criafonteeditavel($nome,$force=false,$preview=false) {
	global $POST;
	$txtsai='<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta name="robots" content="noindex"></head><body><code><textarea rows=20 cols=80>'; 
	$txtsai.=substpostvars('%%POSTTITLE%%')."\n";
	$txtsai.='tags:'.$POST['POSTTAGS']."\n\n";
	$t=$POST['POSTBODY']."\n\n";
	//$t=preg_replace('/</',"&lt;",$t);
	//$t=preg_replace('/>/',"&gt;",$t);
	$txtsai.=$t."\n\n";
	$txtsai.='@@POSTTIME:'.$POST['POSTTIME']."\n";
	$nome=preg_replace('/\.html/',".src.html", $nome);
	foreach ($POST as $key => $value) {
		if(strpos("foo".$key,'AKISMET')>0) continue;
		if(strpos("foo".$key,'POSTSTATUS')>0) continue;
		if(strpos("foo".$key,'POSTCATEGORIES')>0) continue;
		if(strpos("foo".$key,'POSTTYPE')>0) continue;
		if(strpos("foo".$key,'POSTGUID')>0) continue;
		if(strpos("foo".$key,'POSTMETAMYSTAMP')>0) continue;
		if(strpos("foo".$key,'EDIT_LAST')>0) continue;
		if(strpos("foo".$key,'CRON')>0) continue;
		if(strpos("foo".$key,'PRI')>0) continue;
		if(strpos("foo".$key,'COMMENTSTATUS')>0) continue;
		// casos especiais, porque são tratados à parte
		if(strpos("foo".$key,'POSTTITLE')>0) continue;
		// if(strpos("foo".$key,'POSTICON')>0) continue;
		if(strpos("foo".$key,'POSTTIME')>0) continue;
		if(strpos("foo".$key,'POSTTAGS')>0) continue;
		if(strpos("foo".$key,'POSTBODY')>0) continue;
		if(strpos("foo".$key,'POSTOLDCOMMENTS')>0) continue;
		$txtsai.="@@$key:$value\n";
	}
	if (isset($POST['POSTOLDCOMMENTS'])) {
		$t=substpostvars('%%POSTOLDCOMMENTS%%')."\n\n";
		$txtsai.="@@OLDCOMMENTS\n".$t."\n";		
	}	
	$txtsai.="</textarea><!--fim-source-->\n</code><p style=\"width:500px;\">Para editar o conteúdo do post &ldquo;<i>".substpostvars('%%POSTTITLE%%')."</i>&rdquo;, (1) copie o conteúdo acima em seu editor de texto, (2) altere-o como desejar, (3) grave-o no diretório '<tt>staging</tt>' do seu Axe e (4) execute o <tt>axe.php</tt> com os parâmetros <tt>-dU</tt> seguidos pelo nome do arquivo em que você o gravou.";
	gravaarquivohtml($nome,$txtsai,$force,$preview);
} 

function apagafonteeditavel($nome) {
	global $POST;
	$nome=preg_replace('/\.(php|html)$/',".src.html", $nome);
	unlink(blogparm('POSTSDIR').$nome);
} 



function apaga_single($caminho) { // exemplo: 2013/05/wunderlist-atualizado-traz-suporte-a-tags-e-mais.html
// apaga o single de um artigo existente, incluindo seu link numerico (se houver) e entradas nos indices e tags
	if (isset($GLOBALS['POST'])) unset($GLOBALS['POST']);
	global $POST;
	$caminho=preg_replace('|https?://[^/]*/|',"",$caminho);
	$caminho=preg_replace('|^/|',"",$caminho);
	$caminho=preg_replace('/\.html$/',".php",$caminho);
	if (substr($caminho,-4)!=".php") $caminho=$caminho.".php";
	$filein=blogparm('INPUTDIR').'posts/'.$caminho;
	dmsg("Apagando o post $filein");
	if (!is_file($filein)) {
		user_error('Não existe o arquivo '.$filein,E_USER_ERROR);
		die;
	} else {	
		include $filein;
		loadpostvars();
		catalog_del($caminho,"posts/catalog.txt");
		catalog_del($caminho,"posts/".substpostvars('%%POSTANOMES%%')."/catalog.txt");
		foreach(explode(",",$POST['POSTTAGS']) as $value) {
			if (strlen(trim($value))>0) catalog_del($caminho,"tags/catalog-".$value.".txt");
		}
		if (isset($POST['POSTID'])) apagasymlink($nome,$POST['POSTID']);
		apagafonteeditavel($caminho);
		unlink($filein);
	}	
}


function gera_single($caminho, $force=false, $preview=false,$contaitems=1000) {
// POSTA A PARTIR DO DESCRIPTOR - gera/regera o single de um artigo existente no catalogo
	if (isset($GLOBALS['POST'])) unset($GLOBALS['POST']);
	global $POST;
	global $quickrebuild;
	global $blogparms;
	global $nofirstimage;
	$firstimage="";
	if (!$preview) $filein=blogparm('INPUTDIR').'posts/'.$caminho;
	else $filein=blogparm('DRAFTSDIR').$caminho;
	if (!is_file($filein)) {
		user_error('Não existe o arquivo '.$filein,E_USER_ERROR);
		die;
	} else {	
		include $filein;
		loadpostvars();
		$single=single_header($contaitems);
		if ($preview) {
			$single.=single_coverpreview($contaitems);
		}
		$body=insereanunciocentral(single_body($contaitems));
		$single.=$body;
		$single.=single_footer($contaitems);
		if (!$nofirstimage) {
			$firstimage=first_image($body);
		} else {
			$firstimage=substpostvars("%%POSTICON%%");
		}	
		if (!empty($firstimage)) {
			$single=preg_replace('/@@PAGEICON@@/',"$firstimage",$single);
		} else {
			$single=preg_replace('/@@PAGEICON@@/',blogparm('PAGEICON'),$single);		
		}
		if (!$preview) $nome=preg_replace('/\.php$/',".html",$caminho);
		else $nome=preg_replace('/\.php$/',".html",$caminho);
		if ($preview && !empty($blogparms["PREVIEWFIXEDNAME"])) {
			$nome=$blogparms["PREVIEWFIXEDNAME"];
		}
		if (substr($nome,-5)!=".html") $nome=$nome.".html";
		if (gravaarquivohtml($nome,$single,$force,$preview)) {
			if (isset($POST['POSTID'])) criasymlink($nome,$POST['POSTID']);
			if (!$preview) criafonteeditavel($nome,$force,$preview);
			if (true!=$quickrebuild) { // não terá rebuild imediato, portanto atualiza já as tags afetadas 
				foreach(explode(",",$POST['POSTTAGS']) as $tag) {
					if (strlen(trim($tag))>0) cria_tagindex($tag);
				}			
			}	
		}
		return $nome;
	}	
}




function atualiza_descriptor($caminho) {
	// atualiza, a partir de um novo draft, o descriptor de um artigo existente (axe -U)
	// TO DO: identificar se alguma tag SAIU do post, e remove-lo do catalogo dela
	if (isset($GLOBALS['POST'])) unset($GLOBALS['POST']);
	global $POST;
	if (!is_file(blogparm('DRAFTSDIR').$caminho)) {
		user_error('Não existe o arquivo '.blogparm('DRAFTSDIR').$caminho,E_USER_ERROR);
		die;
	} else {
		include blogparm('DRAFTSDIR').$caminho;
		loadpostvars();
		if (!(isset($POST['POSTTIME']) && isset($POST['POSTNAME']))) {
			user_error('Impossível atualizar: Não há POSTTIME ou POSTNAME no arquivo '.blogparm('DRAFTSDIR').$caminho,E_USER_ERROR);
			die;
		} else if (!already_exists(substpostvars('%%POSTNAME%%'))) {
			user_error('Impossível atualizar: não existe o nome '.substpostvars('%%POSTNAME%%')." no catálogo principal.",E_USER_ERROR);
			die;
		} else {
			$saida="<?php\n";	
			foreach ($POST as $key => $value) {
				if ($key=="POSTBODY") {
					$saida.='$POST["POSTBODY"]= <<< endPBPBPBaxe'."\n";
					$saida.=$value."\n";
					$saida.='endPBPBPBaxe;'."\n";
				} elseif ($key=="OLDCOMMENTS") {
					$saida.='$POST["POSTOLDCOMMENTS"]= <<< endPCPCPCaxe'."\n";
					$saida.=$value."\n";
					$saida.='endPCPCPCaxe;'."\n";		
				} else $saida.="\$POST['$key']='".trim($value)."';\n";	
			}
			$saida.="?>";
			$arquivo=preg_replace('/\.[^.]*$/',".php", $arquivo);
			$arqsai=substpostvars('%%POSTANOMES%%').'/'.substpostvars('%%POSTNAME%%').'.php';
			gravadescriptor($arqsai,$saida,true);
			//criafonteeditavel($arqsai,true);
			$short=cutonword(strip_tags(corrigehtml($POST['POSTBODY'])),250);
			$short=preg_replace('/[[:cntrl:]]/',"",$short);			
			$indexentry=date("Y/m/d-H:i:s",strtotime(substpostvars('%%POSTTIME%%'))).substpostvars(";;%%POSTANOMES%%/%%POSTNAME%%.php;;%%POSTTIME%%;;%%POSTTITLE%%;;").trim($short).";;".substpostvars('%%POSTICON%%').";;".time()."\n";
			catalog_replace("posts/catalog.txt",$indexentry);
			catalog_replace("posts/".substpostvars('%%POSTANOMES%%')."/catalog.txt",$indexentry);
			if (isset($POST['POSTTAGS'])) {
				$POST['POSTTAGS']=preg_replace('/[[:blank:]]*/',"",$POST['POSTTAGS']);
				$POST['POSTTAGS']=preg_replace('/, */',",",$POST['POSTTAGS']);
				foreach(explode(",",$POST['POSTTAGS']) as $value) {
					catalog_replace("tags/catalog-".trim($value).".txt",$indexentry);
				}
			} 							
			move_oldfile(blogparm('DRAFTSDIR').$caminho);
			return $arqsai;
		}	
	}
}


function cria_descriptor($caminho) {
	// CRIA o descriptor para um post novo a partir de um draft, e atualiza os catalogos
	if (isset($GLOBALS['POST'])) unset($GLOBALS['POST']);
	global $POST;
	if (!is_file(blogparm('DRAFTSDIR').$caminho)) {
		user_error('Não existe o arquivo '.blogparm('DRAFTSDIR').$caminho,E_USER_ERROR);
		die;
	} else {
		include blogparm('DRAFTSDIR').$caminho;
		loadpostvars();
		if ((isset($POST['POSTTIME']) && isset($POST['POSTNAME']))) {
			user_error('Impossível criar: Já há POSTTIME e POSTNAME no arquivo '.blogparm('DRAFTSDIR').$caminho,E_USER_ERROR);
			die;
		} else if (!isset($POST['POSTTITLE'])) {
			user_error('Impossível criar: não há POSTTITLE definido no arquivo '.blogparm('DRAFTSDIR').$caminho,E_USER_ERROR);
			die;
		} else {
			$POST['POSTTIME']=date("r");
			if (empty($POST['POSTNAME'])) $POST['POSTNAME']=normaliza($POST['POSTTITLE']);
			$POST['POSTNAME']=preg_replace('/%[0-9A-F][0-9A-F]/',"",$POST['POSTNAME']);			
			$POST['POSTTAGS']=normaliza($POST['POSTTAGS'],",");
			$POST['POSTTAGS']=preg_replace('/[[:space:]]/',"",$POST['POSTTAGS']);
			loadpostvars();
			if (already_exists(substpostvars('%%POSTNAME%%'))) {
				$randpref='jaexiste_'.rand(1000,9999).'_';
				rename(blogparm('DRAFTSDIR').$caminho,blogparm('DRAFTSDIR').$randpref.$caminho);
				user_error("Impossível criar descriptor para $caminho: já existe o nome ".substpostvars('%%POSTNAME%%')." no catálogo principal. Draft renomeado para $randpref$caminho",E_USER_ERROR);
				die;
			} else {
				$saida="<?php\n";	
				foreach ($POST as $key => $value) {
					if ($key=="POSTBODY") {
						$saida.='$POST["POSTBODY"]= <<< endPBPBPBaxe'."\n";
						$saida.=$value."\n";
						$saida.='endPBPBPBaxe;'."\n";
					} elseif ($key=="OLDCOMMENTS") {
						$saida.='$POST["POSTOLDCOMMENTS"]= <<< endPCPCPCaxe'."\n";
						$saida.=$value."\n";
						$saida.='endPCPCPCaxe;'."\n";		
					} else $saida.="\$POST['$key']='".trim($value)."';\n";	
				}
				$saida.="?>";
				$arquivo=preg_replace('/\.[^.]*$/',".php", $arquivo);
				$arqsai=substpostvars('%%POSTANOMES%%').'/'.substpostvars('%%POSTNAME%%').'.php';
				decho("** $arqsai");
				gravadescriptor($arqsai,$saida,true);
				//criafonteeditavel($arqsai,true);
				$indexentry=date("Y/m/d-H:i:s",strtotime(substpostvars('%%POSTTIME%%'))).substpostvars(";;%%POSTANOMES%%/%%POSTNAME%%.php;;%%POSTTIME%%;;%%POSTTITLE%%;;").trim(substpostvars('%%POSTMID%%')).";;".substpostvars('%%POSTICON%%').";;".time()."\n";
				catalog_add("posts/catalog.txt",$indexentry);
				catalog_add("posts/".substpostvars('%%POSTANOMES%%')."/catalog.txt",$indexentry);
				if (isset($POST['POSTTAGS'])) {
					$POST['POSTTAGS']=preg_replace('/[[:blank:]]*/',"",$POST['POSTTAGS']);
					$POST['POSTTAGS']=preg_replace('/, */',",",$POST['POSTTAGS']);
					foreach(explode(",",$POST['POSTTAGS']) as $value) {
						catalog_add("tags/catalog-".trim($value).".txt",$indexentry);
					}
				} 				
				move_oldfile(blogparm('DRAFTSDIR').$caminho);
				return $arqsai;
			}
		}	
	}
}

?>
