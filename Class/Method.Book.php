<?php

public $defaultview="BOOK:VIEWBOOK";

function specRefresh() {
  

  $this->AddParamRefresh("book_tplodt","book_headleft,book_headmiddle,book_headright,book_footleft,book_footmiddle,book_footright,book_tplodt");
}

function viewbook($target="_self",$ulink=true,$abstract=false) {
  include_once("FDL/Lib.Dir.php");
  global $action;
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FREEDOM/Layout/fdl_tooltip.js");
  
  $this->lay->set("stylesheet",($this->getValue("book_tplodt")!=""));
  $this->viewdefaultcard($target,$ulink,$abstract);

  
}
/**
 * Return list of chapters
 * @return array of document array
 */
function getChapters() {
  
  $filter[]="chap_bookid='".$this->initid."'";
  $filter[]="doctype!='T'";
  $chapters = getChildDoc($this->dbaccess, 0,0,"ALL",$filter,$this->userid,"TABLE","CHAPTER",false,"");

  return $chapters;
}

/**
 * to sort chapters by level
 */
static function _cmplevel($a,$b) {
  
  $tv1=array_pad((explode(".",$a['chap_level'])),5,0);
  $tv2=array_pad((explode(".",$b['chap_level'])),5,0);
  $iv1='';
  $iv2='';
  foreach ($tv1 as $k=>$v) $iv1.=sprintf("%02d",$v);
  foreach ($tv2 as $k=>$v) $iv2.=sprintf("%02d",$v);

  return strcmp($iv1,$iv2);
}

function gentdm() {

  $chapters = $this->getChapters();
 
  foreach ($chapters as $k=>$chap) {
    $chapters[$k]["level"]=(count(explode(".",$chap["chap_level"]))-1)*15;
    if (controlTdoc($chap,"edit") && (($chap["locked"]==0)||(abs($chap["locked"])==$this->userid))) {
      $chapters[$k]["icon"]=$this->getIcon($chap["icon"]);
    } else {
      $chapters[$k]["icon"]=false;
    }
    $chapters[$k]["chap_comment"]=str_replace(array('"',"\n","\r"),
					      array("rsquo;",'<br>',''),$chap["chap_comment"]);
  }
  uasort($chapters, array (get_class($this), "_cmplevel"));
  

 
  $this->lay->setBlockData("CHAPTERS",$chapters);
}
function openbook($target="_self",$ulink=true,$abstract=false) {
  $this->viewbook($target,$ulink,$abstract);
  $this->gentdm($target,$ulink,$abstract);
  

  $chapid=getFamIdFromName($this->dbaccess,"CHAPTER");
  $filter=array();
  $filter[]="fromid != $chapid";
  $tannx=$this->getContent(true,$filter);


  foreach ($tannx as $k=>$chap) {
    $tannx[$k]["icon"]=$this->getIcon($chap["icon"]);
  }

  $this->lay->setBlockData("ANNX",$tannx);
  
}
function genhtml($target="_self",$ulink=true,$abstract=false) {
  $this->viewbook($target,$ulink,$abstract);
  $this->gentdm($target,$ulink,$abstract);
  $chapters=$this->lay->getBlockData("CHAPTERS");
  
  $chapter0=array();
  foreach ($chapters as $k=>$chap) {
    $chapters[$k]["hlevel"]=(count(explode(".",$chap["chap_level"])));
    if ($chap["chap_level"][0]=="0") {
      $chapter0[$k]=$chapters[$k];
      unset($chapters[$k]);
    }
  }
  $this->lay->setBlockData("CHAPTER0",$chapter0);
  $this->lay->setBlockData("CHAPTERS",$chapters);
  $this->lay->set("booktitle",$this->title);
  $this->lay->set("has0",(count($chapter0)>0));
  $this->lay->set("stylesheet",($this->ispdf && ($this->getValue("book_tplodt")!="")));
  if ($this->ispdf) {
    $this->lay->set("HL",$this->hftoooo($this->getValue("book_headleft")));
    $this->lay->set("HM",$this->hftoooo($this->getValue("book_headmiddle")));
    $this->lay->set("HR",$this->hftoooo($this->getValue("book_headright")));
    $this->lay->set("FL",$this->hftoooo($this->getValue("book_footleft")));
    $this->lay->set("FM",$this->hftoooo($this->getValue("book_footmiddle")));
    $this->lay->set("FR",$this->hftoooo($this->getValue("book_footright")));
    $this->lay->set("toc",($this->getValue("book_toc")=="yes"));    
  } else {    
    $this->lay->set("toc",false);
  }
  $this->lay->set("ispdf",($this->ispdf==true));

}

