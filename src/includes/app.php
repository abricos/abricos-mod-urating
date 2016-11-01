<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class URatingApp
 */
class URatingApp extends AbricosApplication {

    protected function GetClasses(){
        return array();
    }

    protected function GetStructures(){
        return '';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'elementvoting':
                return $this->ElementVoting($d);
        }
        return null;
    }

    public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
        $ret = array();
        while (($row = $this->db->fetch_array($rows))){
            array_push($ret, $row);
            if (is_array($ids1)){
                $ids1[$row[$fnids1]] = $row[$fnids1];
            }
            if (is_array($ids2)){
                $ids2[$row[$fnids2]] = $row[$fnids2];
            }
            if (is_array($ids3)){
                $ids3[$row[$fnids3]] = $row[$fnids3];
            }
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

    public function ParamToObject($o){
        if (is_array($o)){
            $ret = new stdClass();
            foreach ($o as $key => $value){
                $ret->$key = $value;
            }
            return $ret;
        } else if (!is_object($o)){
            return new stdClass();
        }
        return $o;
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
        if (!$this->IsWriteRole()){
            return null;
        }
        if (!($d->act == 'up' || $d->act == 'down' || $d->act == 'refrain')){
            return null;
        }

        $module = Abricos::GetModule($d->module);
        if (empty($module)){
            return null;
        }
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
        $ret->merror = $manager->URating_IsElementVoting($uRep, $d->act, $d->elid, $d->eltype);
        if ($ret->merror > 0){ // модуль не дал разрешение на устновку голоса
            $ret->error = 2;
            return $ret;
        }
        // голосование за элемент разрешено модулем
        $voteup = 0;
        $votedown = 0;
        if ($d->act == 'up'){
            $voteup = 1;
        } else if ($d->act == 'down'){
            $votedown = 1;
        }

        URatingQuery::ElementVoteAppend($this->db, $d->module,
            $d->eltype, $d->elid, $this->userid, $voteup, $votedown);

        $ret->info = URatingQuery::ElementVoteCalc($this->db, $d->module, $d->eltype, $d->elid);

        if (method_exists($manager, 'URating_OnElementVoting')){
            $manager->URating_OnElementVoting($d->eltype, $d->elid, $ret->info);
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
        if (!$this->IsViewRole()){
            return null;
        }
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
            $this->UserRatingClear($this->userid);
        }

        // зарегистрировать все модули
        $modules = Abricos::$modules->RegisterAllModule();

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
        foreach ($uids as $uid){
            array_push($nuids, $uid);
        }
        $this->UserCalcualte($nuids);
    }

    /**
     * Пересчет рейтинг пользователя по данным модуля
     */
    public function UserCalculateByModule($modname, $userid){
        $module = Abricos::GetModule($modname);
        if (empty($module)){
            return;
        }

        $manager = $module->GetManager();
        if (empty($manager) || !method_exists($manager, 'URating_UserCalculate')){
            return;
        }

        // посчитать рейтинг пользователя
        $d = $manager->URating_UserCalculate($userid);

        // обновить данные
        URatingQuery::UserRatingModuleUpdate($this->db, $userid, $modname, $d->skill);
    }

    public function UserCalcualte($usersid){
        if (!is_array($usersid)){
            $usersid = array($usersid);
        }

        $rows = URatingQuery::UserRatingCalculateList($this->db, $usersid);
        while (($row = $this->db->fetch_array($rows))){
            URatingQuery::UserRatingUpdate($this->db, $row['id'], $row['skill']);
        }
    }

    /**
     * Обнуление рейтинга для пересчета
     */
    public function UserRatingClear($userid){
        URatingQuery::UserRatingClear($this->db, $userid);
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
     * @param string $act
     * @param integer $userid
     * @param string $eltype
     */
    public function URating_IsElementVoting(URatingUserReputation $uRep, $act, $userid, $eltype){
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
        $voteRepCount = intval($votes['urating']);
        if ($uRep->reputation <= $voteRepCount){
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
    public function URating_OnElementVoting($eltype, $userid, $info){
        // занести результат расчета репутации пользователя
        URatingQuery::UserReputationUpdate($this->db, $userid, $info['cnt'], $info['up'], $info['down']);

        // обнулить расчеты рейтинга для пересчета
        $this->UserRatingClear($userid);
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
        $ret->skill = $rep->reputation * 10;
        return $ret;
    }

    private $_idCounter = 1;

    public function GenId(){
        return "vote".($this->_idCounter++).'-';
    }
}
