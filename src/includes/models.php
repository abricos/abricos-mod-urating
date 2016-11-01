<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Репутация пользователя
 */
class URatingUserReputation {

    /**
     * Пользователь
     *
     * @var integer
     */
    public $userid;

    /**
     * Репутация
     *
     * @var integer
     */
    public $reputation;

    /**
     * Количество голосов
     *
     * @var integer
     */
    public $voteCount;

    /**
     * Рейтинг
     *
     * @var integer
     */
    public $skill;

    public function __construct($userid, $d){
        $this->id = $userid;
        $this->reputation = intval($d['rep']);
        $this->voteCount = intval($d['vcnt']);
        $this->skill = intval($d['skill']);
    }
}

/**
 * Результат голосования по элементу модуля
 */
class URatingElementVote {

    /**
     * Количество всего голосов
     *
     * @var integer
     */
    public $voteCount = 0;

    /**
     * Количество голосов ЗА
     *
     * @var integer
     */
    public $upCount = 0;

    /**
     * Количество голосов ПРОТИВ
     *
     * @var integer
     */
    public $downCount = 0;

    /**
     * Голос текущего пользователя:
     * null - не голосовал,
     * 1 - ЗА, -1 - ПРОТИВ, 0 - воздержался
     *
     * @var mixed
     */
    public $vote = null;

    public function __construct($d){
    }
}


class URatingVote {

    public $elid;
    public $value;
    public $vote;

    public $jsid = '';

    public function __construct($elid, $value, $vote){
        $this->elid = $elid;
        $this->value = $value;
        $this->vote = $vote;
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->id = $this->elid;
        $ret->vl = $this->value;
        $ret->vt = $this->vote;
        $ret->jsid = $this->jsid;
        return $ret;
    }
}


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
            'jsid' => $vote->jsid
        ));

        return $s;
    }

    public function BuildJSMan(){

        $brick = Brick::$builder->LoadBrickS('urating', 'jsman', null, null);
        $v = &$brick->param->var;

        $arr = array();
        foreach ($this->_list as $vote){
            array_push($arr, $vote->ToAJAX());
        }

        $s = Brick::ReplaceVarByData($brick->content, array(
            "modname" => $this->modName,
            "eltype" => $this->elType,
            "list" => json_encode($arr),
            "errorlang" => $this->errorLang
        ));

        return $s;
    }
}
