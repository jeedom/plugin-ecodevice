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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'ecodeviceCmd', 'class', 'ecodevice');

class ecodevice extends eqLogic {

    const CONF_TYPE = 'type';
    const TYP_CARTE = 'carte';
    
    private $_xmlstatus;
    
    /**
     * Variable shared between preUpdate and postUpdate
     * @var string
     */
    private $_phase;
    
    private static $_teleinfo_default_cmds = array(
        "carte" =>
            array(
                'status' => array(
                    'type' => 'info', 'name' => 'Etat', 'subtype' => 'binary', 'unit' => '', 'isvisible' => 1, 'generic_type' => "GENERIC_INFO", 'template' => 'default'
                ),
                'reboot' => array(
                    'type' => 'action', 'name' => 'Reboot', 'subtype' => 'other', 'unit' => '', 'isvisible' => 0, 'generic_type' => "GENERIC_ACTION", 'template' => 'default'
                ),
            ),
        "teleinfo" =>
            array(
                "BASE" =>
                    array(
                        'type' => 'info', 'name' => 'Index (base)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 1, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '',
                        'tarif' => "BASE"
                    ),
                "HCHC" =>
                    array(
                        'type' => 'info', 'name' => 'Index (HC)', 'subtype' =>  'numeric', 'unit' => 'Wh', 'isvisible' => 1, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "HC"),
                "HCHP"              => array('type' => 'info', 'name' => 'Index (HP)', 'subtype' =>  'numeric', 'unit' => 'Wh', 'isvisible' => 1, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "HC"),
                "BBRHCJB"           => array('type' => 'info', 'name' => 'Index (HC jours bleus Tempo)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHPJB"           => array('type' => 'info', 'name' => 'Index (HP jours bleus Tempo)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHCJW"           => array('type' => 'info', 'name' => 'Index (HC jours blancs Tempo)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHPJW"           => array('type' => 'info', 'name' => 'Index (HP jours blancs Tempo)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHCJR"           => array('type' => 'info', 'name' => 'Index (HC jours rouges Tempo)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHPJR"           => array('type' => 'info', 'name' => 'Index (HP jours rouges Tempo)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "EJPHN"             => array('type' => 'info', 'name' => 'Index (normal EJP)', 'subtype' => 'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "EJP"),
                "EJPHPM"            => array('type' => 'info', 'name' => 'Index (pointe mobile EJP)', 'subtype' =>  'numeric', 'unit' => 'Wh', 'isvisible' => 0, 'generic_type' => "CONSUMPTION", 'template' => 'badge', 'phase' => '', 'tarif' => "EJP"),
                "IINST"             => array('type' => 'info', 'name' => 'Intensité instantanée', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 1, 'generic_type' => "POWER", 'template' => 'default', 'phase' => 'Mono', 'tarif' => ""),
                "IINST1"            => array('type' => 'info', 'name' => 'Intensité instantanée 1', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 0, 'generic_type' => "POWER", 'template' => 'default', 'phase' => 'Tri', 'tarif' => ""),
                "IINST2"            => array('type' => 'info', 'name' => 'Intensité instantanée 2', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 0, 'generic_type' => "POWER", 'template' => 'default', 'phase' => 'Tri', 'tarif' => ""),
                "IINST3"            => array('type' => 'info', 'name' => 'Intensité instantanée 3', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 0, 'generic_type' => "POWER", 'template' => 'default', 'phase' => 'Tri', 'tarif' => ""),
                "PPAP"              => array('type' => 'info', 'name' => 'Puissance Apparente', 'subtype' => 'numeric', 'unit' => 'VA', 'isvisible' => 1, 'generic_type' => "POWER", 'template' => 'badge', 'phase' => '', 'tarif' => ""),
                "OPTARIF"           => array('type' => 'info', 'name' => 'Option tarif', 'subtype' => 'string', 'unit' => '', 'isvisible' => 1, 'generic_type' => "GENERIC_INFO", 'template' => 'badge', 'phase' => '', 'tarif' => ""),
                "DEMAIN"            => array('type' => 'info', 'name' => 'Couleur demain', 'subtype' => 'string', 'unit' => '', 'isvisible' => 0, 'generic_type' => "GENERIC_INFO", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "PTEC"              => array('type' => 'info', 'name' => 'Tarif en cours', 'subtype' => 'string', 'unit' => '', 'isvisible' => 1, 'generic_type' => "GENERIC_INFO", 'template' => 'badge', 'phase' => '', 'tarif' => ""),
                "BASE_evolution"    => array('type' => 'info', 'name' => 'Evolution index (base)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 1, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BASE"),
                "HCHC_evolution"    => array('type' => 'info', 'name' => 'Evolution index (HC)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 1, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "HC"),
                "HCHP_evolution"    => array('type' => 'info', 'name' => 'Evolution index (HP)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 1, "'generic_type' => ", 'template' => 'badge', 'phase' => '', 'tarif' => "HC"),
                "BBRHCJB_evolution" => array('type' => 'info', 'name' => 'Evolution index (HC jours bleus Tempo)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHPJB_evolution" => array('type' => 'info', 'name' => 'Evolution index (HP jours bleus Tempo)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHCJW_evolution" => array('type' => 'info', 'name' => 'Evolution index (HC jours blancs Tempo)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHPJW_evolution" => array('type' => 'info', 'name' => 'Evolution index (HP jours blancs Tempo)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHCJR_evolution" => array('type' => 'info', 'name' => 'Evolution index (HC jours rouges Tempo)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "BBRHPJR_evolution" => array('type' => 'info', 'name' => 'Evolution index (HP jours rouges Tempo)', 'subtype' => 'numeric', 'unit' => 'W/min', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "BBRH"),
                "EJPHN_evolution"   => array('type' => 'info', 'name' => 'Evolution index (normal EJP)', 'subtype' => 'numeric', 'unit' => 'W', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "EJP"),
                "EJPHPM_evolution"  => array('type' => 'info', 'name' => 'Evolution index (pointe mobile EJP)', 'subtype' => 'numeric', 'unit' => 'W', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => "EJP"),
                "ISOUSC"            => array('type' => 'info', 'name' => 'Intensité souscrite', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 1, 'generic_type' => "", 'template' => 'badge', 'phase' => '', 'tarif' => ""),
                "IMAX"              => array('type' => 'info', 'name' => 'Intensité maximale', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 1, 'generic_type' => "", 'template' => 'badge', 'phase' => 'Mono', 'tarif' => ""),
                "IMAX1"             => array('type' => 'info', 'name' => 'Intensité maximale 1', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => 'Tri', 'tarif' => ""),
                "IMAX2"             => array('type' => 'info', 'name' => 'Intensité maximale 2', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => 'Tri', 'tarif' => ""),
                "IMAX3"             => array('type' => 'info', 'name' => 'Intensité maximale 3', 'subtype' => 'numeric', 'unit' => 'A', 'isvisible' => 0, 'generic_type' => "", 'template' => 'badge', 'phase' => 'Tri', 'tarif' => "")
    ));
    
    //private static $

    public static function pull() {
        log::add('ecodevice', 'debug', 'start cron');
        foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"carte"') as $eqLogic) {
            $eqLogic->scan();
        }
        log::add('ecodevice', 'debug', 'stop cron');
    }

    public function getUrl() {
        if ($this->getConfiguration('type', '') == 'carte') {
            $url = 'http://';
            if ($this->getConfiguration('username') != '') {
                $url .= $this->getConfiguration('username') . ':' . $this->getConfiguration('password') . '@';
            }
            $url .= $this->getConfiguration('ip');
            if ($this->getConfiguration('port') != '') {
                $url .= ':' . $this->getConfiguration('port');
            }
            return $url . "/";
        } else {
            $EcodeviceeqLogic = eqLogic::byId($this->getEcodeviceId());
            return $EcodeviceeqLogic->getUrl();
        }
    }

    public function preInsert() {
        switch ($this->getConfiguration('type', '')) {
            case "":
            case "carte":
                $this->setConfiguration('type', 'carte');
                break;
            case "teleinfo":
                $this->setIsEnable(0);
                break;
            case "compteur":
                $this->setIsEnable(0);
                break;
        }
    }
    
    public function preUpdate() {
        switch ($this->getConfiguration('type', '')) {
            case "carte":
                if ($this->getIsEnable()) {
                    log::add('ecodevice', 'debug', 'get ' . $this->getUrl() . 'status.xml');
                    $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                    if ($this->_xmlstatus === false) {
                        throw new \Exception(__('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName());
                    }
                }
                break;
                
            case "teleinfo":
                if ($this->getIsEnable()) {
                    $carteEqL = $this->getEcodevice();
                    $this->_phase = $carteEqL->getPhase($this->getGceId());
                    if ($this->_phase == "") {
                        throw new \Exception(__('Le type de compteur est introuvable. Vérifier la communication entre l\'ecodevice et votre compteur.', __FILE__));
                    }
                }
                break;
                
            case "compteur":
                if ($this->getIsEnable()) {
                    if ($this->getConfiguration('typecompteur') == "") {
                        throw new \Exception(__('Le type de compteur doit être défini.', __FILE__));
                    }
                }
                break;
        }
    }

    public function postInsert() {
        switch ($this->getConfiguration('type', '')) {
            case "carte":
                foreach (self::$_teleinfo_default_cmds['carte'] as $logicalId => $data) {
                    ecodeviceCmd::create($this, $logicalId, $data);
                }
                for ($compteurId = 0; $compteurId <= 1; $compteurId++) {
                    if (!is_object(self::byLogicalId($this->getId() . "_C" . $compteurId, 'ecodevice'))) {
                        log::add('ecodevice', 'debug', 'Creation compteur : ' . $this->getId() . '_C' . $compteurId);
                        $eqLogic = new ecodevice();
                        $eqLogic->setEqType_name('ecodevice');
                        $eqLogic->setConfiguration('type', 'compteur');
                        $eqLogic->setIsEnable(0);
                        $eqLogic->setName('Compteur ' . $compteurId);
                        $eqLogic->setLogicalId($this->getId() . '_C' . $compteurId);
                        $eqLogic->setIsVisible(0);
                        $eqLogic->save();
                    }
                }
                for ($compteurId = 1; $compteurId <= 2; $compteurId++) {
                    if (!is_object(self::byLogicalId($this->getId() . "_T" . $compteurId, 'ecodevice'))) {
                        log::add('ecodevice', 'debug', 'Creation teleinfo : ' . $this->getId() . '_T' . $compteurId);
                        $eqLogic = new ecodevice();
                        $eqLogic->setEqType_name('teleinfo');
                        $eqLogic->setConfiguration('type', 'teleinfo');
                        $eqLogic->setIsEnable(0);
                        $eqLogic->setName('Teleinfo ' . $compteurId);
                        $eqLogic->setLogicalId($this->getId() . '_T' . $compteurId);
                        $eqLogic->setIsVisible(0);
                        $eqLogic->setCategory("energy", "Energie");
                        $eqLogic->save();
                    }
                }
                break;
                
            case "teleinfo":
                break;
                
            case "compteur":
                $consommationjour = $this->getCmd(null, 'consommationjour');
                if (!is_object($consommationjour)) {
                    $consommationjour = new ecodeviceCmd();
                    $consommationjour->setName('Consommation journalière');
                    $consommationjour->setEqLogic_id($this->getId());
                    $consommationjour->setType('info');
                    $consommationjour->setSubType('numeric');
                    $consommationjour->setLogicalId('consommationjour');
                    $consommationjour->setEventOnly(1);
                    $consommationjour->setIsVisible(1);
                    $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
                    $consommationjour->setTemplate('dashboard', 'badge');
                    $consommationjour->setTemplate('mobile', 'badge');
                    $consommationjour->setUnite("l");
                    $consommationjour->save();
                }
                $consommationtotal = $this->getCmd(null, 'consommationtotal');
                if (!is_object($consommationtotal)) {
                    $consommationtotal = new ecodeviceCmd();
                    $consommationtotal->setName('Consommation total');
                    $consommationtotal->setEqLogic_id($this->getId());
                    $consommationtotal->setType('info');
                    $consommationtotal->setSubType('numeric');
                    $consommationtotal->setLogicalId('consommationtotal');
                    $consommationtotal->setEventOnly(1);
                    $consommationtotal->setIsVisible(1);
                    $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
                    $consommationtotal->setTemplate('dashboard', 'badge');
                    $consommationtotal->setTemplate('mobile', 'badge');
                    $consommationtotal->setUnite("l");
                    $consommationtotal->save();
                }
                $debitinstantane = $this->getCmd(null, 'debitinstantane');
                if (!is_object($debitinstantane)) {
                    $debitinstantane = new ecodeviceCmd();
                    $debitinstantane->setName('Debit instantané');
                    $debitinstantane->setEqLogic_id($this->getId());
                    $debitinstantane->setType('info');
                    $debitinstantane->setSubType('numeric');
                    $debitinstantane->setLogicalId('debitinstantane');
                    $debitinstantane->setEventOnly(1);
                    $debitinstantane->setIsVisible(1);
                    $debitinstantane->setDisplay('generic_type', 'GENERIC_INFO');
                    $debitinstantane->setUnite("l/min");
                    $debitinstantane->save();
                }
                $this->setConfiguration('typecompteur', "");
                break;
        }
    }
    
    public function postUpdate() {
        switch ($this->getConfiguration('type', '')) {
            case "carte":
                $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                $count           = 0;
                while ($this->_xmlstatus === false && $count < 3) {
                    $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                    $count++;
                }
                if ($this->_xmlstatus !== false) {
                    for ($compteurId = 0; $compteurId <= 1; $compteurId++) {
                        if (!is_object(self::byLogicalId($this->getId() . "_C" . $compteurId, 'ecodevice'))) {
                            log::add('ecodevice', 'debug', 'Creation compteur : ' . $this->getId() . '_C' . $compteurId);
                            $eqLogic = new ecodevice();
                            $eqLogic->setEqType_name('ecodevice');
                            $eqLogic->setConfiguration('type', 'compteur');
                            $eqLogic->setIsEnable(0);
                            $eqLogic->setName('Compteur ' . $compteurId);
                            $eqLogic->setLogicalId($this->getId() . '_C' . $compteurId);
                            $eqLogic->setIsVisible(0);
                            $eqLogic->save();
                        } else {
                            $eqLogic     = self::byLogicalId($this->getId() . "_C" . $compteurId, 'ecodevice');
                            # Verifie la configuration des compteurs fuel
                            $xpathModele = '//c' . $compteurId . '_fuel';
                            $status      = $this->_xmlstatus->xpath($xpathModele);

                            if (count($status) != 0) {
                                if ($status[0] != "selected") {
                                    if ($eqLogic->getConfiguration('typecompteur') == "Fuel" || $eqLogic->getConfiguration('typecompteur') == "Temps de fonctionnement") {
                                        throw new \Exception(__('Le compteur ' . $eqLogic->getName() . ' ne doit pas être configuré en mode fuel dans l\'ecodevice.', __FILE__));
                                    }
                                    elseif ($eqLogic->getConfiguration('typecompteur') == "") {
                                        $eqLogic->setConfiguration('typecompteur', "Eau");
                                        $eqLogic->save();
                                    }
                                }
                                else {
                                    $eqLogic->setConfiguration('typecompteur', "Fuel");
                                    $eqLogic->save();
                                }
                            }
                            elseif ($eqLogic->getConfiguration('typecompteur') == "Fuel" || $eqLogic->getConfiguration('typecompteur') == "Temps de fonctionnement") {
                                throw new \Exception(__('Le compteur ' . $eqLogic->getName() . ' ne doit pas être configuré en mode fuel dans l\'ecodevice.', __FILE__));
                            }
                            elseif ($eqLogic->getConfiguration('typecompteur') == "") {
                                $eqLogic->setConfiguration('typecompteur', "Eau");
                                $eqLogic->save();
                            }
                        }
                    }
                    for ($compteurId = 1; $compteurId <= 2; $compteurId++) {
                        if (!is_object(self::byLogicalId($this->getId() . "_T" . $compteurId, 'ecodevice'))) {
                            log::add('ecodevice', 'debug', 'Creation teleinfo : ' . $this->getId() . '_T' . $compteurId);
                            $eqLogic = new ecodevice();
                            $eqLogic->setEqType_name('ecodevice');
                            $eqLogic->setConfiguration('type', 'teleinfo');
                            $eqLogic->setIsEnable(0);
                            $eqLogic->setName('Teleinfo ' . $compteurId);
                            $eqLogic->setLogicalId($this->getId() . '_T' . $compteurId);
                            $eqLogic->setIsVisible(0);
                            $eqLogic->setCategory("energy", "Energie");
                            $eqLogic->save();
                        }
                    }
                }
                break;
                
            case "teleinfo":
                if ($this->getIsEnable()) {
                    foreach (self::$_teleinfo_default_cmds['teleinfo'] as $logicalId => $data) {
                        if (($this->getConfiguration('tarification') == "" || $this->getConfiguration('tarification') == $data['tarif'] || $data['tarif'] == "") &&
                            ($this->_phase == $data['phase'] || $data['phase'] == "")) {
                            $cmd = $this->getCmd(null, $logicalId);
                            if (!is_object($cmd)) {
                                ecodeviceCmd::create($this, $logicalId, $data);
                            }
                        }
                        else {
                            //FIXME: is that code usefull?
                            $cmd = $this->getCmd(null, $logicalId);
                            if (is_object($cmd)) {
                                $cmd->remove();
                            }
                        }
                    }
                }
                break;
                
            case "compteur":
                break;
        }
    }

    public function postAjax() {
        switch ($this->getConfiguration('type', '')) {
            case "carte":
                break;
                
            case "teleinfo":
                break;
                
            case "compteur":
                if ($this->getIsEnable()) {
                    foreach ($this->getCmd() as $cmd) {
                        if (!in_array($cmd->getLogicalId(), array("consommationinstantane", "consommationjour", "consommationtotal", "debitinstantane", "tempsfonctionnement", "tempsfonctionnementminute", "nbimpulsiontotal", "nbimpulsionminute", "nbimpulsionjour"))) {
                            $cmd->remove();
                        }
                    }
                    if ($this->getConfiguration('typecompteur') == "Autre") {
                        $tempsfonctionnement = $this->getCmd(null, 'tempsfonctionnement');
                        if (!is_object($tempsfonctionnement)) {
                            $tempsfonctionnement = new ecodeviceCmd();
                            $tempsfonctionnement->setName('Temps de fonctionnement');
                            $tempsfonctionnement->setEqLogic_id($this->getId());
                            $tempsfonctionnement->setType('info');
                            $tempsfonctionnement->setSubType('numeric');
                            $tempsfonctionnement->setLogicalId('tempsfonctionnement');
                            $tempsfonctionnement->setUnite("min");
                            $tempsfonctionnement->setEventOnly(1);
                            $tempsfonctionnement->setIsVisible(1);
                            $tempsfonctionnement->setDisplay('generic_type', 'GENERIC_INFO');
                            $tempsfonctionnement->save();
                        }
                        $tempsfonctionnementminute = $this->getCmd(null, 'tempsfonctionnementminute');
                        if (!is_object($tempsfonctionnementminute)) {
                            $tempsfonctionnementminute = new ecodeviceCmd();
                            $tempsfonctionnementminute->setName('Temps de fonctionnement par minute');
                            $tempsfonctionnementminute->setEqLogic_id($this->getId());
                            $tempsfonctionnementminute->setType('info');
                            $tempsfonctionnementminute->setSubType('numeric');
                            $tempsfonctionnementminute->setLogicalId('tempsfonctionnementminute');
                            $tempsfonctionnementminute->setUnite("min/min");
                            $tempsfonctionnementminute->setEventOnly(1);
                            $tempsfonctionnementminute->setIsVisible(1);
                            $tempsfonctionnementminute->setDisplay('generic_type', 'GENERIC_INFO');
                            $tempsfonctionnementminute->save();
                        }

                        $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
                        if (!is_object($nbimpulsiontotal)) {
                            $nbimpulsiontotal = new ecodeviceCmd();
                            $nbimpulsiontotal->setName('Nombre d impulsion total');
                            $nbimpulsiontotal->setEqLogic_id($this->getId());
                            $nbimpulsiontotal->setType('info');
                            $nbimpulsiontotal->setSubType('numeric');
                            $nbimpulsiontotal->setLogicalId('nbimpulsiontotal');
                            $nbimpulsiontotal->setEventOnly(1);
                            $nbimpulsiontotal->setIsVisible(1);
                            $nbimpulsiontotal->setDisplay('generic_type', 'GENERIC_INFO');
                            $nbimpulsiontotal->save();
                        }
                        $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
                        if (!is_object($nbimpulsionminute)) {
                            $nbimpulsionminute = new ecodeviceCmd();
                            $nbimpulsionminute->setName('Nombre d impulsion par minute');
                            $nbimpulsionminute->setEqLogic_id($this->getId());
                            $nbimpulsionminute->setType('info');
                            $nbimpulsionminute->setSubType('numeric');
                            $nbimpulsionminute->setLogicalId('nbimpulsionminute');
                            $nbimpulsionminute->setUnite("Imp/min");
                            $nbimpulsionminute->setEventOnly(1);
                            $nbimpulsionminute->setIsVisible(1);
                            $nbimpulsionminute->setDisplay('generic_type', 'GENERIC_INFO');
                            $nbimpulsionminute->save();
                        }
                        $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
                        if (!is_object($nbimpulsionjour)) {
                            $nbimpulsionjour = new ecodeviceCmd();
                            $nbimpulsionjour->setName('Nombre d impulsion jour');
                            $nbimpulsionjour->setEqLogic_id($this->getId());
                            $nbimpulsionjour->setType('info');
                            $nbimpulsionjour->setSubType('numeric');
                            $nbimpulsionjour->setLogicalId('nbimpulsionjour');
                            $nbimpulsionjour->setEventOnly(1);
                            $nbimpulsionjour->setIsVisible(1);
                            $nbimpulsionjour->setDisplay('generic_type', 'GENERIC_INFO');
                            $nbimpulsionjour->save();
                        }
                    }
                    elseif ($this->getConfiguration('typecompteur') == "Fuel") {
                        $tempsfonctionnement = $this->getCmd(null, 'tempsfonctionnement');
                        if (is_object($tempsfonctionnement)) {
                            $tempsfonctionnement->remove();
                        }
                        $tempsfonctionnementminute = $this->getCmd(null, 'tempsfonctionnementminute');
                        if (is_object($tempsfonctionnementminute)) {
                            $tempsfonctionnementminute->remove();
                        }
                        $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
                        if (is_object($nbimpulsiontotal)) {
                            $nbimpulsiontotal->remove();
                        }
                        $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
                        if (is_object($nbimpulsionminute)) {
                            $nbimpulsionminute->remove();
                        }
                        $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
                        if (is_object($nbimpulsionjour)) {
                            $nbimpulsionjour->remove();
                        }
                        $debitinstantane = $this->getCmd(null, 'debitinstantane');
                        if (is_object($debitinstantane)) {
                            $debitinstantane->remove();
                        }
                        $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
                        if (!is_object($consommationinstantane)) {
                            $consommationinstantane = new ecodeviceCmd();
                            $consommationinstantane->setName('Consommation instantané');
                            $consommationinstantane->setEqLogic_id($this->getId());
                            $consommationinstantane->setType('info');
                            $consommationinstantane->setSubType('numeric');
                            $consommationinstantane->setLogicalId('consommationinstantane');
                            $consommationinstantane->setUnite("ml/h");
                            $consommationinstantane->setEventOnly(1);
                            $consommationinstantane->setIsVisible(1);
                            $consommationinstantane->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationinstantane->save();
                        }
                        $consommationtotal = $this->getCmd(null, 'consommationtotal');
                        if (!is_object($consommationtotal)) {
                            $consommationtotal = new ecodeviceCmd();
                            $consommationtotal->setName('Consommation total');
                            $consommationtotal->setEqLogic_id($this->getId());
                            $consommationtotal->setType('info');
                            $consommationtotal->setSubType('numeric');
                            $consommationtotal->setLogicalId('consommationtotal');
                            $consommationtotal->setUnite("ml");
                            $consommationtotal->setEventOnly(1);
                            $consommationtotal->setIsVisible(1);
                            $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationtotal->setTemplate('dashboard', 'badge');
                            $consommationtotal->setTemplate('mobile', 'badge');
                            $consommationtotal->save();
                        }
                        $consommationjour = $this->getCmd(null, 'consommationjour');
                        if (!is_object($consommationjour)) {
                            $consommationjour = new ecodeviceCmd();
                            $consommationjour->setName('Consommation journalière');
                            $consommationjour->setEqLogic_id($this->getId());
                            $consommationjour->setType('info');
                            $consommationjour->setSubType('numeric');
                            $consommationjour->setLogicalId('consommationjour');
                            $consommationjour->setUnite("ml");
                            $consommationjour->setEventOnly(1);
                            $consommationjour->setIsVisible(1);
                            $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationjour->setTemplate('dashboard', 'badge');
                            $consommationjour->setTemplate('mobile', 'badge');
                            $consommationjour->save();
                        }
                    }
                    elseif ($this->getConfiguration('typecompteur') == "Eau") {
                        $tempsfonctionnement = $this->getCmd(null, 'tempsfonctionnement');
                        if (is_object($tempsfonctionnement)) {
                            $tempsfonctionnement->remove();
                        }
                        $tempsfonctionnementminute = $this->getCmd(null, 'tempsfonctionnementminute');
                        if (is_object($tempsfonctionnementminute)) {
                            $tempsfonctionnementminute->remove();
                        }
                        $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
                        if (is_object($nbimpulsiontotal)) {
                            $nbimpulsiontotal->remove();
                        }
                        $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
                        if (is_object($nbimpulsionminute)) {
                            $nbimpulsionminute->remove();
                        }
                        $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
                        if (is_object($nbimpulsionjour)) {
                            $nbimpulsionjour->remove();
                        }
                        $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
                        if (is_object($consommationinstantane)) {
                            $consommationinstantane->remove();
                        }
                        $consommationjour = $this->getCmd(null, 'consommationjour');
                        if (!is_object($consommationjour)) {
                            $consommationjour = new ecodeviceCmd();
                            $consommationjour->setName('Consommation journalière');
                            $consommationjour->setEqLogic_id($this->getId());
                            $consommationjour->setType('info');
                            $consommationjour->setSubType('numeric');
                            $consommationjour->setLogicalId('consommationjour');
                            $consommationjour->setEventOnly(1);
                            $consommationjour->setIsVisible(1);
                            $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationjour->setTemplate('dashboard', 'badge');
                            $consommationjour->setTemplate('mobile', 'badge');
                            $consommationjour->setUnite("l");
                            $consommationjour->save();
                        }
                        $consommationtotal = $this->getCmd(null, 'consommationtotal');
                        if (!is_object($consommationtotal)) {
                            $consommationtotal = new ecodeviceCmd();
                            $consommationtotal->setName('Consommation total');
                            $consommationtotal->setEqLogic_id($this->getId());
                            $consommationtotal->setType('info');
                            $consommationtotal->setSubType('numeric');
                            $consommationtotal->setLogicalId('consommationtotal');
                            $consommationtotal->setEventOnly(1);
                            $consommationtotal->setIsVisible(1);
                            $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationtotal->setTemplate('dashboard', 'badge');
                            $consommationtotal->setTemplate('mobile', 'badge');
                            $consommationtotal->setUnite("l");
                            $consommationtotal->save();
                        }
                        $debitinstantane = $this->getCmd(null, 'debitinstantane');
                        if (!is_object($debitinstantane)) {
                            $debitinstantane = new ecodeviceCmd();
                            $debitinstantane->setName('Debit instantané');
                            $debitinstantane->setEqLogic_id($this->getId());
                            $debitinstantane->setType('info');
                            $debitinstantane->setSubType('numeric');
                            $debitinstantane->setLogicalId('debitinstantane');
                            $debitinstantane->setEventOnly(1);
                            $debitinstantane->setIsVisible(1);
                            $debitinstantane->setDisplay('generic_type', 'GENERIC_INFO');
                            $debitinstantane->setUnite("l/min");
                            $debitinstantane->save();
                        }
                    }
                    elseif ($this->getConfiguration('typecompteur') == "Gaz") {
                        $tempsfonctionnement = $this->getCmd(null, 'tempsfonctionnement');
                        if (is_object($tempsfonctionnement)) {
                            $tempsfonctionnement->remove();
                        }
                        $tempsfonctionnementminute = $this->getCmd(null, 'tempsfonctionnementminute');
                        if (is_object($tempsfonctionnementminute)) {
                            $tempsfonctionnementminute->remove();
                        }
                        $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
                        if (is_object($nbimpulsiontotal)) {
                            $nbimpulsiontotal->remove();
                        }
                        $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
                        if (is_object($nbimpulsionminute)) {
                            $nbimpulsionminute->remove();
                        }
                        $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
                        if (is_object($nbimpulsionjour)) {
                            $nbimpulsionjour->remove();
                        }

                        $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
                        if (is_object($consommationinstantane)) {
                            $consommationinstantane->remove();
                        }
                        $consommationjour = $this->getCmd(null, 'consommationjour');
                        if (!is_object($consommationjour)) {
                            $consommationjour = new ecodeviceCmd();
                            $consommationjour->setName('Consommation journalière');
                            $consommationjour->setEqLogic_id($this->getId());
                            $consommationjour->setType('info');
                            $consommationjour->setSubType('numeric');
                            $consommationjour->setLogicalId('consommationjour');
                            $consommationjour->setEventOnly(1);
                            $consommationjour->setIsVisible(1);
                            $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationjour->setTemplate('dashboard', 'badge');
                            $consommationjour->setTemplate('mobile', 'badge');
                            $consommationjour->setUnite("dm³");
                            $consommationjour->save();
                        }
                        $consommationtotal = $this->getCmd(null, 'consommationtotal');
                        if (!is_object($consommationtotal)) {
                            $consommationtotal = new ecodeviceCmd();
                            $consommationtotal->setName('Consommation total');
                            $consommationtotal->setEqLogic_id($this->getId());
                            $consommationtotal->setType('info');
                            $consommationtotal->setSubType('numeric');
                            $consommationtotal->setLogicalId('consommationtotal');
                            $consommationtotal->setEventOnly(1);
                            $consommationtotal->setIsVisible(1);
                            $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationtotal->setTemplate('dashboard', 'badge');
                            $consommationtotal->setTemplate('mobile', 'badge');
                            $consommationtotal->setUnite("dm³");
                            $consommationtotal->save();
                        }
                        $debitinstantane = $this->getCmd(null, 'debitinstantane');
                        if (!is_object($debitinstantane)) {
                            $debitinstantane = new ecodeviceCmd();
                            $debitinstantane->setName('Debit instantané');
                            $debitinstantane->setEqLogic_id($this->getId());
                            $debitinstantane->setType('info');
                            $debitinstantane->setSubType('numeric');
                            $debitinstantane->setLogicalId('debitinstantane');
                            $debitinstantane->setEventOnly(1);
                            $debitinstantane->setIsVisible(1);
                            $debitinstantane->setDisplay('generic_type', 'GENERIC_INFO');
                            $debitinstantane->setUnite("dm³/min");
                            $debitinstantane->save();
                        }
                    }
                    elseif ($this->getConfiguration('typecompteur') == "Electricité") {
                        $tempsfonctionnement = $this->getCmd(null, 'tempsfonctionnement');
                        if (is_object($tempsfonctionnement)) {
                            $tempsfonctionnement->remove();
                        }
                        $tempsfonctionnementminute = $this->getCmd(null, 'tempsfonctionnementminute');
                        if (is_object($tempsfonctionnementminute)) {
                            $tempsfonctionnementminute->remove();
                        }
                        $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
                        if (is_object($nbimpulsiontotal)) {
                            $nbimpulsiontotal->remove();
                        }
                        $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
                        if (is_object($nbimpulsionminute)) {
                            $nbimpulsionminute->remove();
                        }
                        $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
                        if (is_object($nbimpulsionjour)) {
                            $nbimpulsionjour->remove();
                        }

                        $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
                        if (is_object($consommationinstantane)) {
                            $consommationinstantane->remove();
                        }
                        $consommationjour = $this->getCmd(null, 'consommationjour');
                        if (!is_object($consommationjour)) {
                            $consommationjour = new ecodeviceCmd();
                            $consommationjour->setName('Consommation journalière');
                            $consommationjour->setEqLogic_id($this->getId());
                            $consommationjour->setType('info');
                            $consommationjour->setSubType('numeric');
                            $consommationjour->setLogicalId('consommationjour');
                            $consommationjour->setEventOnly(1);
                            $consommationjour->setIsVisible(1);
                            $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationjour->setTemplate('dashboard', 'badge');
                            $consommationjour->setTemplate('mobile', 'badge');
                            $consommationjour->setUnite("Wh");
                            $consommationjour->save();
                        }
                        $consommationtotal = $this->getCmd(null, 'consommationtotal');
                        if (!is_object($consommationtotal)) {
                            $consommationtotal = new ecodeviceCmd();
                            $consommationtotal->setName('Consommation total');
                            $consommationtotal->setEqLogic_id($this->getId());
                            $consommationtotal->setType('info');
                            $consommationtotal->setSubType('numeric');
                            $consommationtotal->setLogicalId('consommationtotal');
                            $consommationtotal->setEventOnly(1);
                            $consommationtotal->setIsVisible(1);
                            $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
                            $consommationtotal->setTemplate('dashboard', 'badge');
                            $consommationtotal->setTemplate('mobile', 'badge');
                            $consommationtotal->setUnite("Wh");
                            $consommationtotal->save();
                        }
                        $debitinstantane = $this->getCmd(null, 'debitinstantane');
                        if (!is_object($debitinstantane)) {
                            $debitinstantane = new ecodeviceCmd();
                            $debitinstantane->setName('Consommation instantanée');
                            $debitinstantane->setEqLogic_id($this->getId());
                            $debitinstantane->setType('info');
                            $debitinstantane->setSubType('numeric');
                            $debitinstantane->setLogicalId('debitinstantane');
                            $debitinstantane->setEventOnly(1);
                            $debitinstantane->setIsVisible(1);
                            $debitinstantane->setDisplay('generic_type', 'GENERIC_INFO');
                            $debitinstantane->setUnite("Wh");
                            $debitinstantane->save();
                        }
                    }
                }
                break;
        }
    }

    public function preRemove() {
        log::add('ecodevice', 'info', 'Suppression ecodevice ' . $this->getConfiguration('type', '') . ' ' . $this->getName());
        if ($this->getConfiguration('type', '') == 'carte') {
            /** @var ecodevice $eqLogic */
            foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"compteur"') as $eqLogic) {
                if ($eqLogic->getEcodeviceId() == $this->getId()) {
                    $eqLogic->remove();
                }
            }
            foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"teleinfo"') as $eqLogic) {
                if ($eqLogic->getEcodeviceId() == $this->getId()) {
                    $eqLogic->remove();
                }
            }
        }
    }

    public function configPush($url_serveur = null) {
        switch ($this->getConfiguration('type', '')) {
            case "carte":
                if (config::byKey('internalAddr') == "") {
                    throw new \Exception(__('L\'adresse IP du serveur Jeedom doit être renseignée.<br>Général -> Administration -> Configuration.<br>Configuration réseaux -> Adresse interne', __FILE__));
                }
                if ($this->getIsEnable()) {
                    throw new \Exception('Configurer l\'URL suivante pour un rafraichissement plus rapide dans l\'ecodevice : page index=>notification :<br>http://' . config::byKey('internalAddr') . '/jeedom/core/api/jeeApi.php?api=' . jeedom::getApiKey('ecodevice') . '&type=ecodevice&id=' . $this->getEcodeviceId() . '&message=data_change<br>Attention surcharge possible importante.');
                    $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                    $count           = 0;
                    while ($this->_xmlstatus === false && $count < 3) {
                        $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                        $count++;
                    }
                    if ($this->_xmlstatus === false) {
                        log::add('ecodevice', 'error', __('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName() . " get " . preg_replace("/:[^:]*@/", ":XXXX@", $this->getUrl()) . 'status.xml');
                        return false;
                    }
                    /** @var ecodevice $eqLogic */
                    foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"compteur"') as $eqLogic) {
                        if ($eqLogic->getIsEnable() && $eqLogic->getEcodeviceId() == $this->getId()) {
                            $eqLogic->configPush($this->getUrl());
                        }
                    }
                    foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"teleinfo"') as $eqLogic) {
                        if ($eqLogic->getIsEnable() && $eqLogic->getEcodeviceId() == $this->getId()) {
                            $eqLogic->configPush($this->getUrl());
                        }
                    }
                }
                break;
            case "teleinfo":
                $gceid       = $this->getGceId();
                $url_serveur .= 'protect/settings/notif' . $gceid . 'P.htm';
                for ($compteur = 0; $compteur < 6; $compteur++) {
                    log::add('ecodevice', 'debug', 'Url ' . $url_serveur);
                    $data = array('num'  => $compteur + ($gceid - 1) * 6,
                        'act'  => $compteur + 3,
                        'serv' => config::byKey('internalAddr'),
                        'port' => 80,
                        'url'  => '/jeedom/core/api/jeeApi.php?api=' . jeedom::getApiKey('ecodevice') . '&type=ecodevice&plugin=ecodevice&id=' . $this->getEcodeviceId() . '&message=data_change');
                    //					'url' => '/jeedom/core/api/jeeApi.php?api='.jeedom::getApiKey('ecodevice').'&type=ecodevice&id='.$this->getId().'&message=data_change');

                    $options = array(
                        'http' => array(
                            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method'  => 'POST',
                            'content' => http_build_query($data),
                        ),
                    );
                    $context = stream_context_create($options);
                    $result  = @file_get_contents($url_serveur, false, $context);
                }
                break;
            case "compteur":
                break;
        }
    }

    public function event() {
        switch ($this->getConfiguration('type', '')) {
            case "carte":
                foreach (eqLogic::byType('ecodevice') as $eqLogic) {
                    if ($eqLogic->getId() == init('id')) {
                        $eqLogic->scan();
                    }
                }
                break;
            case "teleinfo":
                $cmd = ecodeviceCmd::byId(init('id'));
                if (!is_object($cmd)) {
                    throw new \Exception('Commande ID virtuel inconnu : ' . init('id'));
                }
                $cmd->event(init('value'));
                break;
            case "compteur":
                $cmd = ecodeviceCmd::byId(init('id'));
                if (!is_object($cmd)) {
                    throw new \Exception('Commande ID virtuel inconnu : ' . init('id'));
                }
                $cmd->event(init('value'));
                break;
        }
    }

    public function scan() {
        if ($this->getIsEnable()) {
            log::add('ecodevice', 'debug', "Scan " . $this->getName());
            $statuscmd       = $this->getCmd(null, 'status');
            $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
            $count           = 0;
            while ($this->_xmlstatus === false && $count < 3) {
                $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                $count++;
            }
            if ($this->_xmlstatus === false) {
                if ($statuscmd->execCmd() != 0) {
                    $statuscmd->event(0);
                }
                log::add('ecodevice', 'error', __('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName() . " get " . preg_replace("/:[^:]*@/", ":XXXX@", $this->getUrl()) . 'status.xml');
                return false;
            }
            /** @var ecodevice $eqLogic */
            foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"compteur"') as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getEcodeviceId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    if ($eqLogic->getConfiguration('typecompteur') == "Temps de fonctionnement") {
                        # Verifie la configuration des compteurs de temps de fonctionnement
                        $xpathModele = '//c' . $gceid . '_fuel';
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            if ($status[0] != "selected") {
                                throw new \Exception(__('Le compteur ' . $eqLogic->getName() . ' doit être configuré en mode fuel dans l\'ecodevice.', __FILE__));
                            }
                        } else {
                            throw new \Exception(__('Le compteur ' . $eqLogic->getName() . ' doit être configuré en mode fuel dans l\'ecodevice.', __FILE__));
                        }
                        $xpathModele = '//count' . $gceid;
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $nbimpulsiontotal_cmd  = $eqLogic->getCmd(null, 'nbimpulsiontotal');
                            $nbimpulsiontotal      = $nbimpulsiontotal_cmd->execCmd();
                            $nbimpulsionminute_cmd = $eqLogic->getCmd(null, 'nbimpulsionminute');
                            if ($nbimpulsiontotal != $status[0]) {
                                log::add('ecodevice', 'debug', "Change nbimpulsiontotal of " . $eqLogic->getName());
                                $lastCollectDate = $nbimpulsiontotal_cmd->getCollectDate();
                                if ($lastCollectDate == '') {
                                    log::add('ecodevice', 'debug', "Change nbimpulsionminute 0");
                                    $nbimpulsionminute = 0;
                                }
                                else {
                                    $DeltaSeconde = (time() - strtotime($lastCollectDate)) * 60;
                                    if ($DeltaSeconde != 0) {
                                        if ($status[0] > $nbimpulsiontotal) {
                                            $DeltaValeur = $status[0] - $nbimpulsiontotal;
                                        }
                                        else {
                                            $DeltaValeur = $status[0];
                                        }
                                        $nbimpulsionminute = round(($status[0] - $nbimpulsiontotal) / (time() - strtotime($lastCollectDate)) * 60, 6);
                                    }
                                    else {
                                        $nbimpulsionminute = 0;
                                    }
                                }
                                log::add('ecodevice', 'debug', "Change nbimpulsionminute " . $nbimpulsionminute);
                                $nbimpulsionminute_cmd->event($nbimpulsionminute);
                            }
                            else {
                                $nbimpulsionminute_cmd->event(0);
                            }
                            $nbimpulsiontotal_cmd->event((string) $status[0]);
                        }
                        $xpathModele         = '//c' . $gceid . 'day';
                        $status              = $this->_xmlstatus->xpath($xpathModele);
                        log::add('ecodevice', 'debug', 'duree fonctionnement ' . $status[0]);
                        $eqLogic_cmd         = $eqLogic->getCmd(null, 'tempsfonctionnement');
                        $tempsfonctionnement = $eqLogic_cmd->execCmd();
                        $eqLogic_cmd_evol    = $eqLogic->getCmd(null, 'tempsfonctionnementminute');
                        if ($tempsfonctionnement != $status[0] * 3.6) {
                            if ($eqLogic_cmd->getCollectDate() == '') {
                                $tempsfonctionnementminute = 0;
                            }
                            else {
                                if ($status[0] * 3.6 > $tempsfonctionnement) {
                                    $tempsfonctionnementminute = round(($status[0] * 3.6 - $tempsfonctionnement) / (time() - strtotime($eqLogic_cmd->getCollectDate())) * 60, 6);
                                }
                                else {
                                    $tempsfonctionnementminute = round($status[0] * 3.6 / (time() - strtotime($eqLogic_cmd_evol->getCollectDate()) * 60), 6);
                                }
                            }
                            $eqLogic_cmd_evol->event($tempsfonctionnementminute);
                        }
                        else {
                            $eqLogic_cmd_evol->event(0);
                        }
                        $eqLogic_cmd->event(intval($status[0]) * 3.6);
                    }
                    elseif ($eqLogic->getConfiguration('typecompteur') == "Fuel") {
                        # Verifie la configuration des compteurs fuel
                        $xpathModele = '//c' . $gceid . '_fuel';
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            if ($status[0] != "selected") {
                                throw new \Exception(__('Le compteur ' . $eqLogic->getName() . ' doit être configuré en mode fuel dans l\'ecodevice.', __FILE__));
                            }
                        }
                        else {
                            throw new \Exception(__('Le compteur ' . $eqLogic->getName() . ' doit être configuré en mode fuel dans l\'ecodevice.', __FILE__));
                        }
                        $xpathModele = '//count' . $gceid;
                        $status      = $this->_xmlstatus->xpath($xpathModele);
                        if (count($status) != 0) {
                            $consommationtotal     = intval($status[0]);
                            $consommationtotal_cmd = $eqLogic->getCmd(null, 'consommationtotal');
                            log::add('ecodevice', 'debug', "Change consommationtotal of " . $eqLogic->getName());
                            $consommationtotal_cmd->event($consommationtotal);
                        }
                        $xpathModele = '//c' . $gceid . "day";
                        $status      = $this->_xmlstatus->xpath($xpathModele);
                        if (count($status) != 0) {
                            $consommationjour     = intval($status[0]);
                            $consommationjour_cmd = $eqLogic->getCmd(null, 'consommationjour');
                            log::add('ecodevice', 'debug', "Change consommationjour of " . $eqLogic->getName());
                            $consommationjour_cmd->event($consommationjour);
                        }
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);
                        if (count($status) != 0) {
                            $consommationinstantane     = intval($status[0]) * 10;
                            $consommationinstantane_cmd = $eqLogic->getCmd(null, 'consommationinstantane');
                            log::add('ecodevice', 'debug', "Change consommationinstantane of " . $eqLogic->getName());
                            $consommationinstantane_cmd->event($consommationinstantane);
                        }
                    }
                    else {
                        # mode eau, gaz, electricité
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $eqLogic_cmd = $eqLogic->getCmd(null, 'debitinstantane');
                            log::add('ecodevice', 'debug', "Change debitinstantane of " . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                        $xpathModele = '//c' . $gceid . 'day';
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $eqLogic_cmd = $eqLogic->getCmd(null, 'consommationjour');
                            log::add('ecodevice', 'debug', "Change consommationjour of " . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                        $xpathModele = '//count' . $gceid;
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $consommationtotal_cmd = $eqLogic->getCmd(null, 'consommationtotal');
                            log::add('ecodevice', 'debug', "Change consommationtotal of " . $eqLogic->getName());
                            $consommationtotal_cmd->event((string) $status[0]);
                        }
                    }
                }
            }
            
            /** @var ecodevice $eqLogic */
            foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"teleinfo"') as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getEcodeviceId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'protect/settings/teleinfo' . $gceid . '.xml');
                    if ($this->_xmlstatus === false) {
                        if ($statuscmd->execCmd() != 0) {
                            $statuscmd->event(0);
                        }
                        log::add('ecodevice', 'error', __('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName() . " get " . preg_replace("/:[^:]*@/", ":XXXX@", $this->getUrl()) . 'protect/settings/teleinfo' . $gceid . '.xml');
                        return false;
                    }
                    $xpathModele = '//response';
                    $status      = $this->_xmlstatus->xpath($xpathModele);

                    if (count($status) != 0) {
                        foreach ($status[0] as $item => $data) {
                            if (substr($item, 0, 3) == "T" . $gceid . "_") {
                                $eqLogic_cmd = $eqLogic->getCmd(null, substr($item, 3));
                                if (is_object($eqLogic_cmd)) {
                                    $eqLogic_cmd_evol = $eqLogic->getCmd(null, substr($item, 3) . "_evolution");
                                    if (is_object($eqLogic_cmd_evol)) {
                                        $ancien_data = $eqLogic_cmd->execCmd();
                                        if ($ancien_data != $data) {
                                            log::add('ecodevice', 'debug', $eqLogic_cmd->getName() . ' Change ' . $data);
                                            if ($eqLogic_cmd->getCollectDate() == '') {
                                                $nbimpulsionminute = 0;
                                            }
                                            else {
                                                if ($data > $ancien_data) {
                                                    $nbimpulsionminute = round(($data - $ancien_data) / (time() - strtotime($eqLogic_cmd->getCollectDate())) * 60);
                                                }
                                                else {
                                                    $nbimpulsionminute = round($data / (time() - strtotime($eqLogic_cmd_evol->getCollectDate()) * 60));
                                                }
                                            }
                                            $eqLogic_cmd_evol->event($nbimpulsionminute);
                                        }
                                        else {
                                            $eqLogic_cmd_evol->event(0);
                                        }
                                        $eqLogic_cmd->event((string) $data);
                                    }
                                    else {
                                        $eqLogic_cmd->event((string) $data);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($statuscmd->execCmd() != 1) {
                $statuscmd->event(1);
            }
        }
    }

    public function scan_rapide() {
        if ($this->getIsEnable()) {
            log::add('ecodevice', 'debug', "Scan rapide " . $this->getName());
            $statuscmd       = $this->getCmd(null, 'status');
            $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
            $count           = 0;
            while ($this->_xmlstatus === false && $count < 3) {
                $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                $count++;
            }
            if ($this->_xmlstatus === false) {
                if ($statuscmd->execCmd() != 0) {
                    $statuscmd->event(0);
                }
                log::add('ecodevice', 'error', __('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName() . " get " . preg_replace("/:[^:]*@/", ":XXXX@", $this->getUrl()) . 'status.xml');
                return false;
            }
            
            /**
             * Scan and update non teleinfo counters
             * @var ecodevice $eqLogic
             */
            foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"compteur"') as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getEcodeviceId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    if ($eqLogic->getConfiguration('typecompteur') == "Fuel") {
                        # mode fuel
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $consommationinstantane = $status[0] / 100;
                            $eqLogic_cmd            = $eqLogic->getCmd(null, 'consommationinstantane');
                            log::add('ecodevice', 'debug', "Change consommationinstantane of " . $eqLogic->getName());
                            $eqLogic_cmd->event($consommationinstantane);
                        }
                    }
                    else {
                        # mode eau
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $eqLogic_cmd = $eqLogic->getCmd(null, 'debitinstantane');
                            log::add('ecodevice', 'debug', "Change debitinstantane of " . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                    }
                }
            }
            
            /**
             * Scan and update teleinfo counters
             * @var ecodevice $eqLogic
             */
            foreach (eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"teleinfo"') as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getEcodeviceId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    $item        = "T" . $gceid . "_PPAP";
                    $xpathModele = '//' . $item;
                    $status      = $this->_xmlstatus->xpath($xpathModele);

                    if (count($status) != 0) {
                        $eqLogic_cmd = $eqLogic->getCmd(null, substr($item, 3));
                        if (is_object($eqLogic_cmd)) {
                            log::add('ecodevice', 'debug', "Change " . $item . " of " . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                    }
                }
            }
        }
    }

    public static function daemon() {
        $starttime = microtime(true);
        foreach (self::byType('ecodevice') as $eqLogic) {
            $eqLogic->scan_rapide();
        }
        $endtime = microtime(true);
        if ($endtime - $starttime < config::byKey('temporisation_lecture', 'ecodevice', 60, true)) {
            usleep(floor((config::byKey('temporisation_lecture', 'ecodevice') + $starttime - $endtime) * 1000000));
        }
    }

    public static function deamon_info() {
        $return          = array();
        $return['log']   = '';
        $return['state'] = 'nok';
        $cron            = cron::byClassAndFunction('ecodevice', 'daemon');
        if (is_object($cron) && $cron->running()) {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start($_debug = false) {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new \Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        $cron = cron::byClassAndFunction('ecodevice', 'daemon');
        if (!is_object($cron)) {
            throw new \Exception(__('Tâche cron introuvable', __FILE__));
        }
        log::add('ecodevice', 'debug', 'daemon start');
        $cron->run();
    }

    public static function deamon_stop() {
        $cron = cron::byClassAndFunction('ecodevice', 'daemon');
        if (!is_object($cron)) {
            throw new \Exception(__('Tâche cron introuvable', __FILE__));
        }
        log::add('ecodevice', 'debug', 'daemon stop');
        $cron->halt();
    }

    public static function deamon_changeAutoMode($_mode) {
        $cron = cron::byClassAndFunction('ecodevice', 'daemon');
        if (!is_object($cron)) {
            throw new \Exception(__('Tâche cron introuvable', __FILE__));
        }
        $cron->setEnable($_mode);
        $cron->save();
    }

    public function getImage() {
        $f = '/resources/' . $this->getConfiguration('type', '') . '.svg';
        if (file_exists(dirname(__FILE__) . '/../..' . $f)) {
            return 'plugins/' . $this->getEqType_name() . $f;
        }
        return parent::getImage();
    }
    
    ###################################################################################################################
    ##
    ##                   PLUGIN SPECIFIC METHODS
    ##
    ###################################################################################################################
        
    /**
     * Return the eqLogic id of the ecodevice eqLogic related to this meter, or 0 if this eqLogic is itself
     * an ecodevice card
     * @return int
     */
    public function getEcodeviceId() {
        $id = empty($this->getLogicalId()) ? 0 : substr($this->getLogicalId(), 0, strpos($this->getLogicalId(),"_"));
        return $id;
    }
    
    /**
     * Return the eqLogic of the ecodevice related to this meter
     * @throw exception if not found
     * @return ecodevice
     */
    private function getEcodevice() {
        $eql = empty($this->getLogicalId()) ? $this : eqLogic::byId($this->getEcodeviceId());
        if (! is_object($eql))
            throw new \Exception(__('L\'ecodevice associé au compteur', __FILE__) . " " . $this->getName() . __('n\'existe pas', __FILE__));
        return $eql;
    }
    
    /**
     * @param string $gceid Id of this teleinfo in ecodevice provided data
     * @throws \Exception if no data can be retrieved from the ecodevice
     * @return string either Mono or Tri
     */
    private function getPhase($gceid) {
        $phase = "";
        if ($this->getIsEnable()) {            
            log::add('ecodevice', 'debug', 'get ' . $this->getUrl() . 'protect/settings/teleinfo' . $gceid . '.xml');
            $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'protect/settings/teleinfo' . $gceid . '.xml');
            if ($this->_xmlstatus === false) {
                throw new \Exception(__('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName());
            }
            $xpathModele     = '//T' . $gceid . '_IMAX2';
            $status          = $this->_xmlstatus->xpath($xpathModele);
            
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
        }       
        
        log::add('ecodevice', 'debug', 'Detection phase: ' . (empty($phase) ? 'ecodevice désactivé' : $phase));
        return $phase;
    }
    
    /**
     * Return the id of this teleinfo meter in the data provide by the ecodevice
     * @return string
     */
    private function getGceId() {
        return substr($this->getLogicalId(), strpos($this->getLogicalId(), "_") + 2, 1);
    }

    public static function getTypeCompteur() {
        return array(__('Eau', __FILE__),
            __('Fuel', __FILE__),
            __('Gaz', __FILE__),
            __('Electricité', __FILE__),
            __('Autre', __FILE__));
    }
}

