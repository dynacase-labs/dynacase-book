<?php

public $defaultview="BOOK:VIEWBOOK";

function viewbook($target="_self",$ulink=true,$abstract=false) {
  include_once("FDL/Lib.Dir.php");
  global $action;
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FREEDOM/Layout/fdl_tooltip.js");
  
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

  
}
?>