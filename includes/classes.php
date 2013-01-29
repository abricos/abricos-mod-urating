<?php 
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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