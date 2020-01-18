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

class ecodeviceCmd extends cmd {
    
    public static function create($eqLogic, $logicalId, $data) {
        $cmd = new ecodeviceCmd();
        $cmd->setName($data['name']);
        $cmd->setEqLogic_id($eqLogic->getId());
        $cmd->setType($data['type']);
        $cmd->setSubType($data['subtype']);
        $cmd->setLogicalId($logicalId);
        $cmd->setUnite($data['unit']);
        $cmd->setIsVisible($data['isVisible']);
        $cmd->setDisplay('generic_type', $data['generic_type']);
        $cmd->setTemplate('dashboard', $data['template']);
        $cmd->setTemplate('mobile', $data['template']);
        $cmd->save();
        return $cmd;
    }
    
    public function execute($_options = null) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new \Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
        $url = $eqLogic->getUrl();

        if ($this->getLogicalId() == 'reboot') {
            $url .= "protect/settings/reboot.htm";
        } else {
            return false;
        }
        log::add('ecodevice', 'debug', 'get ' . preg_replace("/:[^:]*@/", ":XXXX@", $url) . '?' . http_build_query($data));
        $result = @file_get_contents($url . '?' . http_build_query($data));
        $count  = 0;
        while ($result === false) {
            $result = @file_get_contents($url . '?' . http_build_query($data));
            if ($count < 3) {
                log::add('ecodevice', 'error', __('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName() . " get " . preg_replace("/:[^:]*@/", ":XXXX@", $url) . "?" . http_build_query($data));
                throw new \Exception(__('L\'ecodevice ne repond pas.', __FILE__) . " " . $this->getName());
            }
            $count ++;
        }
        return false;
    }

    public function dontRemoveCmd() {
        return true;
    }
}
