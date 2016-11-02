<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class URatingBuilder
 */
class URatingBuilder {

    public $idPrefix = "";

    public $modName;
    public $elType;
    public $errorLang = '';

    private $_list = array();

    public function __construct($modName, $elType, $errorLang = ''){
        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');

        $this->idPrefix = $uratingApp->GenId();

        $this->modName = $modName;
        $this->elType = $elType;
        $this->errorLang = $errorLang;
    }

    public function BuildVote($cfg){
        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');

        $cfg = $uratingApp->ParamToObject($cfg);

        $vote = new URatingVote($cfg->elid, $cfg->value, $cfg->vote);
        $vote->jsid = $this->idPrefix.$cfg->elid;

        array_push($this->_list, $vote);

        $brick = Brick::$builder->LoadBrickS('urating', 'vote', null, null);
        $v = &$brick->param->var;

        $s = Brick::ReplaceVarByData($brick->content, array(
            'bup' => $v['bup'],
            'bval' => Brick::ReplaceVarByData($v['bval'], array(
                "val" => is_null($cfg->value) ? "—" : $cfg->value
            )),
            'bdown' => $v['bdown'],
            'module' => $this->modName,
            'type' => $this->elType,
            'nodeid' => $vote->jsid,
            'id' => $cfg->elid,
            'value' => $cfg->value,
            'vote' => $cfg->vote
        ));

        return $s;
    }

    public function BuildJSMan(){
        return "";
    }
}

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
 * @property int $up
 * @property int $down
 * @property int $dateline
 */
class URatingVote extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Vote';

    public function IsEmpty(){
        return $this->dateline === 0;
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
 * @property int $voting
 * @property int $up
 * @property int $down
 * @property int $voteAmount
 * @property int $upddate
 */
class URatingVoting extends AbricosModel {
    protected $_structModule = 'urating';
    protected $_structName = 'Voting';
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
