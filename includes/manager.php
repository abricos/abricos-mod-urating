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
	 * Коды ошибок:
	 *   null - ошибка доступа, неверного запроса и еще чего либо непонятного;
	 *   1 - пользователь уже голосовал за этот элемент;
	 *   2 - модуль не разрешил ставить голос за элемент (см. код ошибки в merror);
	 *   0 - все нормально, голос установлен
	 * 
	 * @param object $d
	 * @return object
	 */
	public function ElementVoting($d){
		if (!$this->IsWriteRole()){ return null; }
		if (!($d->vote == 'up' || $d->vote == 'down' || $d->vote == 'refrain')){
			return null;
		}

		$module = Abricos::GetModule($d->module);
		if (empty($module)){ return null; }
		$manager = $module->GetManager();
		if (!method_exists($manager, 'URating_IsElementVoting')){
			return null;
		}
		$ret = new stdClass();
		$ret->error = 0;
		
		// Может этот пользователь уже ставил голос на этот элемент?
		$dbUVote = URatingQuery::ElementVoteByUser($this->db, $d->module, $d->eltype, $d->elid, $this->userid);
		
		if (!empty($dbUVote)){ // уже поставлен голос за этот элемент
			$ret->error = 1;
			return $ret;
		}
		
		// Можно ли ставить голос текущему пользователю за этот элемент
		// Нужно спросить сам модуль
		$uRep = $this->UserReputation($this->userid);
		$ret->merror = $manager->URating_IsElementVoting($uRep, $d->vote, $d->elid, $d->eltype);
		if ($ret->merror > 0){ // модуль не дал разрешение на устновку голоса
			$ret->error = 2;
			return $ret;
		}
		// голосование за элемент разрешено модулем
		$voteup = 0;
		$votedown = 0;
		if ($d->vote == 'up'){
			$voteup = 1;
		}else if ($d->vote == 'down'){
			$votedown = 1;
		}
		
		URatingQuery::ElementVoteAppend($this->db, $d->module, 
				$d->eltype, $d->elid, $this->userid, $voteup, $votedown);
		
		$ret->vote = URatingQuery::ElementVoteCalc($this->db, $d->module, $d->eltype, $d->elid);
		
		if (method_exists($manager, 'URating_OnElementVoting')){
			$manager->URating_OnElementVoting($d->eltype, $d->elid, $ret->vote);
		}
		
		return $ret;
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
	
	private $_voteCountCache = null;
	
	/**
	 * Количество голосов по элементам модулей в текущих сутках 
	 */
	public function UserVoteCountByDay(){
		if (!is_null($this->_voteCountCache)){
			return $this->_voteCountCache;
		}
		$rows = URatingQuery::ElementVoteCountDayByUser($this->db, $this->userid);
		
		$this->_voteCountCache = $this->ToArrayId($rows);
		
		return $this->_voteCountCache;
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
		
		// зарегистрировать все модули
		Abricos::$instance->modules->RegisterAllModule();
		$modules = Abricos::$instance->modules->GetModules();
		
		// опросить каждый модуль на наличие метода запроса SQL по форме
		$sqls = array();
		foreach ($modules as $name => $module){
			if (!method_exists($module, 'URating_SQLCheckCalculate')){
				continue;
			}
			
			$sql = $module->URating_SQLCheckCalculate();
			array_push($sqls, "(".$sql.")");
		}
		
		// объеденить все SQL запросы модулей в один запрос и получить список 
		// пользователей нуждающихся в пересчете данных рейтинга 
		$uids = array();
		$rows = URatingQuery::CalculateUserList($this->db, $sqls);
		while (($row = $this->db->fetch_array($rows))){
			$uids[$row['uid']] = $row['uid'];
			
			// пользователь и модуль определен - запрос пересчета
			$this->UserCalculateByModule($row['m'], $row['uid']);
		}
		$nuids = array();
		foreach($uids as $uid){
			array_push($nuids, $uid);
		}
		$this->UserCalcualte($nuids);
	}
	
	/**
	 * Пересчет рейтинг пользователя по данным модуля
	 */
	public function UserCalculateByModule($modname, $userid){
		$module = Abricos::GetModule($modname);
		if (empty($module)){ return; }
		
		$manager = $module->GetManager();
		if (empty($manager) || !method_exists($manager, 'URating_UserCalculate')){
			return;
		}
		
		// посчитать рейтинг пользователя
		$d = $manager->URating_UserCalculate($userid);
		
		// обновить данные
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
	
	/**
	 * Можно ли проголосовать текущему пользователю за репутацию пользователя
	 *
	 * Метод вызывается из модуля URating
	 *
	 * Возвращает код ошибки:
	 *  0 - все нормально, голосовать можно;
	 *  1 - нельзя голосовать за самого себя;
	 *  2 - голосовать можно только с положительным рейтингом;
	 *  3 - недостаточно голосов (закончились голоса)
	 *
	 *
	 * @param URatingUserReputation $uRep
	 * @param string $vote
	 * @param integer $userid
	 * @param string $eltype
	 */
	public function URating_IsElementVoting(URatingUserReputation $uRep, $vote, $userid, $eltype){
		if ($userid == $this->userid){ // нельзя голосовать за самого себя
			return 1;
		}
		if ($this->IsAdminRole()){ // админу можно голосовать всегда
			return 0;
		}
	
		if ($uRep->reputation < 1){ // голосовать можно только с положительным рейтингом
			return 2;
		}
	
		$votes = $this->UserVoteCountByDay();
	
		// голосов за репутацию равно кол-ву голосов самой репутации
		$voteRepCount = $votes['urating'];
		if ($uRep->reputation > $voteRepCount){
			return 3;
		}
	
		return 0;
	}
	
	/**
	 * Занести результат расчета репутации пользователя
	 *
	 * Метод вызывается из модуля urating
	 *
	 * @param string $eltype
	 * @param integer $elid
	 * @param array $vote
	 */
	public function URating_OnElementVoting($eltype, $userid, $vote){
	
		// занести результат расчета репутации пользователя
		URatingQuery::UserReputationUpdate($this->db, $userid, $vote['cnt'], $vote['up'], $vote['down']);
	}
	
	/**
	 * Расчет рейтинга пользователя
	 *
	 * Метод запрашивает модуль URating
	 *
	 * +10 - за каждый положительный голос в репутацию
	 * -10 - за каждый отрицательный голос в репутацию
	 *
	 * @param integer $userid
	 */
	public function URating_UserCalculate($userid){
		$rep = $this->UserReputation($userid);
	
		$ret = new stdClass();
		$ret->skill = $rep->reputation;
		return $ret;
	}
		
}

?>