function hftocss($hf) {
  $hf=str_replace('"',' ',$hf);
  $hf=str_replace("##PAGES##",'" counter(pages) "',$hf);
  $hf=str_replace("##PAGE##",'" counter(page) "',$hf);
  return '"'.$hf.'"';
}
function hftoooo($hf) {

  $hf=str_replace("##PAGES##","<SDFIELD TYPE=DOCSTAT SUBTYPE=PAGE FORMAT=PAGE>1</SDFIELD>",$hf);
  $hf=str_replace("##PAGE##","<SDFIELD TYPE=PAGE SUBTYPE=RANDOM FORMAT=PAGE>1</SDFIELD>",$hf);
  return $hf;
}


function postCopy(&$copyfrom) {
  include_once("FDL/Lib.Dir.php");
  $filter[]="chap_bookid=".$copyfrom->initid;
  $filter[]="doctype!='T'";

  $chapters = getChildDoc($this->dbaccess, 0,0,"ALL",$filter,$this->userid,"TABLE","CHAPTER");

  $this->deleteValue("book_pdf");
  $this->deleteValue("book_datepdf");
  foreach ($chapters as $k=>$chap) {
    $nc=getDocObject($this->dbaccess,$chap);
    $copy=$nc->Copy();
    if (! is_object($copy)) $err.= $copy;
    else {
      $copy->setValue("chap_bookid",$this->initid);
      $copy->modify();
      $this->Addfile($copy->initid);
    }    
  }


  $chapid=getFamIdFromName($this->dbaccess,"CHAPTER");
  $filter=array();
  $filter[]="fromid != $chapid";
  $tannx=$copyfrom->getContent(true,$filter);
  foreach ($tannx as $k=>$v) {
      $this->Addfile($v["initid"]);    
  }

}
function postDelete() {
  include_once("FDL/Lib.Dir.php");
  $filter[]="chap_bookid=".$this->initid;
  $filter[]="doctype!='T'";

  $chapters = getChildDoc($this->dbaccess, 0,0,"ALL",$filter,$this->userid,"TABLE","CHAPTER");
  $err="";
  foreach ($chapters as $k=>$chap) {
    $nc=getDocObject($this->dbaccess,$chap);
    $err.=$nc->delete();
  }
  return $err;
}

  /**
   * send a request to TE to convert file to PDF
   * 
   * 
   */
