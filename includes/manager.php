<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';
require_once 'classes.php';

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
			case 'elementvoting': return $this->ElementVoting($d);
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
	 * Обработать голос пользователя за элемент модуля
	 * 
	 * @param object $d
	 * @return URatingElementVoteResult
	 */
	public function ElementVoting($d){
		if ($this->IsWriteRole()){ return null; }
		if ($d->vote != 'up' || $d->vote != 'down' || $d->vote != 'refrain'){
			return null;
		}
		
		$module = Abricos::GetModule($d->modname);
		if (empty($module)){ return null; }
		$manager = $module->GetManager();
		if (!method_exists($manager, 'URating_IsElementVoting')){
			return null;
		}
		
		// Может этот пользователь уже ставил голос на этот элемент?
		$dbUVote = URatingQuery::ElementVoteByUser($this->db, $d->module, $d->eltype, $d->elid, $this->userid);
		
		if (!empty($dbUVote)){ // уже поставлен голос за этот элемент
			return null;
		}
		
		// Можно ли ставить голос текущему пользователю за этот элемент
		// Нужно спросить сам модуль

		$uRep = $this->UserReputation($this->userid);
		if (!$manager->URating_IsElementVoting($uRep, $d->vote, $d->elid, $d->eltype)){
			return null;
		}
		
		// return $manager->URating_ElementVoting($d->vote, $d->elid, $d->eltype);
	}
	
	private $_repCache = array();
	
	/**
	 * Репутация пользователя
	 * 
	 * @param integer $userid если 0, то текущий пользователь
	 * @return URatingUserReputation
	 */
	public function UserReputation($userid = 0){
		if (!$this->IsViewRole()){ return null; }
		if ($userid == 0){
			$userid = $this->userid;
		}
		if (!empty($this->_repCache[$userid])){
			return $this->_repCache[$userid];
		}
		if ($userid == 0){
			return new URatingUserReputation($userid, array());
		}
		
		$row = URatingQuery::UserReputation($this->db, $userid);
		$this->_repCache[$userid] = new URatingUserReputation($userid, $row);
		
		return $this->_repCache[$userid];
	}
	
	/**
	 * Можно ли проголосовать текущему пользователю за 
	 * репутацию пользователя
	 *
	 * Метод вызывается из модуля urating
	 *
	 * @param URatingUserReputation $uRep
	 * @param string $vote
	 * @param integer $userid
	 * @param string $eltype
	 */
	public function URating_IsElementVoting(URatingUserReputation $uRep, $vote, $userid, $eltype){
		if ($userid == $this->userid){ // нельзя голосовать за самого себя
			return false;
		}
		if ($this->IsAdminRole()){ // админу можно голосовать всегда
			return true;
		}
		
		if ($uRep->reputation < 1){ // голосовать можно только с положительным рейтингом
			return false;
		} 
		
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