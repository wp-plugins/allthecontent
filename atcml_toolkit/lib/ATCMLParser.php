<?php

include('functions.php');

/**
 * ATCML Parser
 * Simple ATCML parser.
 * @author Vincent Buzzano, ATC Future Medias SA
 * @version 1.1 - 2015-01-08
 */
class ATCMLParser {

    /**
     * Parse Content
     * @param xml (SimpleXML)
     * @return array
     */
    public function parseContent($file) {

        // load xml file
        $xml = simplexml_load_file($file);
        if ($xml == null)
          throw new Exception("Error reading atcml file");

        $filename = trim(basename($file));
        $path = trim(pathinfo ($file, PATHINFO_DIRNAME));

        // get delivery infos
        $delivery_id      = $this->getDeliveryId($filename);
        $delivery_date    = $this->getDeliveryDate($file);

        // add namespace
        $xml->registerXPathNamespace('c', 'http://www.allthecontent.com/xml/delivery/generic/contents');

        // read content
        $content = array();

        $content['uid']             = $this->getUID($xml);
        $content['refid']           = $this->getRefId($xml);

        $content['title']           = $this->getTitle($xml);
        $content['description']     = $this->getDescription($xml);
        $content['credits']         = $this->getCredits($xml);
        $content['publicationDate'] = $this->getPubDate($xml);
        $content['language']        = $this->getLang($xml);
        $content['contentType']     = $this->getContentType($xml);
        $content['coreMedia']       = $this->getCoreMedia($xml);
        $content['format']          = $this->getFormat($xml);
        $content['themes']          = $this->getThemes($xml);
        $content['license']         = $this->getLicense($xml);
        $content['keywords']        = $this->getKeywords($xml);
        $content['tags']            = $this->getTags($xml);
        $content['characters']      = $this->getCharactersCount($xml);
        $content['words']           = $this->getWordsCount($xml);
        $content['links']           = $this->getLinks($xml);
        $content['attachments']     = $this->getAttachments($xml);
        $content['items']           = $this->getItems($xml);

        // version
        $content['version']         = $this->getVersion($xml);
        $content['versionDate']     = $this->getVersionDate($xml);
        if (is_null($content['versionDate']))
            $content['versionDate'] = $content['publicationDate'];

        // delivery info
        $content['_deliveryId']      = $delivery_id;
        $content['_deliveryDate']    = $delivery_date;
        $content['_atcmlVersion']    = $this->getATCMLVersion($xml);
        $content['_atcmlCreated']    = $this->getATCMLCreated($xml);
        $content['_path']            = $path;
        $content['_filename']        = $filename;

        // return content
        return $content;
    }

    /**
     * Get Delivery Id
     * @param filename
     * @return string (delivery_id)
     */
    public function getDeliveryId($filename) {
        $delivery_id = array();
        preg_match("/[0-9a-fA-F]{24}/", $filename, $delivery_id);
        if (count($delivery_id) > 0)
            $delivery_id = $delivery_id[0];
        else $delivery_id = null;
        return $delivery_id;
    }

    /**
     * Get Delivery Date
     * @param file
     * @return datetime (delivery_date)
     */
    private function getDeliveryDate($file) {
        $date = new DateTime(date('Y-m-d H:i:s e', filemtime($file)));
        return $date;
    }

    private function getATCMLCreated($xml) {
        return $this->getNodeDate($xml, "/c:allthecontent/@created", null);
    }

    private function getATCMLVersion($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/@version", "1.0");
    }

    /**
     * Get UID
     * @param xml (SimpleXML)
     * @return uid
     */
    private function getUID($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/@uid", null);
    }

    /**
     * Get RefId
     * @param xml (SimpleXML)
     * @return refid
     */
    private function getRefId($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/@refid", null);
    }

    /**
     * Get Content Version number
     * @param xml (SimpleXML)
     * @return number
     */
    private function getVersion($xml) {
        $l = $this->getNodeText($xml, "/c:allthecontent/c:content/@version", 1);
        return $this->asInt($l);
    }

    /**
     * Get Version date
     * @param xml (SimpleXML)
     * @return time
     */
    private function getVersionDate($xml) {
        return $this->getNodeDate($xml, "/c:allthecontent/c:content/@versiondate", null);
    }

    /**
     * Get Title
     * @param xml (SimpleXML)
     * @return title
     */
    private function getTitle($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:title", "Untitled");
    }

    /**
     * Get Description
     * @param xml (SimpleXML)
     * @return description
     */
    private function getDescription($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:description", null);
    }

    /**
     * Get Credits
     * @param xml (SimpleXML)
     * @return credits
     */
    private function getCredits($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:credits", null);
    }

    /**
     * Get Publication date
     * @param xml (SimpleXML)
     * @return time
     */
    private function getPubDate($xml) {
        return $this->getNodeDate($xml, "/c:allthecontent/c:content/c:pubdate", 'now');
    }

    /**
     * Get Language
     * @param xml (SimpleXML)
     * @return lang code
     */
    private function getLang($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:lang/@code", "none");
    }

    /**
     * Get ContentType
     * @param xml (SimpleXML)
     * @return code
     */
    private function getContentType($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:contenttype/@code", "");
    }

    /**
     * Get Coremedia
     * @param xml (SimpleXML)
     * @return code
     */
    private function getCoreMedia($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:coremedia/@code", "");
    }

    /**
     * Get Format
     * @param xml (SimpleXML)
     * @return code
     */
    function getFormat($xml) {
        return $this->getNodeText($xml, "/c:allthecontent/c:content/c:format/@code", "");
    }

