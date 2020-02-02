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
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';


function ecodevice_init() {
    if (config::byKey('temporisation_lecture', 'ecodevice', '') == '') {
        config::save('temporisation_lecture', 10, 'ecodevice');
    }
    jeedom::getApiKey('ecodevice');
    if (config::byKey('api::ecodevice::mode') == '') {
        config::save('api::ecodevice::mode', 'enable');
    }
    ecodevice::deamon_start();
}

function ecodevice_install() {
    ecodevice_init();
}

function ecodevice_update_remove_subclasses() {
    /** @var ecodeviceCmd $cmd */
    /** @var ecodevice $eqLogic */
        
    /* Remove no more used config parameters */
    config::remove('listChildren', 'ecodevice');
    config::remove('subClass', 'ecodevice');
    
    foreach (eqLogic::byType('ecodevice_teleinfo') as $SubeqLogic) {
        $SubeqLogic->setConfType(ecodevice::TYP_TELEINFO);
        $SubeqLogic->setEqType_name('ecodevice');
        $SubeqLogic->save();
        foreach (cmd::byEqLogicId($SubeqLogic->getId()) as $cmd) {
            $cmd->setEqType('ecodevice');
            $cmd->save();
        }
    }
    foreach (eqLogic::byType('ecodevice_compteur') as $SubeqLogic) {
        $SubeqLogic->setConfType(ecodevice::TYP_COMPTEUR);
        $SubeqLogic->setEqType_name('ecodevice');
        $SubeqLogic->save();
        foreach (cmd::byEqLogicId($SubeqLogic->getId()) as $cmd) {
            $cmd->setEqType('ecodevice');
            $cmd->save();
        }
    }
    foreach (eqLogic::byType('ecodevice') as $eqLogic) {
        if ($eqLogic->getConfType() == '') {
            $eqLogic->setConfType(ecodevice::TYP_CARTE);
            $eqLogic->save();
        }
        foreach (cmd::byEqLogicId($eqLogic->getId()) as $cmd) {
            if ( $cmd->getEqType() != 'ecodevice') {
                $cmd->setEqType('ecodevice');
                $cmd->save();
            }
        }
    }
    
    // FIXME: usefull?
    foreach (eqLogic::byType('ecodevice') as $eqLogic) {
        if ($eqLogic->getConfType() == ecodevice::TYP_COMPTEUR)	{
            if ($eqLogic->getIsEnable()) {
                $eqLogic->postAjax();
                $eqLogic->save();
            }
        }
    }
        
    // Remove old unused source file
    foreach (array("compteur", "teleinfo") as $type) {
        if (file_exists (dirname(__FILE__) . '/../core/class/ecodevice_'.$type.'.class.php'))
            unlink(dirname(__FILE__) . '/../core/class/ecodevice_'.$type.'.class.php');
            if (file_exists (dirname(__FILE__) . '/../desktop/php/ecodevice_'.$type.'.php'))
                unlink(dirname(__FILE__) . '/../desktop/php/ecodevice_'.$type.'.php');
    }
}

function ecodevice_update_complete_rewrite() {
    /** @var ecodeviceCmd $cmd */
    /** @var ecodevice $eqLogic */
    /** @var ecodevice $eqSubLogic */
    
    /* Remove no more used cron */
    foreach (array('cron', 'pull') as $func) {
        $cron = cron::byClassAndFunction('ecodevice', $func);
        if (is_object($cron)) {
            $cron->stop();
            $cron->remove();
        }
    }
    
    $eqLogics = eqLogic::byType('ecodevice');
    foreach ($eqLogics as $eqLogic) {
        if ($eqLogic->getConfType() == ecodevice::TYP_CARTE) {
            foreach ($eqLogics as $eqSubLogic) {
                if ($eqSubLogic->getCarteEqlogicId() == $eqLogic->getId()) {                   
                    $meterId = $eqSubLogic->getMeterId();
                    if ($meterId != '') {
                        if ($eqLogic->getConfMeterIsActivated($meterId) != '1') {
                            $eqLogic->setConfMeterIsActivated($meterId, '1');
                            $eqLogic->setChanged(true);
                        }
                    }
                }
            }
            if ($eqLogic->getChanged()) {
                $eqLogic->save(true);
            }
        }
    }
    
    // Rename some commands
    foreach (cmd::searchConfiguration('', 'ecodevice') as $cmd) {
        if ($cmd->getLogicalId() == 'debitinstantane') {
            $cmd->setLogicalId('consommationinstantane');
            $cmd->save();
        }
    }
}
    
function ecodevice_update() {
    ecodevice_update_remove_subclasses();
    ecodevice_update_complete_rewrite();
	ecodevice_init();
}

function ecodevice_remove() {
    $daemon = cron::byClassAndFunction('ecodevice', 'daemon');
    if (is_object($daemon)) {
        $daemon->remove();
    }
    $cron = cron::byClassAndFunction('ecodevice', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }
	config::remove('subClass', 'ecodevice');
	config::remove('listChildren', 'ecodevice');
	config::remove('temporisation_lecture', 'ecodevice');
}
