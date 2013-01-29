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
	 * @var integer
	 */
	public $userid; 
	
	/**
	 * Репутация
	 * @var integer
	 */
	public $reputation;
	
	/**
	 * Количество голосов
	 * @var integer
	 */
	public $votecount;
	
	/**
	 * Рейтинг
	 * @var integer
	 */
	public $skill;
	
	public function __construct($userid, $d){
		$this->id 				= $userid; 
		$this->reputation		= intval($d['rep']);
		$this->votecount		= intval($d['vcnt']);
		$this->skill			= intval($d['skill']);
	}
}

/**
 * Результат голосования по элементу модуля
 */
class URatingElementVoteResult {

	/**
	 * Количество всего голосов
	 * @var integer
	 */
	public $coutVote = 0;
	
	/**
	 * Количество голосов ЗА
	 * @var integer
	 */
	public $countUp = 0;
	
	/**
	 * Количество голосов ПРОТИВ
	 * @var integer
	 */
	public $countDown = 0;
	
}

?>