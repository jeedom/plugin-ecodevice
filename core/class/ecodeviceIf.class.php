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

require_once 'ecodeviceLog.trait.php';

/**
 * Gather all low level functions interfacing with the ecodevice
 */
class ecodeviceIf {
    
    use ecodeviceLog;
    
    const CTYP_EAU = "Eau";
    const CTYP_GAZ = "Gaz";
    const CTYP_ELEC = "Electricité";
    const CTYP_FUEL = "Fuel";
    
    const CPHASE_MONO = "Mono";
    const CPHASE_TRI = "Tri";
    
    private $name;
    private $url;
    
    /**
     * Never use directly, always self::getStatus instead
     * @var SimpleXMLElement 
     */
    private $_xmlstatus_cache;
    
    private $_xmlteleinfo_cache = array();
    
    
    public function __construct($name, $url) {
        $this->name = $name;
        $this->url = $url;
    }
    
    /**
     * Retrieve the given file from the Eco-device and return an XML object
     * @param string $url
     * @param boolean $retry whether or not we shall retry the retrieval in case of connection failure
     * @return SimpleXMLElement|bool (or false on failure)
     */
    private function loadFile($url, $retry=false) {
        $count = 0;
        $maxcount = $retry ? 3 : 1;
        do  {
            $file = @simplexml_load_file($url);
            $count++;
            $this->addLog('debug', 'retrieve ' . $url . ' (try' . $count . ')');
        }
        while ($file === false && $count <= $maxcount);
        
        return $file;
    }
    
    /**
     * Return the status XML file
     * @param boolean $retry whether or not we shall retry the retrieval in case of connection failure
     * @return SimpleXMLElement SimpleXMLElement object
     * @throw \Exception in case of connection failure
     */
    private function getXMLStatus($retry=false) {
        if (isset($this->_xmlstatus_cache)) {
            return $this->_xmlstatus_cache;
        }        
        $this->_xmlstatus_cache = $this->loadFile($this->url . 'status.xml', $retry);
        if ($this->_xmlstatus_cache === false) {
            unset($this->_xmlstatus_cache);
            $this->throwNonResponsiveException();
        }
        return $this->_xmlstatus_cache;
    }
    
    /**
     * Return the teleinfo XML file of the given teleinfo id
     * @param string $gceid id of the Teleinfo (1 or 2)
     * @param boolean $retry whether or not we shall retry the retrieval in case of connection failure
     * @return SimpleXMLElement SimpleXMLElement object
     * @throw \Exception in case of connection failure with the Eco-device
     */
    private function getXMLTeleinfo($gceid, $retry=false) {
        if (array_key_exists($gceid, $this->_xmlteleinfo_cache)) {
            return $this->_xmlteleinfo_cache[$gceid];
        }
        $this->_xmlteleinfo_cache[$gceid] = $this->loadFile($this->url . 'protect/settings/teleinfo' . $gceid . '.xml', $retry);
        if ($this->_xmlteleinfo_cache[$gceid] === false) {
            unset($this->_xmlteleinfo_cache[$gceid]);
            $this->throwNonResponsiveException();
        }
        return $this->_xmlteleinfo_cache[$gceid];
    }
    
    /**
     * Check whether or not the Ecodevice is responsive
     * @throw \Exception if the Ecodevice is not responsive
     */
    public function checkIsResponsive() {
        $this->getXMLStatus(true);
    }
    
    /**
     * Return the compteur type of the given compteur id
     * @param string $gceid compteur id (0 or 1)
     * @return string among self::CTYP_EAU, self::CTYP_GAZ, self::CTYP_ELEC, self::CTYP_FUEL
     */
    public function getCompteurType($gceid) {
        $dom = DOMDocument::loadHTMLFile($this->url . 'protect/settings/config1' . ($gceid+2) . '.htm');
        if ($dom === false) {
            $this->throwNonResponsiveException();
        }
        $xpath = new DomXPath($dom);
        $nodes = $xpath->query("//select[@name='type']/option[@selected]");
        
        return $nodes[0]->nodeValue;
    }
    
    /**
     * Return the requested teleinfo data for the given Teleinfo meter
     * @param int $gceid  id of the Teleinfo (1 or 2)
     * @param string $dataId
     * @param boolean $retry
     * @return boolean|string requested teleinfo data or false if dataId is not found
     * @throw \Exception in case of connection failure with the Eco-device
     */
    public function getTeleinfoData($gceid, $dataId, $retry=false) {
        $xml = $this->getXMLTeleinfo($gceid, $retry);
        $path = '//T' . $gceid . '_' . $dataId;
        $data = $xml->xpath($path);
        if ($data === false || count($data) != 1)
            return false;
        else
            return (string) $data[0];        
    }
    
    
    /**
     * @param string $gceid id of the Teleinfo (1 or 2)
     * @throw \Exception in case of connection failure with the Eco-device
     * @return string self::CPHASE_MONO or self::CPHASE_TRI
     */
    public function getTeleinfoPhase($gceid) {
        $phase = "";
        $imax2 = $this->getTeleinfoData($gceid, 'IMAX2', true);               
        if ($imax2 !== false && $imax2 != "0") {
            $phase =  self::CPHASE_TRI;
        }
        else {
            $imax = $this->getTeleinfoData($gceid, 'IMAX', true);
            if ($imax !== false && $imax != "0") {
                $phase =  self::CPHASE_MONO;
            }
        }
        
        $this->addLog('debug', 'detection phase: ' . $phase);
        return $phase;
    }
    
    /**
     * Check the given $phase is within [self::CPHASE_MONO, self::CPHASE_TRI]
     * @param string $phase
     * @throws \Exception if $phase is not in the autorized range
     */
    public static function checkPhase($phase) {
        $expectedvalues = array(self::CPHASE_MONO, self::CPHASE_TRI);
        if (! in_array($phase, $expectedvalues)) {
            throw new \Exception(__('Type d\'abonnement' . ' (' . implode('|', $expectedvalues) . ') non attendu', __FILE__) . ' : ' . $phase);
        }
    }
    
    /**
     * Check the given compteur type is within [self::CTYP_EAU, self::CTYP_GAZ, self::CTYP_ELEC, self::CTYP_FUEL]
     * @param string $type
     * @throws \Exception if $type is not in the autorized range
     */
    public static function checkCompteurType($type) {
        $expectedvalues = array(self::CTYP_EAU, self::CTYP_GAZ, self::CTYP_ELEC, self::CTYP_FUEL);
        if (! in_array($type, $expectedvalues)) {
            throw new \Exception(__('Type de compteur (' . implode('|', $expectedvalues) . ') non attendu', __FILE__) . ' : ' . $type);
        }
    }
    
    private function throwNonResponsiveException() {
        throw new \Exception(__('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->name);
    }
}