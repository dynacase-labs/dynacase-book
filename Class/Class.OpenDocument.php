<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package BOOK
*/
/**
 * Document Object Definition
 */

namespace Dcp\Book;

Class openDocument
{
    public $content = "";
    public $styles = "";
    
    function __construct($odsfile)
    {
        if (!file_exists($odsfile)) return "file $odsfile not found";
        
        $this->cibledir = uniqid("/var/tmp/odf");
        
        $cmd = sprintf("unzip  %s  -d %s >/dev/null", escapeshellarg($odsfile) , escapeshellarg($this->cibledir));
        system($cmd);
        
        $contentxml = $this->cibledir . "/content.xml";
        if (file_exists($contentxml)) {
            $this->content = file_get_contents($contentxml);
            $this->domContent = new \DOMDocument();
            $this->domContent->loadXML($this->content);
        }
        $stylexml = $this->cibledir . "/styles.xml";
        if (file_exists($stylexml)) {
            $this->styles = file_get_contents($contentxml);
        }
        return "";
    }
    
    function getOfficeTag($tag)
    {
        if ($this->domContent) {
            $lists = $this->domContent->getElementsByTagNameNS("urn:oasis:names:tc:opendocument:xmlns:office:1.0", $tag);
            if ($lists->length > 0) {
                return $lists->item(0);
            }
        }
        return false;
    }
    
    function getContent()
    {
        if (is_object($this->domContent)) {
            return $this->domContent->saveXML();
        }
        return "";
    }
    
    function changeContent($xml)
    {
        if (is_object($this->domContent)) {
            $contentxml = $this->cibledir . "/content.xml";
            file_put_contents($contentxml, $xml);
            // $contentxml=$this->cibledir."/styles.xml";
            // file_put_contents($contentxml,$this->style_template);
            
        }
    }
    
    function insertPicture($img)
    {
        if ($this->cibledir) {
            $dirpictures = $this->cibledir . "/Pictures/";
            if (!is_dir($dirpictures)) {
                mkdir($dirpictures);
            }
            return copy($img, $dirpictures . basename($img));
        }
        return "";
    }
    
    function getPictures()
    {
        $tp = array();
        if ($this->cibledir) {
            $dirpictures = $this->cibledir . "/Pictures/";
            
            if (is_dir($dirpictures)) {
                if ($dh = opendir($dirpictures)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file[0] != '.') {
                            $tp[] = $dirpictures . $file;
                        }
                    }
                    closedir($dh);
                }
            }
        }
        return $tp;
    }
    function saveAs($cible)
    {
        if (file_exists($cible)) return "file $cible must not be present";
        
        $this->changeContent($this->getContent());
        $cmd = sprintf("cd %s;zip -r %s * >/dev/null", escapeshellarg($this->cibledir) , escapeshellarg($cible));
        
        system($cmd);
        return "";
    }
    function purge()
    {
        if ($this->cibledir && substr($this->cibledir, 0, 12) == "/var/tmp/odf") {
            $contentxml = $this->cibledir . "/content.xml";
            if (file_exists($contentxml)) {
                $this->changeContent($this->getContent());
                $cmd = sprintf("/bin/rm -fr %s  >/dev/null", escapeshellarg($this->cibledir));
                
                system($cmd);
            }
        }
    }
    
    function innerXML(&$node)
    {
        /**
         * @var \DOMNode $node
         */
        if (!$node) return false;
        $document = $node->ownerDocument;
        $nodeAsString = $document->saveXML($node);
        preg_match('!\<.*?\>(.*)\</.*?\>!s', $nodeAsString, $match);
        return $match[1];
    }
}
