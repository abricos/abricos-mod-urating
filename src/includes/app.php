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
 *
 * @property URatingManager $manager
 */
class URatingApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Reputation' => 'URatingReputation',
            'ReputationList' => 'URatingReputationList',
            'Skill' => 'URatingSkill',
            'SkillList' => 'URatingSkillList',
            'Vote' => 'URatingVote',
            'VoteList' => 'URatingVoteList',
            'Voting' => 'URatingVoting',
            'VotingList' => 'URatingVotingList',
            'ToVote' => 'URatingToVote',
            'Owner' => 'URatingOwner',
            'Config' => 'URatingConfig',
            'OwnerConfig' => 'URatingOwnerConfig',
            'OwnerConfigList' => 'URatingOwnerConfigList',
        );
    }

    protected function GetStructures(){
        return 'Vote,Voting,Config,OwnerConfig';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'toVote':
                return $this->ToVoteToJSON($d->data);
            case 'config':
                return $this->ConfigToJSON();
            case 'configSave':
                return $this->ConfigSaveToJSON($d->data);
        }
        return null;
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsWriteRole(){
        return $this->manager->IsWriteRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }

    private function OwnerAppFunctionExist($module, $fn){
        $ownerApp = Abricos::GetApp($module);
        if (empty($ownerApp)){
            return false;
        }
        if (!method_exists($ownerApp, $fn)){
            return false;
        }
        return true;
    }

    /**
     * @param $module
     * @param $type
     * @param $ownerid
     *
     * @return URatingOwner
     */
    public function Owner($module, $type, $ownerid){
        if ($module instanceof URatingOwner){
            return $module;
        }

        return $this->InstanceClass('Owner', array(
            'module' => $module,
            'type' => $type,
            'ownerid' => $ownerid
        ));
    }

    /**
     * @return URatingOwnerConfigList
     */
    public function OwnerConfigList(){
        if ($this->CacheExists('OwnerConfig')){
            return $this->Cache('OwnerConfig');
        }

        /** @var URatingOwnerConfigList $list */
        $list = $this->InstanceClass('OwnerConfigList');
        $rows = URatingQuery::OwnerConfigList($this->db);

        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('OwnerConfig', $d));
        }

        if ($this->IsAdminRole()){
            $modules = Abricos::$modules->RegisterAllModule();
            foreach ($modules as $moduleName => $module){
                if (!method_exists($module, 'URating_IsVoting') || !$module->URating_IsVoting()){
                    continue;
                }

                $manager = $module->GetManager();
                if (!method_exists($manager, 'URating_GetTypes')){
                    continue;
                }
                $types = explode(",", $manager->URating_GetTypes());
                foreach ($types as $type){
                    $config = $list->GetByOwner($moduleName, $type);
                    if ($config->id === 0){
                        $this->OwnerConfigSetDefault($config);
                        $list->Add($config);
                    }
                }
            }
        }


        $this->SetCache('OwnerConfig', $list);

        return $list;
    }

    private function OwnerConfigSetDefault(URatingOwnerConfig $config){
        $manager = Abricos::GetModuleManager($config->module);
        if (method_exists($manager, 'URating_GetDefaultConfig')){
            $def = $manager->URating_GetDefaultConfig($config->type);

            if (isset($def['votingPeriod'])){
                $config->votingPeriod = intval($def['votingPeriod']);
            }
        }

        $configid = URatingQuery::OwnerConfigSave($this->db, $config);
        if ($config->id === 0){
            $config->id = $configid;
        }
        return $config;
    }

    /**
     * @param URatingOwner|string $module
     * @param string $type (optional)
     * @return URatingOwnerConfig
     */
    public function OwnerConfig($module, $type = ''){
        $list = $this->OwnerConfigList();
        $config = $list->GetByOwner($module, $type);

        if ($config->id > 0){
            return $config;
        }

        $this->OwnerConfigSetDefault($config);
        $list->Add($config);

        return $config;
    }

    public function ConfigToJSON(){
        $ret = $this->Config();
        return $this->ResultToJSON('config', $ret);
    }

    /**
     * @return URatingConfig
     */
    public function Config(){
        if ($this->CacheExists('Config')){
            return $this->Cache('Config');
        }

        /** @var URatingConfig $config */
        $config = $this->InstanceClass('Config');

        $config->ownerList = $this->OwnerConfigList();

        $this->SetCache('Config', $config);

        return $config;
    }

    public function ConfigSaveToJSON($d){
        $ret = $this->ConfigSave($d);
        return $this->ResultToJSON('configSave', $ret);
    }

    public function ConfigSave($d){
        if (!$this->IsAdminRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $config = $this->Config();
        for ($i = 0; $i < count($d->owners); $i++){
            $di = $d->owners[$i];
            $ownerConfig = $config->ownerList->Get($di->ownerid);
            $ownerConfig->votingPeriod = intval($di->votingPeriod);

            URatingQuery::OwnerConfigSave($this->db, $ownerConfig);
        }
        return $this->Config();
    }

    /**
     * @return URatingReputation
     */
    public function Reputation(){
        $userid = Abricos::$user->id;

        if ($this->CacheExists('Rep', $userid)){
            return $this->Cache('Rep', $userid);
        }

        $d = URatingQuery::Reputation($this->db);

        /** @var URatingReputation $ret */
        $ret = $this->InstanceClass('Reputation', $d);

        $this->SetCache('Rep', $userid, $ret);

        return $ret;
    }

    /**
     * @param URatingOwner $owner
     * @return URatingVote
     */
    public function Vote(URatingOwner $owner){
        $d = URatingQuery::Vote($this->db, $owner);

        return $this->InstanceClass('Vote', $d);
    }

    /**
     * @param URatingOwner $owner
     * @return URatingVoting
     */
    /**
     * @param URatingOwner|string $module
     * @param string $type (optional)
     * @param int $ownerid (optional)
     * @return URatingVoting
     */
    public function Voting($module, $type = '', $ownerid = 0){
        $owner = $this->Owner($module, $type, $ownerid);

        $d = URatingQuery::Voting($this->db, $owner);

        /** @var URatingVoting $voting */
        $voting = $this->InstanceClass('Voting', $d);
        if ($voting->id === 0){
            $voting->module = $owner->module;
            $voting->type = $owner->type;
            $voting->ownerid = $owner->ownerid;
        }

        $voting->vote = $this->InstanceClass('Vote', $d);
        $voting->config = $this->OwnerConfig($owner->module, $owner->type);

        return $voting;
    }

    /**
     * @param string $module
     * @param string $type
     * @param array(int) $ownerids
     * @return URatingVotingList
     */
    public function VotingList($module, $type, $ownerids){
        $rows = URatingQuery::VotingList($this->db, $module, $type, $ownerids);

        /** @var URatingVotingList $list */
        $list = $this->InstanceClass('VotingList');
        while (($d = $this->db->fetch_array($rows))){
            /** @var URatingVoting $voting */
            $voting = $this->InstanceClass('Voting', $d);

            $voting->vote = $this->InstanceClass('Vote', $d);
            $voting->config = $this->OwnerConfig($module, $type);

            $list->Add($voting);
        }
        $count = count($ownerids);
        for ($i = 0; $i < $count; $i++){
            $ownerid = intval($ownerids[$i]);

            $voting = $list->GetByOwnerId($ownerid);
            if (!empty($voting)){
                continue;
            }
            /** @var URatingVoting $voting */
            $voting = $this->InstanceClass('Voting', array(
                "module" => $module,
                "type" => $type,
                "ownerid" => $ownerid
            ));

            $voting->vote = $this->InstanceClass('Vote', $d);
            $voting->config = $this->OwnerConfig($module, $type);
            $list->Add($voting);
        }

        return $list;
    }

    public function ToVoteToJSON($d){
        $res = $this->ToVote($d);
        return $this->ResultToJSON('toVote', $res);
    }

    public function ToVote($d){
        if (!$this->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var URatingToVote $ret */
        $ret = $this->InstanceClass('ToVote', $d);
        $vars = $ret->vars;

        switch ($vars->action){
            case 'up':
                $ret->up = 1;
                break;
            case 'down':
                $ret->down = 1;
                break;
            case 'refrain':
                break;
            default :
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
        }

        if (!$this->OwnerAppFunctionExist($vars->module, 'URating_IsVoting')){
            return $ret->SetError(AbricosResponse::ERR_SERVER_ERROR);
        }

        $owner = $this->Owner($vars->module, $vars->type, $vars->ownerid);
        $vote = $this->Vote($owner);

        if (!$vote->IsEmpty()){ // уже поставлен голос за этот элемент
            $ret->AddCode(URatingToVote::CODE_JUST_ONE_TIME);
            return $ret;
        }

        $rep = $this->Reputation();

        // Можно ли ставить голос текущему пользователю за этот элемент
        // Нужно спросить сам модуль

        $ownerApp = Abricos::GetApp($vars->module);
        if (!$ownerApp->URating_IsVoting($owner, $rep)){
            return $ret->SetError(AbricosResponse::ERR_FORBIDDEN, URatingToVote::CODE_EXTEND_ERROR);
        }
        // голосование за элемент разрешено модулем

        URatingQuery::VoteAppend($this->db, $ret);

        $ret->AddCode(URatingToVote::CODE_OK);

        $ret->vote = $this->Vote($owner);
        $ret->voting = $this->Voting($owner);

        return $ret;
    }

    public function VotingHTML(URatingVoting $voting){
        if ($this->CacheExists('brick', 'vote')){
            $brick = $this->Cache('brick', 'vote');
        } else {
            $brick = Brick::$builder->LoadBrickS('urating', 'vote');
            $this->SetCache('brick', 'vote', $brick);
        }

        $v = &$brick->param->var;
        $vote = $voting->vote;

        $replace = array(
            'status' => 'ro',
            'module' => $voting->module,
            'type' => $voting->type,
            'nodeid' => $brick->id.'-'.$voting->ownerid,
            'id' => $voting->ownerid,
            'bup' => $v['guestUp'],
            'bval' => $v['guestVal'],
            'bdown' => $v['guestDown'],
        );

        if ($voting->IsFinished()){
            $sVoting = $voting->voting > 0 ? '+' : ($voting->voting < 0) ? '-' : '';
            $sVoting .= $voting->voting;

            $replace['bval'] = Brick::ReplaceVarByData($v['scoreVal'], array(
                "voting" => $sVoting,
                "voteCount" => $voting->voteCount,
                "voteUpCount" => $voting->voteUpCount,
                "voteDownCount" => $voting->voteDownCount,
            ));
        } else if (Abricos::$user->id > 0){
            $replace['status'] = 'w';
            $replace['bup'] = $v['up'];
            $replace['bdown'] = $v['down'];

            $replace['bval'] = Brick::ReplaceVarByData($v['val'], array(
                "voteCount" => $voting->voteCount
            ));
        }

        return Brick::ReplaceVarByData($brick->content, $replace);
    }

    ////////////////////////////////////////////////////////////


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
}
