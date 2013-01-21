<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class URatingManager extends Ab_ModuleManager {
	
	/**
	 * @var URatingModule
	 */
	public $module = null;
	
	/**
	 * @var URatingManager
	 */
	public static $instance = null; 
	
	public function __construct(URatingModule $module){
		parent::__construct($module);
		
		URatingManager::$instance = $this;
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(URatingAction::ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(URatingAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(URatingAction::VIEW);
	}
	
	public function AJAX($d){
		switch($d->do){
			// case 'projectlist': return $this->ProjectList($d->userid);
		}
		return null;
	}
	
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){ $ids1[$row[$fnids1]] = $row[$fnids1]; }
			if (is_array($ids2)){ $ids2[$row[$fnids2]] = $row[$fnids2]; }
			if (is_array($ids3)){ $ids3[$row[$fnids3]] = $row[$fnids3]; }
		}
		return $ret;
	}
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
	
	/**
	 * Расчет рейтинга пользователей
	 * 
	 * 1. запросить sql шаблоны запросов для определения пользователей перерасчета
	 * 2. опросить каждый модуль участвующий в рейтинге на получения значений по пользователю
	 * 3. обновить информацию в базе 
	 */
	public function Calculate($isClearCurrent = false){
		
		if ($isClearCurrent){
			$this->UserClear($this->userid);
		}
		
		Abricos::$instance->modules->RegisterAllModule();
		$modules = Abricos::$instance->modules->GetModules();
		
		$sqls = array();
		foreach ($modules as $name => $module){
			if (!method_exists($module, 'URating_SQLCheckCalculate')){
				continue;
			}
			
			$sql = $module->URating_SQLCheckCalculate();
			array_push($sqls, "(".$sql.")");
		}
		
		$uids = array();
		$rows = URatingQuery::CalculateUserList($this->db, $sqls);
		while (($row = $this->db->fetch_array($rows))){
			$uids[$row['uid']] = $row['uid'];
			$this->UserCalculateByModule($row['m'], $row['uid']);
		}
		$nuids = array();
		foreach($uids as $uid){
			array_push($nuids, $uid);
		}
		$this->UserCalcualte($nuids);
	}
	
	/**
	 * Рассчет рейтинга пользователя по модулю
	 */
	public function UserCalculateByModule($modname, $userid){
		$module = Abricos::GetModule($modname);
		
		if (empty($module) || !method_exists($module, 'URating_UserCalculate')){
			return;
		}
			
		$d = $module->URating_UserCalculate($userid);
		URatingQuery::UserSkillModuleUpdate($this->db, $userid, $modname, $d->skill);
	}

	/**
	 * 
	 * @param unknown_type $usersid
	 */
	public function UserCalcualte($usersid){
		if (!is_array($usersid)){
			$usersid = array($usersid);
		}
		
		$rows = URatingQuery::UserSkillCalculateList($this->db, $usersid);
		while (($row = $this->db->fetch_array($rows))){
			URatingQuery::UserSkillUpdate($this->db, $row['id'], $row['skill']);
		}
	}
	
	/**
	 * Обнуление рейтинга для пересчета
	 */
	public function UserClear($userid){
		URatingQuery::UserSkillClear($this->db, $userid);
	}
}

?>