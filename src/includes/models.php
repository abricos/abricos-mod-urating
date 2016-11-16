<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class URatingVote
 *
 * @property string $module
 * @property string $type
 * @property int $ownerid
 * @property int $userid
 * @property int $vote
 * @property int $voteDate
 */
class URatingVote extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Vote';

    public function IsEmpty(){
        return $this->id === 0;
    }
}

/**
 * Class URatingVoteList
 *
 * @method URatingVote Get(int $id)
 * @method URatingVote GetByIndex(int $i)
 */
class URatingVoteList extends AbricosModelList {
}

/**
 * Class URatingVoting
 *
 * @property string $module
 * @property string $type
 * @property int $ownerid
 * @property int $userid Пользователь объекта для голосования
 *
 * @property int $voteCount
 * @property int $voteUpCount
 * @property int $voteAbstainCount
 * @property int $voteDownCount
 *
 * @property int $score
 * @property int $scoreUp
 * @property int $scoreDown
 * @property int $votingDate
 *
 * @property URatingVote $vote
 * @property URatingOwnerConfig $config
 */
class URatingVoting extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Voting';

    /**
     * @deprecated
     */
    private $voting;

    /**
     * Дата создания (публикации и т.п.) элемента.
     * Необходима для ограничения голосования по времени
     *
     * @var int
     */
    public $ownerDate = 0;

    /**
     * Голосование завершено
     *
     * @return bool
     */
    public function IsFinished(){
        $vPeriod = $this->config->votingPeriod;
        if ($vPeriod === 0){
            return false;
        }
        return $this->ownerDate + $vPeriod < TIMENOW;
    }

    /**
     * Разрешено ли голосовать текущему пользователю
     *
     * @return bool
     */
    public function IsVoting(){
        if ($this->IsFinished()
            || !$this->vote->IsEmpty()
        ){
            return false;
        }

        return Abricos::$user->id > 0;
    }

    /**
     * Показать результат
     *
     * @return bool
     */
    public function IsShowResult(){
        if ($this->config->showResult
            || $this->IsFinished()
            || !$this->vote->IsEmpty()
        ){
            return true;
        }

        return $this->userid > 0 && Abricos::$user->id === $this->userid;
    }
}

/**
 * Class URatingVotingList
 *
 * @method URatingVoting Get(int $id)
 * @method URatingVoting GetByIndex(int $i)
 */
class URatingVotingList extends AbricosModelList {

    private $_cacheVotings = array();

    /**
     * @param URatingVoting $item
     */
    public function Add($item){
        if ($item->id > 0){
            parent::Add($item);
        }

        $this->_cacheVotings[$item->ownerid] = $item;
    }

    /**
     * @param int $ownerid
     * @return URatingVoting
     */
    public function GetByOwnerId($ownerid){
        $ownerid = intval($ownerid);
        if (isset($this->_cacheVotings[$ownerid])){
            return $this->_cacheVotings[$ownerid];
        }
        return null;
    }
}

/**
 * Interface URatingToVoteVars
 *
 * @property string $module
 * @property string $type
 * @property int $ownerid
 * @property string $action
 */
interface URatingToVoteVars {
}

/**
 * Class URatingToVote
 *
 * @property URatingToVoteVars $vars
 * @property int $voteValue
 * @property URatingVoting $voting
 */
class URatingToVote extends AbricosResponse {
    const CODE_OK = 1;

    /**
     * Недостаточно репутации для голосования
     */
    const CODE_ENOUGH_REPUTATION = 2;

    /**
     * Голосование завершено
     */
    const CODE_IS_FINISHED = 4;

    /**
     * Нельзя голосовать повторно
     */
    const CODE_JUST_ONE_TIME = 8;

    /**
     * Неизвестная ошибка
     */
    const CODE_UNKNOWN = 16;

    protected $_structModule = 'urating';
    protected $_structName = 'ToVote';
}

/**
 * Class URatingOwner
 *
 * @property string $module
 * @property string $type
 * @property int $ownerid
 */
class URatingOwner extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Owner';
}

/**
 * Class URatingOwnerList
 *
 * @method URatingOwner Get(int $id)
 * @method URatingOwner GetByIndex(int $i)
 */
class URatingOwnerList extends AbricosModelList {
}

/**
 * Class URatingOwnerConfig
 *
 * @property string $module
 * @property string $type
 *
 * @property int $minUserReputation Минимальный рейтинг пользователя для возможности голосовать
 * @property int $votingPeriod Разрешенный период голосования (0 - всегда)
 * @property bool $showResult Показывать результат сразу
 * @property bool $disableVotingUp Запретить голосовать ЗА
 * @property bool $disableVotingAbstain Запретить воздерживаться
 * @property bool $disableVotingDown Запретить голосовать ПРОТИВ
 */
class URatingOwnerConfig extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'OwnerConfig';
}

/**
 * Class URatingOwnerConfigList
 *
 * @method URatingOwnerConfig Get(int $id)
 * @method URatingOwnerConfig GetByIndex(int $i)
 */
class URatingOwnerConfigList extends AbricosModelList {

    private $_cacheConfig = array();

    /**
     * @param URatingOwnerConfig $item
     */
    public function Add($item){
        parent::Add($item);

        if (!isset($this->_cacheConfig[$item->module])){
            $this->_cacheConfig[$item->module] = array();
        }
        $this->_cacheConfig[$item->module]['t'.$item->type] = $item;
    }

    /**
     * @param URatingOwner|string $module
     * @param string $type (optional)
     * @return URatingOwnerConfig
     */
    public function GetByOwner($module, $type = ''){
        if ($module instanceof URatingOwner){
            $type = $module->type;
            $module = $module->module;
        }
        if (empty($module)){
            throw new Exception('Module is empty in URating Owner');
        }
        if (isset($this->_cacheConfig[$module]['t'.$type])){
            return $this->_cacheConfig[$module]['t'.$type];
        }

        $config = $this->app->InstanceClass('OwnerConfig', array(
            'module' => $module,
            'type' => $type
        ));

        $this->_cacheConfig[$module]['t'.$type] = $config;

        return $config;
    }
}

/**
 * Class URatingConfig
 *
 * @property URatingOwnerConfigList $ownerList
 */
class URatingConfig extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Config';
}
