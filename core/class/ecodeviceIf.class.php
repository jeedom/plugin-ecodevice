<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Gather all low level functions interfacing with the ecodevice
 */
class ecodeviceIf {
    
    private $name;
    private $url;
    private $_xmlstatus;
       
    public function __construct($name, $url) {
        $this->name = $name;
        $this->url = $url;
    }
    
    private function getStatus($retry=false) {
        if (isset($this->_xmlstatus)) {
            return $this->_xmlstatus;
        }
        
        $statusurl = $this->url . 'status.xml';
        
        $count = 0;
        $maxcount = $retry ? 3 : 1;
        do  {
            $this->_xmlstatus = @simplexml_load_file($statusurl);
            $count++;
            log::add(ecodevice::class, 'debug', 'retrieve ' . $statusurl . ' (try' . $count . ')');
        }
        while ($this->_xmlstatus === false && $count <= $maxcount);
        
        if ($this->_xmlstatus === false) {
            unset($this->_xmlstatus);
            $this->throwNonRespondingException();
        }
        return $this->_xmlstatus;
    }
    
    /**
     * @param string $gceid meter id (2 or 3)
     *          2=meter 1, 3=meter 2
     */
    public function getMeterType($gceid) {
        $dom = new DomDocument;
        $dom->loadHTMLFile($this->getUrl() . 'protect/settings/config12.htm');
        $xpath = new DomXPath($dom);
        $nodes = $xpath->query("//select[@name='type']/option[@selected]");
        
        return $nodes[0]->nodeValue;
    }
    
    /**
     * @param string $gceid Id of this teleinfo in ecodevice provided data
     * @throws \Exception if no data can be retrieved from the ecodevice
     * @return string either Mono or Tri
     */
    public function getPhase($gceid) {
        $phase = "";
        log::add(ecodevice::class, 'debug', 'get file ' . $this->url . 'protect/settings/teleinfo' . $gceid . '.xml');
        $this->_xmlstatus = @simplexml_load_file($this->url . 'protect/settings/teleinfo' . $gceid . '.xml');
        if ($this->_xmlstatus === false) {
            $this->throwNonRespondingException();
        }
        $xpathModele = '//T' . $gceid . '_IMAX2';
        $status      = $this->_xmlstatus->xpath($xpathModele);
        
        if (count($status) != 0) {
            if ($status[0] != "0") {
                $phase =  "Tri";
            }
        }
        if ($phase == "") {
            $xpathModele = '//T' . $gceid . '_IMAX';
            $status      = $this->_xmlstatus->xpath($xpathModele);
            
            if (count($status) != 0) {
                if ($status[0] != "0") {
                    $phase = "Mono";
                }
            }
        }
        
        log::add(ecodevice::class, 'debug', 'detection phase: ' . $phase);
        return $phase;
    }
    
    private function throwNonRespondingException() {
        throw new \Exception(__('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->name);
    }
}