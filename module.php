<?php 
/**
 * @version $Id: module.php 982 2012-10-09 15:32:19Z roosit $
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class URatingModule extends Ab_Module {
	
	/**
	 * Период пересчета в секундах, по умолчанию 5 минут
	 */
	const PERIOD_CHECK = 300;
	
	/**
	 * @var URatingModule
	 */
	public static $instance = null;
	
	public function __construct(){
		$this->version = "0.1";
		$this->name = "urating";
		$this->permission = new URatingPermission($this);
		
		URatingModule::$instance = $this;
	}
	
	/**
	 * Получить менеджер
	 *
	 * @return URatingManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new URatingManager($this);
		}
		return $this->_manager;
	}
	
}


class URatingAction {
	
	const VIEW	= 10;
	
	const WRITE	= 30;
	
	const ADMIN	= 50;
}

class URatingPermission extends Ab_UserPermission {
	
	public function URatingPermission(URatingModule $module){
		
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

?>