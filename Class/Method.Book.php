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
  
  foreach ($chapters as $k=>$chap) {
    $chapters[$k]["hlevel"]=(count(explode(".",$chap["chap_level"])));
  }
  $this->lay->setBlockData("CHAPTERS",$chapters);
  $this->lay->set("booktitle",$this->title);
  $this->lay->set("HL",$this->hftocss($this->getValue("book_headleft")));
  $this->lay->set("HM",$this->hftocss($this->getValue("book_headmiddle")));
  $this->lay->set("HR",$this->hftocss($this->getValue("book_headright")));
  $this->lay->set("FL",$this->hftocss($this->getValue("book_footleft")));
  $this->lay->set("FM",$this->hftocss($this->getValue("book_footmiddle")));
  $this->lay->set("FR",$this->hftocss($this->getValue("book_footright")));
}

function hftocss($hf) {
  $hf=str_replace('"',' ',$hf);
  $hf=str_replace("##PAGES##",'" counter(pages) "',$hf);
  $hf=str_replace("##PAGE##",'" counter(page) "',$hf);
  return '"'.$hf.'"';
}
?>