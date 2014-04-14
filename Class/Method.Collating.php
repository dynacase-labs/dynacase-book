<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package BOOK
*/
/**
 * Specials methods for BOOK family
 */
/**
 * @begin-method-ignore
 * this part will be deleted when construct document class until end-method-ignore
 */
Class _COLLATING extends Doc
{
    /*
     * @end-method-ignore
    */
    function postRefresh()
    {
        $tchaps = $this->getMultipleRawValues("coll_chapid");
        $tattrids = $this->getMultipleRawValues("coll_attrid");
        $fstates = $this->getMultipleRawValues("coll_statefilter");
        $tfiles = array();
        $tdates = array();
        $tstates = array();
        foreach ($tchaps as $k => $v) {
            $tfiles[$k] = _('no file');
            $tdates[$k] = "";
            $tstates[$k] = "";
            if ($v) {
                $fstate = strtok($fstates[$k], ' ');
                if ($fstate == "_latest_") $d = new_doc($this->dbaccess, $v, true);
                else $d = new_doc($this->dbaccess, $v);
                if ($fstate) {
                    if (($fstate != "_latest_") && ($fstate != "_fixed_")) {
                        $lid = $d->getRevisionState($fstate, true);
                        if ($lid) $d = new_doc($this->dbaccess, $lid);
                        else $d = false;
                    }
                }
                if ($d && $d->isAlive()) {
                    $aid = trim(strtok($tattrids[$k], ' '));
                    $tchaps[$k] = $d->id;
                    if (!$aid) {
                        $af = $d->GetFirstFileAttributes();
                        if ($af) {
                            $aid = $af->id;
                            $tattrids[$k] = sprintf("%s (%s)", $af->id, $af->getLabel());
                            $this->setValue("coll_attrid", $tattrids);
                        }
                    }
                    
                    if ($aid) {
                        $f = $d->getRawValue($aid);
                        if ($f) {
                            $tfiles[$k] = $f;
                            $tdates[$k] = $this->getFileInfo($f, "mdate");
                        }
                        $state = $d->getState();
                        if ($d->locked == - 1) {
                            $tstates[$k] = $state ? _($state) : "";
                        } else {
                            $tstates[$k] = $d->getStateActivity(_("latest revision"));
                        }
                    }
                }
            }
        }
        
        $this->setValue("coll_chapid", $tchaps);
        $this->setValue("coll_chapstate", $tstates);
        $this->setValue("coll_chapfile", $tfiles);
        $this->setValue("coll_chapfiledate", $tdates);
        
        $tdates[] = $this->getFileInfo($this->getRawValue("coll_allott") , "mdate");
        
        $max = $this->maxdate($tdates);
        if ($max) {
            $this->setValue("coll_datemodif", $max);
        } else $this->clearValue("coll_datemodif");
        
        $maxd = FrenchDateToUnixTs($max);
        $prod = FrenchDateToUnixTs($this->getFileInfo($this->getRawValue("coll_allodt") , "mdate"));
        if ($maxd > $prod) {
            return _("the collating is not up to date. Need collate it");
        }
        return parent::postRefresh();
    }
    
    function maxdate($t)
    {
        $max = 0;
        $ki = - 1;
        foreach ($t as $k => $d) {
            $m = FrenchDateToUnixTs($d);
            if ($m > $max) {
                $max = $m;
                $ki = $k;
            }
        }
        if ($ki >= 0) return $t[$ki];
        return "";
    }
    /**
     * @apiExpose
     */
    function collating()
    {
        include_once "FDL/Lib.Vault.php";
        $ott = $this->getRawValue("coll_allott");
        $err = "";
        if ($ott) {
            $outfile = uniqid(sys_get_temp_dir() . "/merge") . ".zip";
            $tfiles = $this->getMultipleRawValues("coll_chapfile");
            $zip = new ZipArchive;
            if ($zip->open($outfile, ZIPARCHIVE::CREATE) === true) {
                $file = $this->vault_filename_fromvalue($ott, true);
                if ($file) {
                    if ($zip->addFile($file, sprintf("%05d.%s", 0, getFileExtension(basename($file))))) {
                        foreach ($tfiles as $k => $v) {
                            $file = $this->vault_filename_fromvalue($v, true);
                            if ($file) {
                                if (!$zip->addFile($file, sprintf("%05d.%s", $k + 1, getFileExtension(basename($file))))) {
                                    $err = sprintf(_("Conversion aborted: cannot compose zip archive"));
                                    break;
                                }
                            }
                        }
                    } else $err = sprintf(_("Conversion aborted: cannot compose zip archive"));
                }
                $zip->close();
            } else $err = sprintf(_("Conversion aborted: cannot create zip archive"));
            
            if ($err == "") {
                $odtfile = uniqid(sys_get_temp_dir() . "/merge") . ".odt";
                $err = convertFile($outfile, "mergeodt", $odtfile, $info);
                if ($err == "") {
                    $this->setFile("coll_allodt", $odtfile, $this->getTitle() . ".odt");
                    $err = $this->modify();
                }
                @unlink($odtfile);
            }
            @unlink($outfile);
        }
        return $err;
    }
    
    function _mergeOdt_old()
    {
        include_once "BOOK/Class.OpenDocument.php";
        $ott = $this->getRawValue("coll_allott");
        if ($ott) {
            
            $tfiles = $this->getMultipleRawValues("coll_chapfile");
            
            $this->ott = new openDocument($this->vault_filename_fromvalue($ott, true));
            
            $ottbody = $this->ott->getOfficeTag('body');
            foreach ($tfiles as $k => $v) {
                $chap = new openDocument($this->vault_filename_fromvalue($v, true));
                if ($chap) {
                    $officetextnode = $chap->getOfficeTag('text');
                    if ($officetextnode) {
                        $clone = $this->ott->domContent->importNode($officetextnode, true);
                        $ottbody->appendChild($clone);
                    }
                    $pictures = $chap->getPictures();
                    foreach ($pictures as $img) {
                        $this->ott->insertPicture($img);
                    }
                    $chap->purge();
                }
            }
            $cible = uniqid("/var/tmp/odf") . ".odt";
            $this->ott->saveAs($cible);
            $this->setFile("coll_allodt", $cible);
        }
    }
    /**
     * @begin-method-ignore
     * this part will be deleted when construct document class until end-method-ignore
     */
}
/*
 * @end-method-ignore
*/
?>
