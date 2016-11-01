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
        $this->version = "0.1.2";
        $this->name = "urating";
        $this->permission = new URatingPermission($this);
    }

    public function GetManagerClassName(){
        return 'URatingManager';
    }

    /**
     * Этот метод запрашивает модуль URating (т.е. сам у себя, так как этот
     * же модуль отвечает и за репутацию пользователя)
     *
     * В расчете участвуют только те пользователи, которым поставили
     * хотябы один голос за репутацию
     */
    public function URating_SQLCheckCalculate(){
        $db = Abricos::$db;
        return "
			SELECT
				DISTINCT u.userid as uid,
				'".$this->name."' as m
			FROM ".$db->prefix."urating_user u
			LEFT JOIN ".$db->prefix."urating_modcalc mc ON u.userid=mc.userid
				AND mc.module='".bkstr($this->name)."'
			WHERE u.votecount > 0 
				AND ((mc.upddate + ".URatingModule::PERIOD_CHECK." < u.votedate) 
					OR ISNULL(mc.upddate))
			LIMIT 30
		";
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
