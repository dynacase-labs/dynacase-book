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
Class _BOOK extends Dir
{
    /*
     * @end-method-ignore
    */
    public $defaultview = "BOOK:VIEWBOOK";
    private $ispdf;
    
    function specRefresh()
    {
        
        $this->AddParamRefresh("book_tplodt", "book_headleft,book_headmiddle,book_headright,book_footleft,book_footmiddle,book_footright,book_tplodt");
    }
    /**
     * @param string $target
     * @param bool $ulink
     * @param bool $abstract
     * @templateController
     */
    function viewbook($target = "_self", $ulink = true, $abstract = false)
    {
        include_once "FDL/Lib.Dir.php";
        global $action;
        $action->parent->AddJsRef($action->GetParam("CORE_PUBURL") . "/FREEDOM/Layout/fdl_tooltip.js");
        
        $this->lay->set("stylesheet", ($this->getRawValue("book_tplodt") != ""));
        $this->viewdefaultcard($target, $ulink, $abstract);
    }
    /**
     * Return list of chapters
     * @return array of document array
     */
    function getChapters()
    {
        
        $filter[] = "chap_bookid='" . $this->initid . "'";
        $filter[] = "doctype!='T'";
        $search = new SearchDoc("", "CHAPTER");
        foreach ($filter as $currentFilter) {
            $search->addFilter($currentFilter);
        }
        
        $chapters = $search->search();
        
        return $chapters;
    }
    /**
     * to sort chapters by level
     */
    static function _cmplevel($a, $b)
    {
        
        $tv1 = array_pad((explode(".", $a['chap_level'])) , 5, 0);
        $tv2 = array_pad((explode(".", $b['chap_level'])) , 5, 0);
        $iv1 = '';
        $iv2 = '';
        foreach ($tv1 as $v) {
            $iv1.= sprintf("%02d", $v);
        }
        foreach ($tv2 as $v) {
            $iv2.= sprintf("%02d", $v);
        }
        
        return strcmp($iv1, $iv2);
    }
    /**
     * @templateController
     */
    function gentdm()
    {
        
        $chapters = $this->getChapters();
        
        foreach ($chapters as $k => $chap) {
            $chapters[$k]["level"] = (count(explode(".", $chap["chap_level"])) - 1) * 15;
            if (controlTdoc($chap, "edit") && (($chap["locked"] == 0) || (abs($chap["locked"]) == $this->userid))) {
                $chapters[$k]["icon"] = $this->getIcon($chap["icon"]);
            } else {
                $chapters[$k]["icon"] = false;
            }
            $chapters[$k]["chap_comment"] = str_replace(array(
                '"',
                "\n",
                "\r"
            ) , array(
                "rsquo;",
                '<br>',
                ''
            ) , $chap["chap_comment"]);
        }
        uasort($chapters, array(
            get_class($this) ,
            "_cmplevel"
        ));
        
        $this->lay->setBlockData("CHAPTERS", $chapters);
    }
    /**
     * @param string $target
     * @param bool $ulink
     * @param bool $abstract
     * @templateController
     */
    function openbook($target = "_self", $ulink = true, $abstract = false)
    {
        $this->viewbook($target, $ulink, $abstract);
        $this->gentdm($target, $ulink, $abstract);
        
        $chapid = getFamIdFromName($this->dbaccess, "CHAPTER");
        $filter = array();
        $filter[] = "fromid != $chapid";
        $tannx = $this->getContent(true, $filter);
        
        foreach ($tannx as $k => $chap) {
            $tannx[$k]["icon"] = $this->getIcon($chap["icon"]);
        }
        
        $this->lay->setBlockData("ANNX", $tannx);
    }
    /**
     * @param string $target
     * @param bool $ulink
     * @param bool $abstract
     * @templateController
     */
    function genhtml($target = "_self", $ulink = true, $abstract = false)
    {
        $this->viewbook($target, $ulink, $abstract);
        $this->gentdm($target, $ulink, $abstract);
        $chapters = $this->lay->getBlockData("CHAPTERS");
        
        $chapter0 = array();
        foreach ($chapters as $k => $chap) {
            $chapters[$k]["hlevel"] = (count(explode(".", $chap["chap_level"])));
            if ($chap["chap_level"][0] == "0") {
                $chapter0[$k] = $chapters[$k];
                unset($chapters[$k]);
            }
        }
        $this->lay->setBlockData("CHAPTER0", $chapter0);
        $this->lay->setBlockData("CHAPTERS", $chapters);
        $this->lay->set("booktitle", $this->title);
        $this->lay->set("has0", (count($chapter0) > 0));
        $this->lay->set("stylesheet", ($this->ispdf && ($this->getRawValue("book_tplodt") != "")));
        if ($this->ispdf) {
            $this->lay->set("HL", $this->hftoooo($this->getRawValue("book_headleft")));
            $this->lay->set("HM", $this->hftoooo($this->getRawValue("book_headmiddle")));
            $this->lay->set("HR", $this->hftoooo($this->getRawValue("book_headright")));
            $this->lay->set("FL", $this->hftoooo($this->getRawValue("book_footleft")));
            $this->lay->set("FM", $this->hftoooo($this->getRawValue("book_footmiddle")));
            $this->lay->set("FR", $this->hftoooo($this->getRawValue("book_footright")));
            $this->lay->set("toc", ($this->getRawValue("book_toc") == "yes"));
        } else {
            $this->lay->set("toc", false);
        }
        $this->lay->set("ispdf", ($this->ispdf == true));
    }
    
    function hftocss($hf)
    {
        $hf = str_replace('"', ' ', $hf);
        $hf = str_replace("##PAGES##", '" counter(pages) "', $hf);
        $hf = str_replace("##PAGE##", '" counter(page) "', $hf);
        return '"' . $hf . '"';
    }
    function hftoooo($hf)
    {
        
        $hf = str_replace("##PAGES##", "<SDFIELD TYPE=DOCSTAT SUBTYPE=PAGE FORMAT=PAGE>1</SDFIELD>", $hf);
        $hf = str_replace("##PAGE##", "<SDFIELD TYPE=PAGE SUBTYPE=RANDOM FORMAT=PAGE>1</SDFIELD>", $hf);
        return $hf;
    }
    /**
     * @param Dir $copyfrom
     * @return string|void
     */
    function postCopy(&$copyfrom)
    {
        include_once "FDL/Lib.Dir.php";
        $filter[] = "chap_bookid=" . $copyfrom->initid;
        $filter[] = "doctype!='T'";
        
        $search = new SearchDoc("", "CHAPTER");
        foreach ($filter as $currentFilter) {
            $search->addFilter($currentFilter);
        }
        
        $chapters = $search->search();
        
        $this->clearValue("book_pdf");
        $this->clearValue("book_datepdf");
        $err = "";
        foreach ($chapters as $chap) {
            $nc = getDocObject($this->dbaccess, $chap);
            $copy = $nc->duplicate();
            if (!is_object($copy)) $err.= $copy;
            else {
                $copy->setValue("chap_bookid", $this->initid);
                $copy->modify();
                $this->insertDocument($copy->initid);
            }
        }
        
        $chapid = getFamIdFromName($this->dbaccess, "CHAPTER");
        $filter = array();
        $filter[] = "fromid != $chapid";
        $tannx = $copyfrom->getContent(true, $filter);
        foreach ($tannx as $v) {
            $this->insertDocument($v["initid"]);
        }
    }
    function postDelete()
    {
        include_once "FDL/Lib.Dir.php";
        $filter[] = "chap_bookid=" . $this->initid;
        $filter[] = "doctype!='T'";
        
        $search = new SearchDoc("", "CHAPTER");
        foreach ($filter as $currentFilter) {
            $search->addFilter($currentFilter);
        }
        
        $chapters = $search->search();
        $err = "";
        foreach ($chapters as $chap) {
            $nc = getDocObject($this->dbaccess, $chap);
            $err.= $nc->delete();
        }
        return $err;
    }
    /**
     * send a request to TE to convert file to PDF
     * @templateController
     *
     */
    public function genpdf()
    {
        include_once "FDL/Lib.Vault.php";
        $tea = getParam("TE_ACTIVATE");
        if ($tea != "yes") {
            addWarningMsg(_("TE engine not activated"));
            return;
        }
        if (@include_once ("WHAT/Class.TEClient.php")) {
            include_once "FDL/Class.TaskRequest.php";
            global $action;
            $action->parent->AddJsRef($action->GetParam("CORE_PUBURL") . "/BOOK/Layout/genpdf.js");
            
            $this->ispdf = true;
            $html = $this->viewDoc("BOOK:GENHTML:S");
            $this->ispdf = false;
            
            $this->lay->set("docid", $this->id);
            $this->lay->set("title", $this->title);
            $va = $this->getRawValue("book_pdf");
            if (preg_match(PREGEXPFILE, $va, $reg)) {
                $vid = $reg[2];
                
                $ofout = new VaultDiskStorage($this->dbaccess, $vid);
                $ofout->teng_state = 2;
                $ofout->modify();
            } else {
                // create first
                $filename = uniqid("/var/tmp/conv") . ".txt";
                file_put_contents($filename, "-");
                $vf = newFreeVaultFile($this->dbaccess);
                $vid = 0;
                $err = $vf->Store($filename, false, $vid);
                unlink($filename);
                if ($err == "") {
                    $vf->storage->teng_state = 2;
                    $vf->storage->modify();
                    $mime = "application/pdf";
                    $this->setValue("book_pdf", "$mime|$vid");
                    $this->modify();
                }
            }
            
            if ($this->getRawValue("book_tplodt")) {
                $engine = 'odt';
                $urlindex = getOpenTeUrl(array(
                    "app" => "FDL",
                    "action" => "FDL_METHOD",
                    "method" => "ooo2pdf",
                    "id" => $this->id
                ));
                $callback = $urlindex . "&sole=Y&app=FDL&action=FDL_METHOD&redirect=no&method=ooo2pdf&id=" . $this->id;
            } else {
                $urlindex = getOpenTeUrl();
                $engine = 'pdf';
                $callback = $urlindex . "&sole=Y&app=FDL&action=INSERTFILE&engine=$engine&vidout=$vid&name=" . urlencode($this->title) . ".pdf";
            }
            $ot = new TransformationEngine(getParam("TE_HOST") , getParam("TE_PORT"));
            $html = preg_replace('/<font([^>]*)face="([^"]*)"/is', "<font\\1", $html);
            $html = preg_replace(array(
                "/SRC=\"([^\"]+)\"/e",
                "/src=\"([^\"]+)\"/e"
            ) , "\$this->srcfile('\\1')", $html);
            $html = preg_replace(array(
                '/size="([1-9])"/e',
                '/size=([1-9])/e',
                '/font-size: medium;/e'
            ) , "", $html); // delete font size
            $html = str_replace('<table ', '<table style=" page-break-inside: avoid;" ', $html);
            
            $filename = uniqid("/var/tmp/txt-") . '.html';
            file_put_contents($filename, $html);
            $info = "";
            $err = $ot->sendTransformation($engine, $vid, $filename, $callback, $info);
            
            @unlink($filename);
            if ($err == "") {
                global $action;
                $tr = new TaskRequest($this->dbaccess);
                $tr->tid = $info["tid"];
                $tr->fkey = $vid;
                $tr->status = $info["status"];
                $tr->comment = $info["comment"];
                $tr->uid = $this->userid;
                $tr->uname = $action->user->firstname . " " . $action->user->lastname;
                $tr->Add();
            } else {
                $vf = initVaultAccess();
                $filename = uniqid("/var/tmp/txt-" . $vid . '-');
                file_put_contents($filename, $err);
                $vf->Retrieve($vid, $info);
                $vf->Save($filename, false, $vid);
                @unlink($filename);
                $vf->rename($vid, _("impossible conversion") . ".txt");
                $vf->storage->teng_state = - 2;
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
    public function ooo2pdf()
    {
        include_once "FDL/insertfile.php";
        include_once "FDL/Lib.Vault.php";
        $tea = getParam("TE_ACTIVATE");
        if ($tea != "yes") {
            addWarningMsg(_("TE engine not activated"));
            return;
        }
        if (@include_once ("WHAT/Class.TEClient.php")) {
            include_once "FDL/Class.TaskRequest.php";
            
            $tid = GetHttpVars("tid");
            
            $filename = uniqid("/var/tmp/txt-") . '.odt';
            $err = getTEFile($tid, $filename, $info);
            if ($err == "") {
                // add style sheet
                $ott = $this->getRawValue("book_tplodt");
                if ($ott) {
                    $this->insertstyle($filename, $this->vault_filename("book_tplodt", true));
                }
                
                $va = $this->getRawValue("book_pdf");
                $vid = "";
                if (preg_match(PREGEXPFILE, $va, $reg)) $vid = $reg[2];
                
                $engine = 'pdf';
                
                $urlindex = getOpenTeUrl();
                $callback = $urlindex . "&sole=Y&app=FDL&action=INSERTFILE&engine=$engine&vidout=$vid&name=" . urlencode($this->title) . ".pdf";
                $ot = new TransformationEngine(getParam("TE_HOST") , getParam("TE_PORT"));
                
                $err = $ot->sendTransformation($engine, $vid, $filename, $callback, $info);
                @unlink($filename);
                if ($err == "") {
                    global $action;
                    $tr = new TaskRequest($this->dbaccess);
                    $tr->tid = $info["tid"];
                    $tr->fkey = $vid;
                    $tr->status = $info["status"];
                    $tr->comment = $info["comment"];
                    $tr->uid = $this->userid;
                    $tr->uname = $action->user->firstname . " " . $action->user->lastname;
                    $tr->Add();
                } else {
                    $vf = initVaultAccess();
                    $filename = uniqid("/var/tmp/txt-" . $vid . '-');
                    file_put_contents($filename, $err);
                    $vf->Retrieve($vid, $info);
                    $vf->Save($filename, false, $vid);
                    @unlink($filename);
                    $vf->rename($vid, _("impossible conversion") . ".txt");
                    $vf->storage->teng_state = - 2;
                    $vf->storage->modify();;
                }
            }
        } else {
            addWarningMsg(_("TE engine activate but TE-CLIENT not found"));
        }
    }
    
    function insertstyle($odt, $ott)
    {
        if (!file_exists($odt)) return "file $odt not found";
        $dodt = uniqid("/var/tmp/odt");
        $cmd = sprintf("unzip  %s  -d %s >/dev/null", $odt, $dodt);
        system($cmd);
        
        $dott = uniqid("/var/tmp/ott");
        $cmd = sprintf("unzip  %s  -d %s >/dev/null", $ott, $dott);
        system($cmd);
        
        $cmd = sprintf("cp %s/styles.xml  %s >/dev/null", $dott, $dodt);
        system($cmd);
        
        $cmd = sprintf("sed -i -e 's/style:master-page-name=\"HTML\"//g' %s/content.xml", $dodt);
        system($cmd);
        $cmd = sprintf("sed -i -e 's!href=\"../../../!href=\"/var/!g' %s/content.xml", $dodt);
        system($cmd);
        if (is_dir("$dott/Pictures")) {
            if (!is_dir("$dodt/Pictures")) mkdir("$dodt/Pictures");
            $cmd = sprintf("cp -r %s/Pictures/*  %s/Pictures >/dev/null", $dott, $dodt);
            system($cmd);
        }
        $cmd = sprintf("cd %s;zip -r %s * >/dev/null", $dodt, $odt);
        system($cmd);
        
        $cmd = sprintf("/bin/rm -fr %s", $dodt);
        system($cmd);
        
        $cmd = sprintf("/bin/rm -fr %s", $dott);
        system($cmd);
        return "";
    }
    function srcfile($src)
    {
        $vext = array(
            "gif",
            "png",
            "jpg",
            "jpeg",
            "bmp"
        );
        
        if (preg_match("/vid=([0-9]+)/", $src, $reg)) {
            $info = vault_properties($reg[1]);
            if (!in_array(strtolower(fileextension($info->path)) , $vext)) return "";
            
            return 'src="file://' . $info->path . '"';
        }
        
        $src = html_entity_decode($src);
        $url = parse_url($src);
        
        $argv = array();
        foreach (explode('&', $url['query']) as $arg) {
            $v = explode('=', $arg);
            if ($v[0] !== '') {
                $argv[$v[0]] = rawurldecode($v[1]);
            }
        }
        
        if (preg_match("/^\s*$/", $argv['docid'])) {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Empty '%s' in '%s'.", 'docid', $src) , HISTO_ERROR);
            return "";
        }
        $docid = $argv['docid'];
        
        if (preg_match("/^\s*$/", $argv['attrid'])) {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Empty '%s' in '%s'.", 'attrid', $src) , HISTO_ERROR);
            return "";
        }
        $attrid = $argv['attrid'];
        
        if (preg_match("/^\s*$/", $argv['index'])) {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Empty '%s' in '%s'.", 'index', $src) , HISTO_ERROR);
            return "";
        }
        $index = $argv['index'];
        
        $doc = new_Doc($this->dbaccess, $docid);
        if (!is_object($doc) || !$doc->isAlive()) {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Document with id '%s' does not exists or is not alive.", $docid) , HISTO_ERROR);
            return "";
        }
        
        if ($index < 0) {
            $file = $doc->getValue($attrid);
        } else {
            $tvalue = $doc->getTValue($attrid);
            if ($index >= count($tvalue)) {
                $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Out of range index '%s' for attrid '%s' in document '%s'.", $index, $attrid, $docid) , HISTO_ERROR);
                return "";
            }
            $file = $tvalue[$index];
        }
        if ($file == '') {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Empty attr '%s[%s]' in document '%s'.", $attrid, $index, $docid) , HISTO_ERROR);
            return "";
        }
        
        $path = $this->vault_filename_fromvalue($file, true);
        if ($path == '') {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("Empty path for file in attr '%s[%s]' in document '%s'.", $attrid, $index, $docid) , HISTO_ERROR);
            return "";
        }
        if (!file_exists($path)) {
            $this->addHistoryEntry(__CLASS__ . "::" . __FUNCTION__ . " " . sprintf("File '%s' in attr '%s[%s]' in document '%s' does not exists.", $path, $attrid, $index, $docid) , HISTO_ERROR);
            return "";
        }
        
        $path = sprintf('src="file://%s"', $path);
        
        return $path;
    }
    
    function getFileDate($va)
    {
        if (preg_match(PREGEXPFILE, $va, $reg)) {
            include_once "VAULT/Class.VaultDiskStorage.php";
            $vid = $reg[2];
            
            $ofout = new VaultDiskStorage($this->dbaccess, $vid);
            
            return $ofout->mdate;
        }
        return "";
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