public function genpdf($target="_self",$ulink=true,$abstract=false) {     
  include_once("FDL/Lib.Vault.php");   
  $tea=getParam("TE_ACTIVATE");
  if ($tea!="yes") { 
    addWarningMsg(_("TE engine not activated"));
    return;
  }
  if (@include_once("WHAT/Class.TEClient.php")) {
    include_once("FDL/Class.TaskRequest.php");
    global $action;
    $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/BOOK/Layout/genpdf.js");
    
    

    $this->ispdf=true;
    $html=$this->viewDoc("BOOK:GENHTML:S");
    $this->ispdf=false;
    
    $this->lay->set("docid",$this->id);
    $this->lay->set("title",$this->title);
    $va=$this->getValue("book_pdf");
    if (preg_match(PREGEXPFILE, $va, $reg)) {  
      $vid=$reg[2];
      
      $ofout=new VaultDiskStorage($this->dbaccess,$vid);
      $ofout->teng_state=2; 
      $ofout->modify();
    } else {
      // create first
      $filename=uniqid("/var/tmp/conv").".txt";
      $nc=file_put_contents($filename,"-");
      $vf = newFreeVaultFile($this->dbaccess);
      $err=$vf->Store($filename, false , $vid);
      unlink($filename);
      if ($err=="") {
	$vf->storage->teng_state=2;
	$vf->storage->modify();;
	$this->setValue("book_pdf","$mime|$vid");
	$this->modify();
      }
    }
          
    if ($this->getValue("book_tplodt")) {
      $engine='odt';
      $urlindex=getOpenTeUrl(array("app"=>"FDL",
				   "action"=>"FDL_METHOD",
				   "method"=>"ooo2pdf",
				   "id"=>$this->id));
      $callback=$urlindex."&sole=Y&app=FDL&action=FDL_METHOD&redirect=no&method=ooo2pdf&id=".$this->id;
    } else {
      $urlindex=getOpenTeUrl();
      $engine='pdf';
      $callback=$urlindex."&sole=Y&app=FDL&action=INSERTFILE&engine=$engine&vidout=$vid&name=".urlencode($this->title).".pdf";
    }
    $ot=new TransformationEngine(getParam("TE_HOST"),getParam("TE_PORT"));
    $html = preg_replace('/<font([^>]*)face="([^"]*)"/is',
			 "<font\\1",
			 $html);
    $html = preg_replace(array("/SRC=\"([^\"]+)\"/e","/src=\"([^\"]+)\"/e"),
			 "\$this->srcfile('\\1')",
			 $html);
    $html = preg_replace(array('/size="([1-9])"/e','/size=([1-9])/e','/font-size: medium;/e'), "", $html); // delete font size
    $html = str_replace('<table ','<table style=" page-break-inside: avoid;" ', $html);

    $filename= uniqid("/var/tmp/txt-").'.html';
    file_put_contents($filename,$html);
    $err=$ot->sendTransformation($engine,$vid,$filename,$callback,$info);
   
    @unlink($filename);
    if ($err=="") {
      global $action;
      $tr=new TaskRequest($this->dbaccess);
      $tr->tid=$info["tid"];
      $tr->fkey=$vid;
      $tr->status=$info["status"];
      $tr->comment=$info["comment"];
      $tr->uid=$this->userid;
      $tr->uname=$action->user->firstname." ".$action->user->lastname;
      $err=$tr->Add();
    } else {
      $vf=initVaultAccess();
      $filename= uniqid("/var/tmp/txt-".$vid.'-');
      file_put_contents($filename,$err);
      //$vf->rename($vidout,"toto.txt");
      $vf->Retrieve($vid, $info);
      $err=$vf->Save($filename, false , $vid);
      @unlink($filename);
      $vf->rename($vid,_("impossible conversion").".txt");
      $vf->storage->teng_state=-2;
      $vf->storage->modify();;
    }

  } else {
    addWarningMsg(_("TE engine activate but TE-CLIENT not found"));
  }
}


  /**
   * send a request to TE to convert file to PDF
   * Pass two
   * 
   */
public function ooo2pdf() {     
  include_once("FDL/insertfile.php");       
  include_once("FDL/Lib.Vault.php");   
  $tea=getParam("TE_ACTIVATE");
  if ($tea!="yes") { 
    addWarningMsg(_("TE engine not activated"));
    return;
  }
  if (@include_once("WHAT/Class.TEClient.php")) {
    include_once("FDL/Class.TaskRequest.php");

    $tid= GetHttpVars("tid");

    $filename= uniqid("/var/tmp/txt-").'.odt';
    $err=getTEFile($tid,$filename,$info);
    if ($err=="") {
      // add style sheet
      $ott=$this->getValue("book_tplodt");
      if ($ott) {
	$this->insertstyle($filename,$this->vault_filename("book_tplodt",true));
      }



      $va=$this->getValue("book_pdf");
      if (preg_match(PREGEXPFILE, $va, $reg)) $vid=$reg[2];
      
      $engine='pdf';
           
      $urlindex=getOpenTeUrl();
      $callback=$urlindex."&sole=Y&app=FDL&action=INSERTFILE&engine=$engine&vidout=$vid&name=".urlencode($this->title).".pdf";
      $ot=new TransformationEngine(getParam("TE_HOST"),getParam("TE_PORT"));
    

      $err=$ot->sendTransformation($engine,$vid,$filename,$callback,$info);
      @unlink($filename);
      if ($err=="") {
	global $action;
	$tr=new TaskRequest($this->dbaccess);
	$tr->tid=$info["tid"];
	$tr->fkey=$vid;
	$tr->status=$info["status"];
	$tr->comment=$info["comment"];
	$tr->uid=$this->userid;
	$tr->uname=$action->user->firstname." ".$action->user->lastname;
	$err=$tr->Add();
      } else {
	$vf=initVaultAccess();
	$filename= uniqid("/var/tmp/txt-".$vid.'-');
	file_put_contents($filename,$err);
	//$vf->rename($vidout,"toto.txt");
	$vf->Retrieve($vid, $info);
	$err=$vf->Save($filename, false , $vid);
	@unlink($filename);
	$vf->rename($vid,_("impossible conversion").".txt");
	$vf->storage->teng_state=-2;
	$vf->storage->modify();;
      }
    }

  } else {
    addWarningMsg(_("TE engine activate but TE-CLIENT not found"));
  }
}