    /**
     * Get License
     * @param xml (SimpleXML)
     * @return array
     */
    private function getLicense($xml) {
        $l = $this->getNode($xml, "/c:allthecontent/c:content/c:license");
        $v = array();
        if (is_object($l)) {
            foreach($l->children() as $k)
                $v[$k->getName()] = ($this->asString($k) == "true" ? "yes":"no");
        }
        return $v;
    }

    /**
     * Get KeyWords
     * @param xml (SimpleXML)
     * @return array
     */
    private function getKeywords($xml) {
        $l = $this->getNode($xml, "/c:allthecontent/c:content/c:keywords");
        $v = array();
        if (is_object($l))
            foreach($l->children() as $k)
                $v[] = $this->asString($k);
        return $v;
    }

    /**
     * Get Tags
     * @param xml (SimpleXML)
     * @return array(array)
     */
    private function getTags($xml) {
        $l = $this->getNodeList($xml, "/c:allthecontent/c:content/c:tag");
        $tags = array();
        foreach($l as $t) {
            $atts = $t->attributes();
            $code = $this->asString($atts['code']);
            if ($code == 'characters' || $code == 'words')
                continue;
            $values = array();
            foreach($t->children() as $v) {
                $values[] = $this->asString($v);
            }
            $tags[$code] = $values;
        }
        return $tags;
    }

    /**
     * Get Links
     * @param xml (SimpleXML)
     * @return array(array)
     */
    private function getLinks($xml) {
       $l = $this->getNodeList($xml, "/c:allthecontent/c:content/c:link");
        $links = array();
        foreach($l as $t) {
            $atts = $t->attributes();
            $url = $this->asString($atts['url']);
            $name = $this->asString($t);
            $links[$url] = $name;
        }
        return $links;
    }

    /**
     * Get Attachments
     * @param xml (SimpleXML)
     * @return array(array)
     */
    private function getAttachments($xml) {
        $l = $this->getNodeList($xml, "/c:allthecontent/c:content/c:attachment");
        $attachments = array();
        foreach($l as $t) {
            $atts = $t->attributes();
            $attachment = array();
            $attachment['uid']      = $this->asString($atts['uid']);
            $attachment['type']     = $this->asString($atts['type']);
            $attachment['format']   = $this->asString($atts['format']);
            $attachment['mimetype'] = $this->asString($atts['mimetype']);

            foreach($t->children() as $child) {
                if ($child->getName() == "description")
                    $attachment['description'] = $this->asString($child);
                else if (($child->getName() == "credits"))
                    $attachment['credits'] = $this->asString($child);
            }
            $attachment['filename'] = $this->asString($atts['filename']);
            $attachments[] = $attachment;
        }
        return $attachments;
    }

    /**
     * Get Items
     * @param xml (SimpleXML)
     * @return array(array)
     */
    private function getItems($xml) {
        $l = $this->getNodeList($xml, "/c:allthecontent/c:content/c:item");
        $items = array();
        foreach($l as $t) {
            $atts = $t->attributes();
            $item = array();
            $item['uid']      = $this->asString($atts['uid']);
            $item['mimetype'] = $this->asString($atts['mimetype']);

            if (isset($atts['filename'])) {
                $filename = $this->asString($atts['filename']);
                $item['filename'] = $filename;
            } else if (isset($t)) {
                $item['content'] = $this->asString($t);
            }

            $items[] = $item;
        }
        return $items;
    }

    /**
     * Get Number of characters in the content
     * @param xml (SimpleXML)
     * @return number
     */
    private function getCharactersCount($xml) {
        $l = $this->getNodeList($xml, "/c:allthecontent/c:content/c:tag[@code='characters']");
        if (count($l) == 0) return 0;
        $n = $l[0]->children();
        if (count($n) == 0) return 0;
        return $this->asInt($n[0]);
    }

    /**
     * Get Number of words in the content
     * @param xml (SimpleXML)
     * @return number
     */
    private function getWordsCount($xml) {
        $l = $this->getNodeList($xml, "/c:allthecontent/c:content/c:tag[@code='words']");
        if (count($l) == 0) return 0;
        $n = $l[0]->children();
        if (count($n) == 0) return 0;
        return $this->asInt($n[0]);
    }

    /**
     * Get Themes
     * @param xml (SimpleXML)
     * @return code
     */
    private function getThemes($xml) {
        $result = $this->getNodeList($xml, "/c:allthecontent/c:content/c:theme/@code");
        $ar = array();
        foreach($result as $theme) {
            $ar[] = $this->asString($theme);
        }
        return $ar;
    }

    private function getNodeText($xml, $xpath, $default = null) {
        $result = $this->getNode($xml, $xpath);
        if (!is_null($result) && isset($result)) return $this->asString($result);
        else return $default;
    }

    private function getNodeDate($xml, $xpath, $default = null) {
        $result = $this->getNode($xml, $xpath);
        if (!is_null($result) && isset($result)) return $this->asDateTime($result);
        else return $this->asDateTime($default);
    }

    private function getNode($xml, $xpath) {
        $result = $this->getNodeList($xml,$xpath);
        if (count($result) > 0) return $result[0];
        else return null;
    }

    private function getNodeList($xml, $xpath) {
        return $xml->xpath($xpath);
    }

    private function asString($value) {
//        if (is_object($value)) return $value->__toString();
        if (is_object($value)) return (string) $value;
        else if (isset($value)) return (string) $value;
        else return null;
    }

    private function asInt($value) {
        if (is_object($value)) return intval($this->asString($value));
        else if (isset($value)) return intval($value);
        else return null;
    }

    private function asDateTime($value) {
        if (is_object($value)) return new DateTime($this->asString($value));
        else if (isset($value)) return new DateTime($value);
        else return null;
    }

}
?>