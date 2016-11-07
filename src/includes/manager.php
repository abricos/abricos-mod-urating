<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class URatingManager
 */
class URatingManager extends Ab_ModuleManager {

    public function IsAdminRole(){
        return $this->IsRoleEnable(URatingAction::ADMIN);
    }

    public function IsWriteRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(URatingAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(URatingAction::VIEW);
    }

    public function GetAppClassName(){
        return 'URatingApp';
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    public function Bos_MenuData(){
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "urating",
                "title" => $i18n->Translate('title'),
                "role" => NotifyAction::ADMIN,
                "icon" => "/modules/urating/images/logo-96x96.png",
                "url" => "urating/wspace/ws/",
                "parent" => "controlPanel"
            )
        );
    }
}