function insertstyle($odt,$ott) {
  if (! file_exists($odt)) return "file $odt not found";
  $dodt=uniqid("/var/tmp/odt");  
  $cmd = sprintf("unzip  %s  -d %s >/dev/null",$odt , $dodt );
  system($cmd);

  $dott=uniqid("/var/tmp/ott");  
  $cmd = sprintf("unzip  %s  -d %s >/dev/null",$ott , $dott );
  system($cmd);
  /*
  $domo=new DOMDocument();
  $domo->load("$dodt/styles.xml");
  $autoo=$domo->getElementsByTagNameNS("urn:oasis:names:tc:opendocument:xmlns:office:1.0","automatic-styles");
  print count($autoo);
    print $domo->saveXML($autoo->item(0));

  $domt=new DOMDocument();
  $domt->load("$dodt/styles.xml");
  $autot=$domt->getElementsByTagNameNS("urn:oasis:names:tc:opendocument:xmlns:office:1.0","automatic-styles");
  print count($autot);

  $c=$domt->importNode($autoo->item(0),true);
  $autot->item(0)->parentNode->insertBefore($c,$autot->item(0));
  
  */


  $cmd = sprintf("cp %s/styles.xml  %s >/dev/null",$dott , $dodt );
  system($cmd);

  $cmd = sprintf("sed -i -e 's/style:master-page-name=\"HTML\"//g' %s/content.xml",$dodt);
  system($cmd);
  $cmd = sprintf("sed -i -e 's!href=\"../../../!href=\"/var/!g' %s/content.xml",$dodt);
  system($cmd);
  if (is_dir("$dott/Pictures")) {
    if (! is_dir("$dodt/Pictures")) mkdir("$dodt/Pictures");
    $cmd = sprintf("cp -r %s/Pictures/*  %s/Pictures >/dev/null",$dott , $dodt );
    system($cmd);
  }
  $cmd = sprintf("cd %s;zip -r %s * >/dev/null",$dodt , $odt );
  system($cmd);

  
  $cmd = sprintf("/bin/rm -fr %s", $dodt );
  system($cmd);

  $cmd = sprintf("/bin/rm -fr %s", $dott );
  system($cmd);
  
}
function srcfile($src) {
  global $ifiles;
  $vext= array("gif","png","jpg","jpeg","bmp");
  
  if (preg_match("/vid=([0-9]+)/",$src,$reg)) {
    $info=vault_properties($reg[1]);
    if ( ! in_array(strtolower(fileextension($info->path)),$vext)) return "";
    
    return 'src="file://'.$info->path.'"';
  }

  return "";
}
function getFileDate($va) {  
  if (preg_match(PREGEXPFILE, $va, $reg)) {  
    include_once("VAULT/Class.VaultDiskStorage.php");
    $vid=$reg[2];
    
    $ofout=new VaultDiskStorage($this->dbaccess,$vid);

    return $ofout->mdate;
  }
}

?>