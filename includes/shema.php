<?php
/**
 * @version $Id: shema.php 985 2012-10-10 11:20:10Z roosit $
 * @package Abricos
 * @subpackage Jofolio
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
			
			`skill` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Сила',
			
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			UNIQUE KEY `user` (`userid`)
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
	
	/*	
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."ugr_user (
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			
			`urvalue` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Результат отношения к пользователю',
			`urcount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во голосов',
			`urpiston` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во пистонов для голосования',
			
			UNIQUE KEY `user` (`userid`)
		)".$charset
	);

	// отношение пользователя к пользователю +1 0 -1
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urg_userrelation (
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Тот, кому ставят оценку',
			`fromuserid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Тот, кто ее ставит',
			`rating` int(1) NOT NULL DEFAULT 0 COMMENT '-1 0 +1',
				
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Создания',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Удаления',
			`updatedate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Обновления',
	
			KEY `userid` (`userid`),
			UNIQUE KEY `relation` (`userid`,`fromuserid`)
		)".$charset
	);
	/**/
}

?>