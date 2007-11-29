<?php

public $defaultview="BOOK:VIEWBOOK";

function viewbook($target="_self",$ulink=true,$abstract=false) {
  include_once("FDL/Lib.Dir.php");
  global $action;
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FREEDOM/Layout/fdl_tooltip.js");
  
  $this->viewdefaultcard($target,$ulink,$abstract);
  $filter[]="chap_bookid=".$this->initid;
  $filter[]="doctype!='T'";

  $chapters = getChildDoc($this->dbaccess, 0,0,"ALL",$filter,$this->userid,"TABLE","CHAPTER",false,"chap_level");

  foreach ($chapters as $k=>$chap) {
    $chapters[$k]["level"]=(count(explode(".",$chap["chap_level"]))-1)*15;
    $chapters[$k]["chap_comment"]=str_replace(array('"',"\n","\r"),
					      array("rsquo;",'<br>',''),$chap["chap_comment"]);
  }
  
  $this->lay->setBlockData("CHAPTERS",$chapters);
}


function openbook($target="_self",$ulink=true,$abstract=false) {
  $this->viewbook($target,$ulink,$abstract);
  
  $chapid=getFamIdFromName($this->dbaccess,"CHAPTER");
  $filter[]="fromid != $chapid";
  $tannx=$this->getContent(true,$filter);



  $this->lay->setBlockData("ANNX",$tannx);
  
}
function genhtml($target="_self",$ulink=true,$abstract=false) {
  $this->viewbook($target,$ulink,$abstract);
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
  if ($this->ispdf) {
    $this->lay->set("HL",$this->hftocss($this->getValue("book_headleft")));
    $this->lay->set("HM",$this->hftocss($this->getValue("book_headmiddle")));
    $this->lay->set("HR",$this->hftocss($this->getValue("book_headright")));
    $this->lay->set("FL",$this->hftocss($this->getValue("book_footleft")));
    $this->lay->set("FM",$this->hftocss($this->getValue("book_footmiddle")));
    $this->lay->set("FR",$this->hftocss($this->getValue("book_footright")));
    $this->lay->set("toc",($this->getValue("book_toc")=="yes"));
  
    $base=getParam("CORE_EXTERNURL");

    if (ereg("(https?)://([^/]*)",$base,$reg)) {  
      $base=$reg[1].'://'.$reg[2].'/';
    }
  
    global $_SERVER;
    $login=$_SERVER["PHP_AUTH_USER"];
    $pw=$_SERVER["PHP_AUTH_PW"];

    if (substr($base,0,7)=="http://") $base=str_replace("http://","http://$login:$pw@",$base);
    else if (substr($base,0,8)=="https://") $base=str_replace("https://","http://$login:$pw@",$base);
    $this->lay->set("basehref","$base");
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
  include_once("FDL/Lib.Vault.php");   $tea=getParam("TE_ACTIVATE");
  if ($tea!="yes") { 
    addWarningMsg(_("TE engine not activated"));
    return;
  }
  if (@include_once("TE/Class.TEClient.php")) {
    include_once("FDL/Class.TaskRequest.php");
    global $action;
    $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/BOOK/Layout/genpdf.js");
    
    

    $this->ispdf=true;
    $html=$this->viewDoc("BOOK:GENHTML:S");
    $this->ispdf=false;

    $this->lay->set("docid",$this->id);
    $this->lay->set("title",$this->title);
    $va=$this->getValue("book_pdf");
    if (ereg ("(.*)\|(.*)", $va, $reg)) {  
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
    $engine='pdf';
    $urlindex=getParam("CORE_EXTERNURL");
    $callback=$urlindex."?sole=Y&app=FDL&action=INSERTFILE&engine=$engine&vidout=$vid&name=".urlencode($this->title).".pdf";
    $ot=new TransformationEngine(getParam("TE_HOST"),getParam("TE_PORT"));

    $filename= uniqid("/var/tmp/txt-".'.html');
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

function getFileDate($va) {  
  if (ereg ("(.*)\|(.*)", $va, $reg)) {  
    include_once("VAULT/Class.VaultDiskStorage.php");
    $vid=$reg[2];
    
    $ofout=new VaultDiskStorage($this->dbaccess,$vid);

    return $ofout->mdate;
  }
}

?>