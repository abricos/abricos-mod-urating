<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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

    public function __construct($userid, $d) {
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

    public function __construct($d) {
    }
}

?>