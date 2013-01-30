<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author  Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current; 
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
	Abricos::GetModule('urating')->permission->Install();
	
	// таблица результатов рассчета
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_user (
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			
			`reputation` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя',
			`voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА пользователя',
			`votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ пользователя',
			`votecount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во голосов за репутацию',
			`votedate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета репутации',
			
			`skill` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Сила (рейтинг)',
			
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			UNIQUE KEY `user` (`userid`),
			KEY (`reputation`),
			KEY (`skill`)
		)".$charset
	);
	
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_modcalc (
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			`module` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			
			`skill` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Сила',
			
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			UNIQUE KEY `modcalc` (`userid`, `module`)
		)".$charset
	);
}

if ($updateManager->isUpdate('0.1.1')){
	
	// голосование за определенный элемент модуля
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_vote (
			`module` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			`elementtype` varchar(50) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			`elementid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Проголосовавший пользователь',
				
			`voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ЗА (возможно кол-во)',
			`votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ПРОТИВ (возможно кол-во)',

			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата голоса',
			
			UNIQUE KEY `modvote` (`module`,`elementtype`,`elementid`,`userid`),
			KEY `userid` (`userid`),
			KEY `element` (`module`,`elementtype`,`elementid`)
		)".$charset
	);
	
	// результат голосований
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_votecalc (
			`module` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			`elementtype` varchar(50) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			`elementid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
	
			`votecount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество всего голосов',
			`voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество ЗА',
			`votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество ПРОТИВ',
			
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
				
			UNIQUE KEY `modvote` (`module`,`elementtype`,`elementid`)
		)".$charset
	);
	
}

if ($updateManager->isUpdate('0.1.1') && !$updateManager->isInstall()){
	$db->query_write("
		ALTER TABLE ".$pfx."urating_user
			ADD `reputation` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя',
			ADD `voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА пользователя',
			ADD `votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ пользователя',
			ADD `votecount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во голосов за репутацию',
			ADD `votedate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета репутации',
			ADD KEY (`reputation`),
			ADD KEY (`skill`)
	");
}

?>