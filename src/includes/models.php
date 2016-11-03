<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class URating
 *
 * @property int $id User ID
 * @property int $voting
 * @property int $votingDate
 * @property int $skill
 * @property int $skillDate
 * @property int $up
 * @property int $down
 * @property int $amount
 */
class URatingReputation extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'URating';
}

/**
 * Class URatingList
 *
 * @method URatingReputation Get(int $userid)
 * @method URatingReputation GetByIndex(int $i)
 */
class URatingReputationList extends AbricosModelList {
}

/**
 * Class URatingSkill
 *
 * @property int $userid
 * @property string $module
 * @property int $skill
 */
class URatingSkill extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Skill';
}

/**
 * Class URatingSkillList
 *
 * @method URatingSkill Get(int $id)
 * @method URatingSkill GetByIndex(int $i)
 */
class URatingSkillList extends AbricosModelList {
}

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
 *
 * @property int $voteCount
 * @property int $voteUpCount
 * @property int $voteAbstainCount
 * @property int $voteDownCount
 *
 * @property int $voting
 * @property int $votingUp
 * @property int $votingDown
 * @property int $votingDate
 *
 * @property URatingVote $vote
 * @property URatingOwnerConfig $config
 */
class URatingVoting extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Voting';

    /**
     * Дата создания (публикации и т.п.) элемента.
     * Необходима для ограничения голосования по времени
     * @var int
     */
    public $ownerDate = 0;
}

/**
 * Class URatingVotingList
 *
 * @method URatingVoting Get(int $id)
 * @method URatingVoting GetByIndex(int $i)
 */
class URatingVotingList extends AbricosModelList {
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
 * @property int $up
 * @property int $down
 * @property URatingVote $vote
 * @property URatingVoting $voting
 */
class URatingToVote extends AbricosResponse {
    const CODE_OK = 1;
    const CODE_JUST_ONE_TIME = 2;
    const CODE_EXTEND_ERROR = 4;

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
 * @property int $votingPeriod Разрешенный период голосования (0 - всегда)
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
