<?php

/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */


function preCreated() {
  $book=new_doc($this->dbaccess,$this->getValue("chap_bookid"));
  if ($book->isAlive()) {
    if ($doc->locked == -1) { // it is revised document
      $ldocid = $book->latestId();
      if ($ldocid != $book->id) $book = new_Doc($this->dbaccess, $ldocid);
    }
    $err=$book->control("modify");
    if ($err=="") return "";
  }
  
  return _("need modify acl in book");
  }

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
