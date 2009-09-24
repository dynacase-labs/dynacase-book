<?php

function lbookstates($dbaccess, $docid, $name="") {
  $doc=new_doc($dbaccess,$docid);
  $tr=array();
  $staticstates=array("_fixed_"." ("._("fixed revision").")",
		      "_latest_"." ("._("latest revision").")");
  foreach ($staticstates as $k=>$v) $tr[]=array($v,$v);

  if ($doc->isAlive() && $doc->wid) {
    $wdoc=new_doc($dbaccess,$doc->wid,false);
    if ($wdoc && method_exists($wdoc,"getStates")) {
      $states=$wdoc->getStates();      
      foreach ($states as $k=>$v) {
	if (($name == "") ||    (eregi("$name", $v , $reg))) $tr[]=array($v.' ('._($v).')',$v.' ('._($v).')');
      }
    } 
  } 
  return $tr;  
}



?>