<?php


function postModify() {
  $html=$this->getValue("chap_content");
  $html = preg_replace('/<font([^>]*)face="([^"]*)"/is',
			 "<font\\1",
		       $html); //delete font face

  $this->setValue("chap_content",$html);
  $err=$this->modify();
  return $err;
  }
?>
