<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Модуль расчета пользовательского рейтинга
 *
 * @method URatingManager GetManager()
 */
class URatingModule extends Ab_Module {

    /**
     * Период пересчета в секундах, по умолчанию 5 минут
     */
    const PERIOD_CHECK = 300;

    public function __construct(){
        $this->version = "0.2.0";
        $this->name = "urating";
        $this->permission = new URatingPermission($this);
    }

    public function GetManagerClassName(){
        return 'URatingManager';
    }

    public function Bos_IsMenu(){
        return true;
    }
}


class URatingAction {
    const VIEW = 10;
    const WRITE = 30;
    const ADMIN = 50;
}

class URatingPermission extends Ab_UserPermission {

    public function __construct(URatingModule $module){

        $defRoles = array(
            new Ab_UserRole(URatingAction::VIEW, Ab_UserGroup::GUEST),
            new Ab_UserRole(URatingAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(URatingAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(URatingAction::WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(URatingAction::WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(URatingAction::ADMIN, Ab_UserGroup::ADMIN),
        );
        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            URatingAction::VIEW => $this->CheckAction(URatingAction::VIEW),
            URatingAction::WRITE => $this->CheckAction(URatingAction::WRITE),
            URatingAction::ADMIN => $this->CheckAction(URatingAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new URatingModule());
