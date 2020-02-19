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
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once 'ecodeviceCmd.class.php';
require_once 'ecodeviceIf.class.php';
require_once 'ecodeviceLog.trait.php';


class ecodevice extends eqLogic {

    const TYP_CARTE = 'carte';
    const TYP_TELEINFO = 'teleinfo';
    const TYP_COMPTEUR = 'compteur';
    
    const METER_IDS = array('T1', 'T2', 'C0', 'C1');
    
    const POST_ACTION_SET_CMDS = 1; 
            
    private $_xmlstatus;
    
    private $_ecodeviceIf;
    
    /**
     * Carte ecodevice eqlogic object related to this ecodevice object.
     * Do not use directly, call getCarteEqlogic to retrieve it.
     * @var ecodevice
     */
    private $_carte;
    
    /**
     * Data shared between preSave and postSave
     * @var self::POST_ACTION_UPDATE_CMDS
     */
    private $_post_action;
    
    /**
     * Variable shared between preUpdate and postUpdate
     * @var string
     */
    private $_phase;

    private static $_teleinfo_default_cmds = array(
        self::TYP_CARTE => array(
            'status' => array('type' => 'info','name' => 'Etat','subtype' => 'binary','unit' => '','isvisible' => 1,
                'generic_type' => 'GENERIC_INFO','template' => 'default'),
            'reboot' => array('type' => 'action','name' => 'Reboot','subtype' => 'other','unit' => '','isvisible' => 0,
                'generic_type' => 'GENERIC_ACTION','template' => 'default')
        ),
        self::TYP_TELEINFO => array(
            'BASE' => array('type' => 'info','name' => 'Index (base)','subtype' => 'numeric','unit' => 'Wh',
                'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '','tarif' => 'BASE'),
            'HCHC' => array('type' => 'info','name' => 'Index (HC)','subtype' => 'numeric','unit' => 'Wh',
                'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '','tarif' => 'HC'),
            'HCHP' => array('type' => 'info','name' => 'Index (HP)','subtype' => 'numeric','unit' => 'Wh',
                'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '','tarif' => 'HC'),
            'BBRHCJB' => array('type' => 'info','name' => 'Index (HC jours bleus Tempo)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'BBRH'),
            'BBRHPJB' => array('type' => 'info','name' => 'Index (HP jours bleus Tempo)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'BBRH'),
            'BBRHCJW' => array('type' => 'info','name' => 'Index (HC jours blancs Tempo)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'BBRH'),
            'BBRHPJW' => array('type' => 'info','name' => 'Index (HP jours blancs Tempo)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'BBRH'),
            'BBRHCJR' => array('type' => 'info','name' => 'Index (HC jours rouges Tempo)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'BBRH'),
            'BBRHPJR' => array('type' => 'info','name' => 'Index (HP jours rouges Tempo)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'BBRH'),
            'EJPHN' => array('type' => 'info','name' => 'Index (normal EJP)','subtype' => 'numeric','unit' => 'Wh',
                'isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '','tarif' => 'EJP'),
            'EJPHPM' => array('type' => 'info','name' => 'Index (pointe mobile EJP)','subtype' => 'numeric',
                'unit' => 'Wh','isvisible' => 0,'generic_type' => 'CONSUMPTION','template' => 'badge','phase' => '',
                'tarif' => 'EJP'),
            'IINST' => array('type' => 'info','name' => 'Intensité instantanée','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 1,'generic_type' => 'POWER','template' => 'default','phase' => ecodeviceIf::CPHASE_MONO,'tarif' => ''),
            'IINST1' => array('type' => 'info','name' => 'Intensité instantanée 1','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 0,'generic_type' => 'POWER','template' => 'default','phase' => ecodeviceIf::CPHASE_TRI,'tarif' => ''),
            'IINST2' => array('type' => 'info','name' => 'Intensité instantanée 2','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 0,'generic_type' => 'POWER','template' => 'default','phase' => ecodeviceIf::CPHASE_TRI,'tarif' => ''),
            'IINST3' => array('type' => 'info','name' => 'Intensité instantanée 3','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 0,'generic_type' => 'POWER','template' => 'default','phase' => ecodeviceIf::CPHASE_TRI,'tarif' => ''),
            'PPAP' => array('type' => 'info','name' => 'Puissance Apparente','subtype' => 'numeric','unit' => 'VA',
                'isvisible' => 1,'generic_type' => 'POWER','template' => 'badge','phase' => '','tarif' => ''),
            'OPTARIF' => array('type' => 'info','name' => 'Option tarif','subtype' => 'string','unit' => '',
                'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge','phase' => '','tarif' => ''),
            'DEMAIN' => array('type' => 'info','name' => 'Couleur demain','subtype' => 'string','unit' => '',
                'isvisible' => 0,'generic_type' => 'GENERIC_INFO','template' => 'badge','phase' => '','tarif' => 'BBRH'),
            'PTEC' => array('type' => 'info','name' => 'Tarif en cours','subtype' => 'string','unit' => '',
                'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge','phase' => '','tarif' => ''),
            'BASE_evolution' => array('type' => 'info','name' => 'Evolution index (base)','subtype' => 'numeric',
                'unit' => 'W/min','isvisible' => 1,'generic_type' => '','template' => 'badge','phase' => '',
                'tarif' => 'BASE'),
            'HCHC_evolution' => array('type' => 'info','name' => 'Evolution index (HC)','subtype' => 'numeric',
                'unit' => 'W/min','isvisible' => 1,'generic_type' => '','template' => 'badge','phase' => '',
                'tarif' => 'HC'),
            'HCHP_evolution' => array('type' => 'info','name' => 'Evolution index (HP)','subtype' => 'numeric',
                'unit' => 'W/min','isvisible' => 1, 'generic_type' => '','template' => 'badge','phase' => '',
                'tarif' => 'HC'),
            'BBRHCJB_evolution' => array('type' => 'info','name' => 'Evolution index (HC jours bleus Tempo)',
                'subtype' => 'numeric','unit' => 'W/min','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'BBRH'),
            'BBRHPJB_evolution' => array('type' => 'info','name' => 'Evolution index (HP jours bleus Tempo)',
                'subtype' => 'numeric','unit' => 'W/min','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'BBRH'),
            'BBRHCJW_evolution' => array('type' => 'info','name' => 'Evolution index (HC jours blancs Tempo)',
                'subtype' => 'numeric','unit' => 'W/min','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'BBRH'),
            'BBRHPJW_evolution' => array('type' => 'info','name' => 'Evolution index (HP jours blancs Tempo)',
                'subtype' => 'numeric','unit' => 'W/min','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'BBRH'),
            'BBRHCJR_evolution' => array('type' => 'info','name' => 'Evolution index (HC jours rouges Tempo)',
                'subtype' => 'numeric','unit' => 'W/min','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'BBRH'),
            'BBRHPJR_evolution' => array('type' => 'info','name' => 'Evolution index (HP jours rouges Tempo)',
                'subtype' => 'numeric','unit' => 'W/min','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'BBRH'),
            'EJPHN_evolution' => array('type' => 'info','name' => 'Evolution index (normal EJP)','subtype' => 'numeric',
                'unit' => 'W','isvisible' => 0,'generic_type' => '','template' => 'badge','phase' => '',
                'tarif' => 'EJP'),
            'EJPHPM_evolution' => array('type' => 'info','name' => 'Evolution index (pointe mobile EJP)',
                'subtype' => 'numeric','unit' => 'W','isvisible' => 0,'generic_type' => '','template' => 'badge',
                'phase' => '','tarif' => 'EJP'),
            'ISOUSC' => array('type' => 'info','name' => 'Intensité souscrite','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 1,'generic_type' => '','template' => 'badge','phase' => '','tarif' => ''),
            'IMAX' => array('type' => 'info','name' => 'Intensité maximale','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 1,'generic_type' => '','template' => 'badge','phase' => ecodeviceIf::CPHASE_MONO,'tarif' => ''),
            'IMAX1' => array('type' => 'info','name' => 'Intensité maximale 1','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 0,'generic_type' => '','template' => 'badge','phase' => ecodeviceIf::CPHASE_TRI,'tarif' => ''),
            'IMAX2' => array('type' => 'info','name' => 'Intensité maximale 2','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 0,'generic_type' => '','template' => 'badge','phase' => ecodeviceIf::CPHASE_TRI,'tarif' => ''),
            'IMAX3' => array('type' => 'info','name' => 'Intensité maximale 3','subtype' => 'numeric','unit' => 'A',
                'isvisible' => 0,'generic_type' => '','template' => 'badge','phase' => ecodeviceIf::CPHASE_TRI,'tarif' => '')
        ),
        self::TYP_COMPTEUR => array(
            ecodeviceIf::CTYP_EAU => array(
                'consommationjour' => array('type' => 'info','name' => 'Consommation journalière','subtype' => 'numeric','unit' => 'l',
                    'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge'),
                'consommationtotal' => array('type' => 'info','name' => 'Consommation totale','subtype' => 'numeric','unit' => 'm3',
                    'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge'),
                'consommationinstantane' => array('type' => 'info','name' => 'Débit','subtype' => 'numeric','unit' => 'l/min',
                    'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge')
            ),
            ecodeviceIf::CTYP_GAZ => array(
                'consommationjour' => array('type' => 'info','name' => 'Consommation journalière','subtype' => 'numeric','unit' => 'l',
                    'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge'),
                'consommationtotal' => array('type' => 'info','name' => 'Consommation totale','subtype' => 'numeric','unit' => 'm3',
                    'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge'),
                'consommationinstantane' => array('type' => 'info','name' => 'Débit','subtype' => 'numeric','unit' => 'l/min',
                    'isvisible' => 1,'generic_type' => 'GENERIC_INFO','template' => 'badge')
            ),
            ecodeviceIf::CTYP_ELEC => array(
                'consommationjour' => array('type' => 'info','name' => 'Consommation journalière','subtype' => 'numeric','unit' => 'Wh',
                    'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge'),
                'consommationtotal' => array('type' => 'info','name' => 'Consommation totale','subtype' => 'numeric','unit' => 'kWh',
                    'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge'),
                'consommationinstantane' => array('type' => 'info','name' => 'Puissance','subtype' => 'numeric','unit' => 'W',
                    'isvisible' => 1,'generic_type' => 'POWER','template' => 'badge')
            ),
            ecodeviceIf::CTYP_FUEL => array(
                'consommationjour' => array('type' => 'info','name' => 'Consommation journalière','subtype' => 'numeric','unit' => 'l',
                    'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge'),
                'consommationtotal' => array('type' => 'info','name' => 'Consommation totale','subtype' => 'numeric','unit' => 'l',
                    'isvisible' => 1,'generic_type' => 'CONSUMPTION','template' => 'badge'),
                'consommationinstantane' => array('type' => 'info','name' => 'Débit','subtype' => 'numeric','unit' => 'l/h',
                    'isvisible' => 1,'generic_type' => 'POWER','template' => 'badge')
            )
        )
    );
    
    /**
     * Create a meter (Teleinfo or Compteur) associated to this carte ecodevice
     * @param string $meterId among ['T1', 'T2', 'C0', 'C1']
     */
    public function create($meterId) {
        self::checkMeterId($meterId);
        if (substr($meterId, 0, 1) == 'C') {
            $type = self::TYP_COMPTEUR;
        }
        else {
            $type = self::TYP_TELEINFO;
        }
        $logicalId = $this->getId() . '_' . $meterId;
        log::add(ecodevice::class, 'info', 'Création équipement ' . $type . ' avec l\'id logique ' . $logicalId);
        $eqLogic = new ecodevice();
        $eqLogic->setEqType_name(ecodevice::class);
        $eqLogic->setConfType($type);
        $eqLogic->setIsEnable(0);
        $eqLogic->setName(ucfirst($type) . substr($meterId, 1, 1));
        $eqLogic->setLogicalId($logicalId);
        $eqLogic->setIsVisible(0);
        $eqLogic->setCategory('energy', 1);
        $eqLogic->save();
        
    }
        
    public static function cron() {
        log::add(ecodevice::class, 'debug', 'begin cron');
        foreach (self::byEcodeviceType(self::TYP_CARTE) as $eqLogic) {
            $eqLogic->scan();
        }
        log::add(ecodevice::class, 'debug', 'end cron');
    }

    public function preInsert() {
//         switch ($this->getConfType()) {
//             case '':
//             case self::TYP_CARTE:
//                 $this->setConfType(self::TYP_CARTE);
//                 break;
//             case self::TYP_TELEINFO:
//                 $this->setIsEnable(0);
//                 break;
//             case self::TYP_COMPTEUR:
//                 $this->setIsEnable(0);
//                 break;
//         }
    }
    
    /**
     * Override setConfiguration to create Teleinfo or Compteur objects when requested
     * {@inheritDoc}
     * @see eqLogic::setConfiguration()
     */
    public function setConfiguration($_key, $_value) {
        if ($this->getConfType() == self::TYP_CARTE && in_array($_key, self::METER_IDS)) {
            if ($_value && $_value != $this->getConfMeterIsActivated($_key)) {
                log::add(ecodevice::class, 'debug', $this->getName() . ': ajoute compteur ' . $_value);
                $this->create($_key);
            }
        }
        return parent::setConfiguration($_key, $_value);
    }
    
    public function preSave() {
        // At equipment creation though the UI we need to initialize the type
        if (empty($this->getConfType())) {
            $this->setConfType(self::TYP_CARTE);
            $this->_post_action = self::POST_ACTION_SET_CMDS;
        }
        
        // Check that the Ecodevice is responding : if not, an exception is raised and saving is aborted
        $carte = $this->getCarteEqlogic();
        if ($carte->getIsEnable()) {
            $if = $carte->getEcodeviceIf();
            $if->checkIsResponsive();
        
            if ($this->getConfType() == self::TYP_TELEINFO) {
                $phase = $if->getTeleinfoPhase($this->getGceId());
                if ($phase != $this->getPhase()) {
                    $this->setPhase($phase);
                    $this->_post_action = self::POST_ACTION_SET_CMDS;
                }
                $tarif = $if->getTeleinfoData($this->getGceId(), 'OPTARIF');
                if ($tarif != $this->getTarif()) {
                    $this->setTarif($tarif);
                    $this->_post_action = self::POST_ACTION_SET_CMDS;
                }
            }
            
            else if ($this->getConfType() == self::TYP_COMPTEUR) {
                $compteurtype = $if->getCompteurType($this->getGceId());
                if ($compteurtype != $this->getCompteurType()) {
                    $this->setCompteurType($compteurtype);
                    $this->_post_action = self::POST_ACTION_SET_CMDS;
                }
            }
        }
    }
    
    public function postSave() {
        if (isset($this->_post_action) && $this->_post_action == self::POST_ACTION_SET_CMDS) {
            switch ($this->getConfType()) {
                case self::TYP_CARTE:
                    foreach (self::$_teleinfo_default_cmds[self::TYP_CARTE] as $logicalId => $data) {
                        ecodeviceCmd::create($this, $logicalId, $data);
                    }
                    break;
                    
                case self::TYP_TELEINFO:
                    foreach (self::$_teleinfo_default_cmds[self::TYP_TELEINFO] as $logicalId => $data) {
                        if (($this->getPhase() == $data['phase'] || $data['phase'] == '') &&
                            ($this->getTarif() == $data['tarif'] || $data['tarif'] == '')) {
                            $cmd = $this->getCmd(null, $logicalId);
                            if (!is_object($cmd)) {
                                ecodeviceCmd::create($this, $logicalId, $data);
                            }
                        }
                        else {
                            $cmd = $this->getCmd(null, $logicalId);
                            if (is_object($cmd)) {
                                $cmd->remove();
                            }
                        }
                    }
                    break;          

                case self::TYP_COMPTEUR:
                    foreach (self::$_teleinfo_default_cmds[self::TYP_COMPTEUR][$this->getCompteurType()] as $logicalId => $data) {
                        $cmd = $this->getCmd(null, $logicalId);
                        if (!is_object($cmd)) {
                            ecodeviceCmd::create($this, $logicalId, $data);
                        }
                    }
                    break;
            }
            unset($this->_post_action);
        }
    }
    
    public function preUpdate() {       
//         switch ($this->getConfType()) {
//             case self::TYP_CARTE:
//                 if ($this->getIsEnable()) {
//                     log::add(ecodevice::class, 'debug', 'get ' . $this->getUrl() . 'status.xml');
//                     $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
//                     if ($this->_xmlstatus === false) {
//                         throw new \Exception(__('L\'ecodevice ne repond pas.', __FILE__) . ' ' . $this->getName());
//                     }
//                 }
//                 break;
                
//             case self::TYP_TELEINFO:
//                 if ($this->getIsEnable()) {
//                     $if = $this->getEcodeviceIf();
//                     $this->_phase = $if->getTeleinfoPhase($this->getGceId());
//                     if ($this->_phase == '') {
//                         throw new \Exception(__('Le type de compteur est introuvable. Vérifier la communication entre l\'ecodevice et votre compteur.', __FILE__));
//                     }
//                 }
//                 break;
                
//             case self::TYP_COMPTEUR:
//                 if ($this->getIsEnable()) {
//                     if ($this->getCompteurType() == '') {
//                         throw new \Exception(__('Le type de compteur doit être défini.', __FILE__));
//                     }
//                 }
//                 break;
//        }
    }

    public function postInsert() {
//         switch ($this->getConfType()) {
//             case self::TYP_CARTE:
//                 foreach (self::$_teleinfo_default_cmds[self::TYP_CARTE] as $logicalId => $data) {
//                     ecodeviceCmd::create($this, $logicalId, $data);
//                 }
//                 break;
                
//             case self::TYP_TELEINFO:
//                 break;
                
//             case self::TYP_COMPTEUR:
//                 $consommationjour = $this->getCmd(null, 'consommationjour');
//                 if (!is_object($consommationjour)) {
//                     $consommationjour = new ecodeviceCmd();
//                     $consommationjour->setName('Consommation journalière');
//                     $consommationjour->setEqLogic_id($this->getId());
//                     $consommationjour->setType('info');
//                     $consommationjour->setSubType('numeric');
//                     $consommationjour->setLogicalId('consommationjour');
//                     $consommationjour->setEventOnly(1);
//                     $consommationjour->setIsVisible(1);
//                     $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
//                     $consommationjour->setTemplate('dashboard', 'badge');
//                     $consommationjour->setTemplate('mobile', 'badge');
//                     $consommationjour->setUnite('l');
//                     $consommationjour->save();
//                 }
//                 $consommationtotal = $this->getCmd(null, 'consommationtotal');
//                 if (!is_object($consommationtotal)) {
//                     $consommationtotal = new ecodeviceCmd();
//                     $consommationtotal->setName('Consommation total');
//                     $consommationtotal->setEqLogic_id($this->getId());
//                     $consommationtotal->setType('info');
//                     $consommationtotal->setSubType('numeric');
//                     $consommationtotal->setLogicalId('consommationtotal');
//                     $consommationtotal->setEventOnly(1);
//                     $consommationtotal->setIsVisible(1);
//                     $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
//                     $consommationtotal->setTemplate('dashboard', 'badge');
//                     $consommationtotal->setTemplate('mobile', 'badge');
//                     $consommationtotal->setUnite('l');
//                     $consommationtotal->save();
//                 }
//                 $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
//                 if (!is_object($consommationinstantane)) {
//                     $consommationinstantane = new ecodeviceCmd();
//                     $consommationinstantane->setName('Debit');
//                     $consommationinstantane->setEqLogic_id($this->getId());
//                     $consommationinstantane->setType('info');
//                     $consommationinstantane->setSubType('numeric');
//                     $consommationinstantane->setLogicalId('consommationinstantane');
//                     $consommationinstantane->setEventOnly(1);
//                     $consommationinstantane->setIsVisible(1);
//                     $consommationinstantane->setDisplay('generic_type', 'GENERIC_INFO');
//                     $consommationinstantane->setUnite('l/min');
//                     $consommationinstantane->save();
//                 }
//                 $this->setConfiguration('typecompteur', '');
//                 break;
//         }
    }
    
    public function postUpdate() {
//         switch ($this->getConfType()) {
//             case self::TYP_CARTE:
//                 $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
//                 $count           = 0;
//                 while ($this->_xmlstatus === false && $count < 3) {
//                     $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
//                     $count++;
//                 }
//                 if ($this->_xmlstatus !== false) {
//                     for ($compteurId = 0; $compteurId <= 1; $compteurId++) {
//                         $eqLogic     = self::byLogicalId($this->getId() . '_C' . $compteurId, ecodevice::class);
//                         if (is_object($eqLogic)) {
//                             # Verifie la configuration des compteurs fuel
//                             $xpathModele = '//c' . $compteurId . '_fuel';
//                             $status      = $this->_xmlstatus->xpath($xpathModele);

//                             if (count($status) != 0) {
//                                 if ($status[0] != 'selected') {
//                                     if ($eqLogic->getCompteurType() == 'Fuel') {
//                                         log::add(ecodevice::class, 'error', __('Le compteur ' . $eqLogic->getName() . ' n\'est pas configuré en mode fuel dans l\'ecodevice.', __FILE__));
//                                         continue;
//                                     }
//                                     elseif ($eqLogic->getCompteurType() == '') {
//                                         $eqLogic->setConfiguration('typecompteur', 'Eau');
//                                         $eqLogic->save();
//                                     }
//                                 }
//                                 else {
//                                     $eqLogic->setConfiguration('typecompteur', 'Fuel');
//                                     $eqLogic->save();
//                                 }
//                             }
//                             elseif ($eqLogic->getCompteurType() == 'Fuel') {
//                                 log::add(ecodevice::class, 'error', __('Le compteur ' . $eqLogic->getName() . ' n\'est pas configuré en mode fuel dans l\'ecodevice.', __FILE__));
//                                 continue;
//                             }
//                             elseif ($eqLogic->getCompteurType() == '') {
//                                 $eqLogic->setConfiguration('typecompteur', 'Eau');
//                                 $eqLogic->save();
//                             }
//                         }
//                     }
//                 }
//                 break;
                
//             case self::TYP_TELEINFO:
//                 if ($this->getIsEnable()) {
//                     foreach (self::$_teleinfo_default_cmds[self::TYP_TELEINFO] as $logicalId => $data) {
//                         if (($this->getTarif() == '' || $this->getTarif() == $data['tarif'] || $data['tarif'] == '') &&
//                             ($this->_phase == $data['phase'] || $data['phase'] == '')) {
//                             $cmd = $this->getCmd(null, $logicalId);
//                             if (!is_object($cmd)) {
//                                 ecodeviceCmd::create($this, $logicalId, $data);
//                             }
//                         }
//                         else {
//                             //FIXME: is that code usefull?
//                             $cmd = $this->getCmd(null, $logicalId);
//                             if (is_object($cmd)) {
//                                 $cmd->remove();
//                             }
//                         }
//                     }
//                 }
//                 break;
                
//             case self::TYP_COMPTEUR:    
//                 break;
//         }
    }

    public function postAjax() {
//         switch ($this->getConfType()) {
//             case self::TYP_CARTE:
//                 break;
                
//             case self::TYP_TELEINFO:
//                 break;
                
//             case self::TYP_COMPTEUR:
//                 if ($this->getIsEnable()) {
//                     foreach ($this->getCmd() as $cmd) {
//                         if (!in_array($cmd->getLogicalId(), array('consommationinstantane', 'consommationjour', 'consommationtotal', 'debitinstantane', 'nbimpulsiontotal', 'nbimpulsionminute', 'nbimpulsionjour'))) {
//                             $cmd->remove();
//                         }
//                     }
//                     if ($this->getCompteurType() == 'Fuel') {
//                         $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
//                         if (is_object($nbimpulsiontotal)) {
//                             $nbimpulsiontotal->remove();
//                         }
//                         $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
//                         if (is_object($nbimpulsionminute)) {
//                             $nbimpulsionminute->remove();
//                         }
//                         $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
//                         if (is_object($nbimpulsionjour)) {
//                             $nbimpulsionjour->remove();
//                         }
//                         $debitinstantane = $this->getCmd(null, 'debitinstantane');
//                         if (is_object($debitinstantane)) {
//                             $debitinstantane->remove();
//                         }
//                         $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
//                         if (!is_object($consommationinstantane)) {
//                             $consommationinstantane = new ecodeviceCmd();
//                             $consommationinstantane->setName('Débit');
//                             $consommationinstantane->setEqLogic_id($this->getId());
//                             $consommationinstantane->setType('info');
//                             $consommationinstantane->setSubType('numeric');
//                             $consommationinstantane->setLogicalId('consommationinstantane');
//                             $consommationinstantane->setUnite('ml/h');
//                             $consommationinstantane->setEventOnly(1);
//                             $consommationinstantane->setIsVisible(1);
//                             $consommationinstantane->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationinstantane->save();
//                         }
//                         $consommationtotal = $this->getCmd(null, 'consommationtotal');
//                         if (!is_object($consommationtotal)) {
//                             $consommationtotal = new ecodeviceCmd();
//                             $consommationtotal->setName('Consommation totale');
//                             $consommationtotal->setEqLogic_id($this->getId());
//                             $consommationtotal->setType('info');
//                             $consommationtotal->setSubType('numeric');
//                             $consommationtotal->setLogicalId('consommationtotal');
//                             $consommationtotal->setUnite('ml');
//                             $consommationtotal->setEventOnly(1);
//                             $consommationtotal->setIsVisible(1);
//                             $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationtotal->setTemplate('dashboard', 'badge');
//                             $consommationtotal->setTemplate('mobile', 'badge');
//                             $consommationtotal->save();
//                         }
//                         $consommationjour = $this->getCmd(null, 'consommationjour');
//                         if (!is_object($consommationjour)) {
//                             $consommationjour = new ecodeviceCmd();
//                             $consommationjour->setName('Consommation journalière');
//                             $consommationjour->setEqLogic_id($this->getId());
//                             $consommationjour->setType('info');
//                             $consommationjour->setSubType('numeric');
//                             $consommationjour->setLogicalId('consommationjour');
//                             $consommationjour->setUnite('ml');
//                             $consommationjour->setEventOnly(1);
//                             $consommationjour->setIsVisible(1);
//                             $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationjour->setTemplate('dashboard', 'badge');
//                             $consommationjour->setTemplate('mobile', 'badge');
//                             $consommationjour->save();
//                         }
//                     }
//                     elseif ($this->getCompteurType() == 'Eau') {
//                         $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
//                         if (is_object($nbimpulsiontotal)) {
//                             $nbimpulsiontotal->remove();
//                         }
//                         $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
//                         if (is_object($nbimpulsionminute)) {
//                             $nbimpulsionminute->remove();
//                         }
//                         $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
//                         if (is_object($nbimpulsionjour)) {
//                             $nbimpulsionjour->remove();
//                         }
//                         $consommationjour = $this->getCmd(null, 'consommationjour');
//                         if (!is_object($consommationjour)) {
//                             $consommationjour = new ecodeviceCmd();
//                             $consommationjour->setName('Consommation journalière');
//                             $consommationjour->setEqLogic_id($this->getId());
//                             $consommationjour->setType('info');
//                             $consommationjour->setSubType('numeric');
//                             $consommationjour->setLogicalId('consommationjour');
//                             $consommationjour->setEventOnly(1);
//                             $consommationjour->setIsVisible(1);
//                             $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationjour->setTemplate('dashboard', 'badge');
//                             $consommationjour->setTemplate('mobile', 'badge');
//                             $consommationjour->setUnite('l');
//                             $consommationjour->save();
//                         }
//                         $consommationtotal = $this->getCmd(null, 'consommationtotal');
//                         if (!is_object($consommationtotal)) {
//                             $consommationtotal = new ecodeviceCmd();
//                             $consommationtotal->setName('Consommation total');
//                             $consommationtotal->setEqLogic_id($this->getId());
//                             $consommationtotal->setType('info');
//                             $consommationtotal->setSubType('numeric');
//                             $consommationtotal->setLogicalId('consommationtotal');
//                             $consommationtotal->setEventOnly(1);
//                             $consommationtotal->setIsVisible(1);
//                             $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationtotal->setTemplate('dashboard', 'badge');
//                             $consommationtotal->setTemplate('mobile', 'badge');
//                             $consommationtotal->setUnite('l');
//                             $consommationtotal->save();
//                         }
//                         $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
//                         if (!is_object($consommationinstantane)) {
//                             $consommationinstantane = new ecodeviceCmd();
//                             $consommationinstantane->setName('Debit');
//                             $consommationinstantane->setEqLogic_id($this->getId());
//                             $consommationinstantane->setType('info');
//                             $consommationinstantane->setSubType('numeric');
//                             $consommationinstantane->setLogicalId('consommationinstantane');
//                             $consommationinstantane->setEventOnly(1);
//                             $consommationinstantane->setIsVisible(1);
//                             $consommationinstantane->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationinstantane->setUnite('l/min');
//                             $consommationinstantane->save();
//                         }
//                     }
//                     elseif ($this->getCompteurType() == 'Gaz') {
//                         $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
//                         if (is_object($nbimpulsiontotal)) {
//                             $nbimpulsiontotal->remove();
//                         }
//                         $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
//                         if (is_object($nbimpulsionminute)) {
//                             $nbimpulsionminute->remove();
//                         }
//                         $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
//                         if (is_object($nbimpulsionjour)) {
//                             $nbimpulsionjour->remove();
//                         }
//                         $consommationjour = $this->getCmd(null, 'consommationjour');
//                         if (!is_object($consommationjour)) {
//                             $consommationjour = new ecodeviceCmd();
//                             $consommationjour->setName('Consommation journalière');
//                             $consommationjour->setEqLogic_id($this->getId());
//                             $consommationjour->setType('info');
//                             $consommationjour->setSubType('numeric');
//                             $consommationjour->setLogicalId('consommationjour');
//                             $consommationjour->setEventOnly(1);
//                             $consommationjour->setIsVisible(1);
//                             $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationjour->setTemplate('dashboard', 'badge');
//                             $consommationjour->setTemplate('mobile', 'badge');
//                             $consommationjour->setUnite('dm³');
//                             $consommationjour->save();
//                         }
//                         $consommationtotal = $this->getCmd(null, 'consommationtotal');
//                         if (!is_object($consommationtotal)) {
//                             $consommationtotal = new ecodeviceCmd();
//                             $consommationtotal->setName('Consommation total');
//                             $consommationtotal->setEqLogic_id($this->getId());
//                             $consommationtotal->setType('info');
//                             $consommationtotal->setSubType('numeric');
//                             $consommationtotal->setLogicalId('consommationtotal');
//                             $consommationtotal->setEventOnly(1);
//                             $consommationtotal->setIsVisible(1);
//                             $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationtotal->setTemplate('dashboard', 'badge');
//                             $consommationtotal->setTemplate('mobile', 'badge');
//                             $consommationtotal->setUnite('dm³');
//                             $consommationtotal->save();
//                         }
//                         $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
//                         if (!is_object($consommationinstantane)) {
//                             $consommationinstantane = new ecodeviceCmd();
//                             $consommationinstantane->setName('Debit');
//                             $consommationinstantane->setEqLogic_id($this->getId());
//                             $consommationinstantane->setType('info');
//                             $consommationinstantane->setSubType('numeric');
//                             $consommationinstantane->setLogicalId('consommationinstantane');
//                             $consommationinstantane->setEventOnly(1);
//                             $consommationinstantane->setIsVisible(1);
//                             $consommationinstantane->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationinstantane->setUnite('dm³/min');
//                             $consommationinstantane->save();
//                         }
//                     }
//                     elseif ($this->getCompteurType() == 'Electricité') {
//                         $nbimpulsiontotal = $this->getCmd(null, 'nbimpulsiontotal');
//                         if (is_object($nbimpulsiontotal)) {
//                             $nbimpulsiontotal->remove();
//                         }
//                         $nbimpulsionminute = $this->getCmd(null, 'nbimpulsionminute');
//                         if (is_object($nbimpulsionminute)) {
//                             $nbimpulsionminute->remove();
//                         }
//                         $nbimpulsionjour = $this->getCmd(null, 'nbimpulsionjour');
//                         if (is_object($nbimpulsionjour)) {
//                             $nbimpulsionjour->remove();
//                         }
//                         $consommationjour = $this->getCmd(null, 'consommationjour');
//                         if (!is_object($consommationjour)) {
//                             $consommationjour = new ecodeviceCmd();
//                             $consommationjour->setName('Consommation journalière');
//                             $consommationjour->setEqLogic_id($this->getId());
//                             $consommationjour->setType('info');
//                             $consommationjour->setSubType('numeric');
//                             $consommationjour->setLogicalId('consommationjour');
//                             $consommationjour->setEventOnly(1);
//                             $consommationjour->setIsVisible(1);
//                             $consommationjour->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationjour->setTemplate('dashboard', 'badge');
//                             $consommationjour->setTemplate('mobile', 'badge');
//                             $consommationjour->setUnite('Wh');
//                             $consommationjour->save();
//                         }
//                         $consommationtotal = $this->getCmd(null, 'consommationtotal');
//                         if (!is_object($consommationtotal)) {
//                             $consommationtotal = new ecodeviceCmd();
//                             $consommationtotal->setName('Consommation total');
//                             $consommationtotal->setEqLogic_id($this->getId());
//                             $consommationtotal->setType('info');
//                             $consommationtotal->setSubType('numeric');
//                             $consommationtotal->setLogicalId('consommationtotal');
//                             $consommationtotal->setEventOnly(1);
//                             $consommationtotal->setIsVisible(1);
//                             $consommationtotal->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationtotal->setTemplate('dashboard', 'badge');
//                             $consommationtotal->setTemplate('mobile', 'badge');
//                             $consommationtotal->setUnite('Wh');
//                             $consommationtotal->save();
//                         }
//                         $consommationinstantane = $this->getCmd(null, 'consommationinstantane');
//                         if (!is_object($consommationinstantane)) {
//                             $consommationinstantane = new ecodeviceCmd();
//                             $consommationinstantane->setName('Consommation instantanée');
//                             $consommationinstantane->setEqLogic_id($this->getId());
//                             $consommationinstantane->setType('info');
//                             $consommationinstantane->setSubType('numeric');
//                             $consommationinstantane->setLogicalId('consommationinstantane');
//                             $consommationinstantane->setEventOnly(1);
//                             $consommationinstantane->setIsVisible(1);
//                             $consommationinstantane->setDisplay('generic_type', 'GENERIC_INFO');
//                             $consommationinstantane->setUnite('Wh');
//                             $consommationinstantane->save();
//                         }
//                     }
//                 }
//                 break;
//         }
    }

    /**
     * On ecodevice removal, remove also associated teleinfo and compteur
     */
    public function preRemove() {
        log::add(ecodevice::class, 'info', 'Suppression ecodevice ' . $this->getConfType() . ' ' . $this->getName());
        if ($this->getConfType() == self::TYP_CARTE) {
            /** @var ecodevice $eqLogic */
            foreach (self::byEcodeviceType(self::TYP_COMPTEUR) as $eqLogic) {
                if ($eqLogic->getCarteEqlogicId() == $this->getId()) {
                    $eqLogic->remove();
                }
            }
            foreach (self::byEcodeviceType(self::TYP_TELEINFO) as $eqLogic) {
                if ($eqLogic->getCarteEqlogicId() == $this->getId()) {
                    $eqLogic->remove();
                }
            }
        }
    }
    
    
    /**
     * On ecodevice removal which is not a carte object, set the configuration meter id of the associated 
     * carte object to false
     */
    public function postRemove() {
        if ($this->getConfType() != self::TYP_CARTE) {
            $carte = $this->getCarteEqlogic();
            $carte->setConfMeterIsActivated($this->getMeterId(), false);
            $carte->save(true);
        }
    }
    

    public function configPush($url_serveur = null) {
        switch ($this->getConfType()) {
            case self::TYP_CARTE:
                if (config::byKey('internalAddr') == '') {
                    throw new \Exception(__('L\'adresse IP du serveur Jeedom doit être renseignée.<br>Général -> Administration -> Configuration.<br>Configuration réseaux -> Adresse interne', __FILE__));
                }
                if ($this->getIsEnable()) {
                    throw new \Exception('Configurer l\'URL suivante pour un rafraichissement plus rapide dans l\'ecodevice : page index=>notification :<br>http://' . config::byKey('internalAddr') . '/jeedom/core/api/jeeApi.php?api=' . jeedom::getApiKey(ecodevice::class) . '&type=ecodevice&id=' . $this->getCarteEqlogicId() . '&message=data_change<br>Attention surcharge possible importante.');
                    $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                    $count           = 0;
                    while ($this->_xmlstatus === false && $count < 3) {
                        $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'status.xml');
                        $count++;
                    }
                    if ($this->_xmlstatus === false) {
                        log::add(ecodevice::class, 'error', __('L\'ecodevice ne repond pas.', __FILE__) . ' ' . $this->getName() . ' get ' . preg_replace('/:[^:]*@/', ':XXXX@', $this->getUrl()) . 'status.xml');
                        return false;
                    }
                    /** @var ecodevice $eqLogic */
                    foreach (self::byEcodeviceType(self::TYP_COMPTEUR) as $eqLogic) {
                        if ($eqLogic->getIsEnable() && $eqLogic->getCarteEqlogicId() == $this->getId()) {
                            $eqLogic->configPush($this->getUrl());
                        }
                    }
                    foreach (self::byEcodeviceType(self::TYP_TELEINFO) as $eqLogic) {
                        if ($eqLogic->getIsEnable() && $eqLogic->getCarteEqlogicId() == $this->getId()) {
                            $eqLogic->configPush($this->getUrl());
                        }
                    }
                }
                break;
                
            case self::TYP_TELEINFO:
                $gceid       = $this->getGceId();
                $url_serveur .= 'protect/settings/notif' . $gceid . 'P.htm';
                for ($compteur = 0; $compteur < 6; $compteur++) {
                    log::add(ecodevice::class, 'debug', 'Url ' . $url_serveur);
                    $data = array('num'  => $compteur + ($gceid - 1) * 6,
                        'act'  => $compteur + 3,
                        'serv' => config::byKey('internalAddr'),
                        'port' => 80,
                        'url'  => '/jeedom/core/api/jeeApi.php?api=' . jeedom::getApiKey(ecodevice::class) . '&type=ecodevice&plugin=ecodevice&id=' . $this->getCarteEqlogicId() . '&message=data_change');
                    //					'url' => '/jeedom/core/api/jeeApi.php?api='.jeedom::getApiKey(ecodevice::class).'&type=ecodevice&id='.$this->getId().'&message=data_change');

                    $options = array(
                        'http' => array(
                            'header'  => 'Content-type: application/x-www-form-urlencoded\r\n',
                            'method'  => 'POST',
                            'content' => http_build_query($data),
                        ),
                    );
                    $context = stream_context_create($options);
                    $result  = @file_get_contents($url_serveur, false, $context);
                }
                break;
                
            case self::TYP_COMPTEUR:
                break;
        }
    }

    public function event() {
        switch ($this->getConfType()) {
            case self::TYP_CARTE:
                foreach (eqLogic::byType(ecodevice::class) as $eqLogic) {
                    if ($eqLogic->getId() == init('id')) {
                        $eqLogic->scan();
                    }
                }
                break;
            case self::TYP_TELEINFO:
                $cmd = ecodeviceCmd::byId(init('id'));
                if (!is_object($cmd)) {
                    throw new \Exception('Commande ID virtuel inconnu : ' . init('id'));
                }
                $cmd->event(init('value'));
                break;
            case self::TYP_COMPTEUR:
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
            log::add(ecodevice::class, 'debug', 'Scan ' . $this->getName());
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
                log::add(ecodevice::class, 'error', __('L\'ecodevice ne repond pas.', __FILE__) . ' ' . $this->getName() . ' get ' . preg_replace('/:[^:]*@/', ':XXXX@', $this->getUrl()) . 'status.xml');
                return false;
            }
            foreach (self::byEcodeviceType(self::TYP_COMPTEUR) as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getCarteEqlogicId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    if ($eqLogic->getCompteurType() == 'Fuel') {
                        # Verifie la configuration des compteurs fuel
                        $xpathModele = '//c' . $gceid . '_fuel';
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            if ($status[0] != 'selected') {
                                log::add(ecodevice::class, 'error', __('Le compteur ' . $eqLogic->getName() . ' n\'est pas configuré en mode fuel dans l\'ecodevice.', __FILE__));
                            }
                        }
                        $xpathModele = '//count' . $gceid;
                        $status      = $this->_xmlstatus->xpath($xpathModele);
                        if (count($status) != 0) {
                            $consommationtotal     = intval($status[0]);
                            $consommationtotal_cmd = $eqLogic->getCmd(null, 'consommationtotal');
                            log::add(ecodevice::class, 'debug', 'Change consommationtotal of ' . $eqLogic->getName());
                            $consommationtotal_cmd->event($consommationtotal);
                        }
                        $xpathModele = '//c' . $gceid . 'day';
                        $status      = $this->_xmlstatus->xpath($xpathModele);
                        if (count($status) != 0) {
                            $consommationjour     = intval($status[0]);
                            $consommationjour_cmd = $eqLogic->getCmd(null, 'consommationjour');
                            log::add(ecodevice::class, 'debug', 'Change consommationjour of ' . $eqLogic->getName());
                            $consommationjour_cmd->event($consommationjour);
                        }
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);
                        if (count($status) != 0) {
                            $consommationinstantane     = intval($status[0]) * 10;
                            $consommationinstantane_cmd = $eqLogic->getCmd(null, 'consommationinstantane');
                            log::add(ecodevice::class, 'debug', 'Change consommationinstantane of ' . $eqLogic->getName());
                            $consommationinstantane_cmd->event($consommationinstantane);
                        }
                    }
                    else {
                        # mode eau, gaz, electricité
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $eqLogic_cmd = $eqLogic->getCmd(null, 'consommationinstantane');
                            log::add(ecodevice::class, 'debug', 'Change consommationinstantane of ' . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                        $xpathModele = '//c' . $gceid . 'day';
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $eqLogic_cmd = $eqLogic->getCmd(null, 'consommationjour');
                            log::add(ecodevice::class, 'debug', 'Change consommationjour of ' . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                        $xpathModele = '//count' . $gceid;
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $consommationtotal_cmd = $eqLogic->getCmd(null, 'consommationtotal');
                            log::add(ecodevice::class, 'debug', 'Change consommationtotal of ' . $eqLogic->getName());
                            $consommationtotal_cmd->event((string) $status[0]);
                        }
                    }
                }
            }
            
            /** @var ecodevice $eqLogic */
            foreach (self::byEcodeviceType(self::TYP_TELEINFO) as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getCarteEqlogicId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    $this->_xmlstatus = @simplexml_load_file($this->getUrl() . 'protect/settings/teleinfo' . $gceid . '.xml');
                    if ($this->_xmlstatus === false) {
                        if ($statuscmd->execCmd() != 0) {
                            $statuscmd->event(0);
                        }
                        log::add(ecodevice::class, 'error', __('L\'ecodevice ne repond pas.', __FILE__) . ' ' . $this->getName() . ' get ' . preg_replace('/:[^:]*@/', ':XXXX@', $this->getUrl()) . 'protect/settings/teleinfo' . $gceid . '.xml');
                        return false;
                    }
                    $xpathModele = '//response';
                    $status      = $this->_xmlstatus->xpath($xpathModele);

                    if (count($status) != 0) {
                        foreach ($status[0] as $item => $data) {
                            if (substr($item, 0, 3) == 'T' . $gceid . '_') {
                                $eqLogic_cmd = $eqLogic->getCmd(null, substr($item, 3));
                                if (is_object($eqLogic_cmd)) {
                                    $eqLogic_cmd_evol = $eqLogic->getCmd(null, substr($item, 3) . '_evolution');
                                    if (is_object($eqLogic_cmd_evol)) {
                                        $ancien_data = $eqLogic_cmd->execCmd();
                                        if ($ancien_data != $data) {
                                            log::add(ecodevice::class, 'debug', $eqLogic_cmd->getName() . ' Change ' . $data);
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
            log::add(ecodevice::class, 'debug', 'Scan rapide ' . $this->getName());
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
                log::add(ecodevice::class, 'error', __('L\'ecodevice ne repond pas.', __FILE__) . ' ' . $this->getName() . ' get ' . preg_replace('/:[^:]*@/', ':XXXX@', $this->getUrl()) . 'status.xml');
                return false;
            }
            
            /**
             * Scan and update non teleinfo counters
             * @var ecodevice $eqLogic
             */
            foreach (self::byEcodeviceType(self::TYP_COMPTEUR) as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getCarteEqlogicId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    if ($eqLogic->getCompteurType() == 'Fuel') {
                        # mode fuel
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $consommationinstantane = $status[0] / 100;
                            $eqLogic_cmd            = $eqLogic->getCmd(null, 'consommationinstantane');
                            log::add(ecodevice::class, 'debug', 'Change consommationinstantane of ' . $eqLogic->getName());
                            $eqLogic_cmd->event($consommationinstantane);
                        }
                    }
                    else {
                        # mode eau
                        $xpathModele = '//meter' . ($gceid + 2);
                        $status      = $this->_xmlstatus->xpath($xpathModele);

                        if (count($status) != 0) {
                            $eqLogic_cmd = $eqLogic->getCmd(null, 'consommationinstantane');
                            log::add(ecodevice::class, 'debug', 'Change consommationinstantane of ' . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                    }
                }
            }
            
            /**
             * Scan and update teleinfo counters
             * @var ecodevice $eqLogic
             */
            foreach (self::byEcodeviceType(self::TYP_TELEINFO) as $eqLogic) {
                if ($eqLogic->getIsEnable() && $eqLogic->getCarteEqlogicId() == $this->getId()) {
                    $gceid = $eqLogic->getGceId();
                    $item        = 'T' . $gceid . '_PPAP';
                    $xpathModele = '//' . $item;
                    $status      = $this->_xmlstatus->xpath($xpathModele);

                    if (count($status) != 0) {
                        $eqLogic_cmd = $eqLogic->getCmd(null, substr($item, 3));
                        if (is_object($eqLogic_cmd)) {
                            log::add(ecodevice::class, 'debug', 'Change ' . $item . ' of ' . $eqLogic->getName());
                            $eqLogic_cmd->event((string) $status[0]);
                        }
                    }
                }
            }
        }
    }

    public static function daemon() {
        $starttime = microtime(true);
        foreach (self::byEcodeviceType(self::TYP_CARTE) as $eqLogic) {
            $eqLogic->scan_rapide();
        }
        $endtime = microtime(true);
        if ($endtime - $starttime < config::byKey('temporisation_lecture', ecodevice::class, 60, true)) {
            usleep(floor((config::byKey('temporisation_lecture', ecodevice::class) + $starttime - $endtime) * 1000000));
        }
    }

    public static function deamon_info() {
        $return          = array();
        $return['log']   = '';
        $return['state'] = 'nok';
        $cron            = cron::byClassAndFunction(ecodevice::class, 'daemon');
        if (is_object($cron) && $cron->running()) {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start($_debug = false) {
        self::deamon_stop();
        $cron = cron::byClassAndFunction(ecodevice::class, 'daemon');
        if (!is_object($cron)) {
            $cron = new cron();
            $cron->setClass(ecodevice::class);
            $cron->setFunction('daemon');
            $cron->setEnable(1);
            $cron->setDeamon(1);
            $cron->setTimeout(1440);
            $cron->setSchedule('* * * * *');
            $cron->save();
        }
        log::add(ecodevice::class, 'info', 'daemon start');
        $cron->run();
    }

    public static function deamon_stop() {
        $cron = cron::byClassAndFunction(ecodevice::class, 'daemon');
        if (is_object($cron)) {
            $cron->halt();
            log::add(ecodevice::class, 'info', 'daemon stop');
        }
        else {
            log::add(ecodevice::class, 'warning', __('Tâche cron associé au démon introuvable', __FILE__));
        }
    }
    
    public function getImage() {
        $f = '/resources/' . $this->getConfType() . '.svg';
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
     * Return all ecodevice objects of the given type 
     * @param string $type among ecodevice::TYP_CARTE, ecodevice::TYP_TELEINFO, ecodevice::TYP_COMPTEUR
     * @throw \Exception if $type is not in the autorized range
     * @return ecodevice[]
     */
    public static function byEcodeviceType($type) {
        self::checkType($type);
        return eqLogic::byTypeAndSearhConfiguration(ecodevice::class, '"type":"' . $type . '"');
    }
    
    /**
     * Return the eqLogic id of the ecodevice carte object related to this meter, or 0 if this eqLogic is itself
     * an ecodevice card
     * @return int
     */
    public function getCarteEqlogicId() {
        $id = empty($this->getLogicalId()) ? 0 : substr($this->getLogicalId(), 0, strpos($this->getLogicalId(), '_'));
        return $id;
    }
    
    /**
     * Return the carte ecodevice eqlogic object related to this ecodevice object
     * @throw exception if the carte object does not exist
     * @return ecodevice
     */
    private function getCarteEqlogic() {
        if (isset($this->_carte))
            return $this->_carte;
        
        $this->_carte = empty($this->getLogicalId()) ? $this : eqLogic::byId($this->getCarteEqlogicId());
        if (! is_object($this->_carte))
            throw new \Exception(__('L\'ecodevice associé au compteur', __FILE__) . ' ' . $this->getName() . __('n\'existe pas', __FILE__));
        
        return $this->_carte;
    }
    
    /**
     * Returns the ecodeviceIf object allowing to dialog with the ecodevice equipment related to this ecodevice object
     * @return ecodeviceIf
     */
    private function getEcodeviceIf() {
        if (isset($this->_ecodeviceIf)) {
            return $this->_ecodeviceIf;
        }
        
        $this->_ecodeviceIf = new ecodeviceIf($this->getCarteEqlogic()->getName(), $this->getUrl());
        return $this->_ecodeviceIf;
    }
    
    /**
     * Returns the URL of the ecodevice equipment related to this ecodevice object
     * @return string
     */
    public function getUrl() {
        $carte = $this->getCarteEqlogic();
        $url = 'http://';
        if ($carte->getConfiguration('username') != '') {
            $url .= $carte->getConfiguration('username') . ':' . $carte->getConfiguration('password') . '@';
        }
        $url .= $carte->getConfiguration('ip');
        if ($carte->getConfiguration('port') != '') {
            $url .= ':' . $carte->getConfiguration('port');
        }
        return $url . '/';
    }    
    
    /**
     * Return whether or not the given meter is activated for this ecodevice
     * Important: this ecodevice shall be of type carte
     * @param string $meterId among ['T1', 'T2', 'C0', 'C1']
     * @return string '0' or '1'
     */
    public function getConfMeterIsActivated($meterId) {
        return $this->getConfiguration($meterId, '0');
    }
    
    /**
     * Set wether or not the given meter is activated for this ecodevice
     * Important: this ecodevice shall be of type carte
     * @param string $meterId among ['T1', 'T2', 'C0', 'C1']
     * @param string $isActivated '0' or '1'
     * @throw Exception if $type is not in the autorized range
     */
    public function setConfMeterIsActivated($meterId, $isActivated) {
        self::checkMeterId($meterId);
        $this->setConfiguration($meterId, $isActivated);
    }
    
    /**
     * Return the meter id (among ['T1', 'T2', 'C0', 'C1'] for a teleinfo or compteur ecodevice object.
     * Return '' for a carte object.
     * @return string
     */
    public function getMeterId() {
        return substr($this->getLogicalId(), strpos($this->getLogicalId(), '_') + 1, 2);
    }
    
    /**
     * Return the id of this meter in the data provided by the ecodevice
     * @return string 1 or 2 for a Teleinfo meter, 0 or 1 for a Compteur meter
     */
    private function getGceId() {
        return substr($this->getLogicalId(), strpos($this->getLogicalId(), '_') + 2, 1);
    }

    /**
     * Return the meter type configuration of this ecodevice object
     * @return string
     */
    public function getCompteurType() {
        return $this->getConfiguration('typecompteur');
    }

    /**
     * Return the meter type configuration of this ecodevice object
     * @return string
     */
    public function setCompteurType($type) {
        ecodeviceIf::checkCompteurType($type);
        return $this->setConfiguration('typecompteur', $type);
    }
    
    /**
     * Return this ecodevice object type
     * @return string among ecodevice::TYP_CARTE, ecodevice::TYP_TELEINFO, ecodevice::TYP_COMPTEUR
     */
    public function getConfType() {
        return $this->getConfiguration('type');
    }
    
    /**
     * Set this ecodevice object type
     * @param string $type among ecodevice::TYP_CARTE, ecodevice::TYP_TELEINFO, ecodevice::TYP_COMPTEUR
     * @throw \Exception if $type is not in the autorized range
     */
    public function setConfType($type) {
        self::checkType($type);
        $this->setConfiguration('type', $type);
    }
    
    /**
     * @return string ecodeviceIf::CPHASE_MONO or ecodeviceIf::CPHASE_TRI
     */
    public function getPhase() {
        return $this->getConfiguration('phase');
    }
    
    public function setPhase($phase) {
        ecodeviceIf::checkPhase($phase);
        $this->setConfiguration('phase', $phase);
    }
    
    public function getTarif() {
        return $this->getConfiguration('tarification');
    }
    
    public function setTarif($tarif) {
        return $this->setConfiguration('tarification', $tarif);
    }
    
    /**
     * Initialize the phase of this teleinfo according to the existing commands.
     * This method is supposed to be used when migrating to the version having introduced the phase configuration parameter only. 
     */
    public function initPhaseFromExistingCmds() {
        foreach (self::$_teleinfo_default_cmds[self::TYP_TELEINFO] as $logicalId => $data) {
            if (in_array($data['phase'], array(ecodeviceIf::CPHASE_MONO, ecodeviceIf::CPHASE_TRI))) {
                if (is_object($this->getCmd(null, $logicalId))) {
                    $this->setPhase($data['phase']);
                }
                break;
            }
        }
    }
    
    /**
     * Check the given $type is among the given list : if not, throw an exception
     * @param string $type
     * @param string[] $expectations array of expected types (by default ecodevice::TYP_CARTE, ecodevice::TYP_TELEINFO, ecodevice::TYP_COMPTEUR)
     * @throws \Exception if $type is not in the autorized range
     */
    private static function checkType($type, $expected=array(ecodevice::TYP_CARTE, ecodevice::TYP_TELEINFO, ecodevice::TYP_COMPTEUR)) {
        if (! in_array($type, array(ecodevice::TYP_CARTE, ecodevice::TYP_TELEINFO, ecodevice::TYP_COMPTEUR)))
            throw new \Exception(__('Type d\'équipement non attendu', __FILE__) . ' : ' . $type);
    }
    
    /**
     * Check the given $meterId is within 'T1', 'T2', 'C0', 'C1'
     * @param string $meterId
     * @throws \Exception if $meterId is not in the autorized range
     */
    private static function checkMeterId($meterId) {
        if (! in_array($meterId, self::METER_IDS))
            throw new \Exception(__('Identifiant de compteur non attendu', __FILE__) . ' : ' . $meterId);
    } 
}

