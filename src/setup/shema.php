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
}

if ($updateManager->isUpdate('0.2.0')){

    // Репутация пользователя - кол-во колосов отданных другими пользователями за него
    // Сила - результат активности пользователя на сайте

    // рейтинг пользователя
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating (
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			
			voting int(10) NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя (голоса `за` и `против`)',
			votingDate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			
			skill int(10) NOT NULL DEFAULT 0 COMMENT 'Рейтинг (сила)',
			skillDate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			
			up int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА пользователя',
			down int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ пользователя',
			amount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во голосов за репутацию',
			votedate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета репутации',
			
			UNIQUE KEY userid (userid),
			KEY (voting),
			KEY (skill)
		)".$charset
    );

    // сила пользователя в модуле
    // например: uprofile (добавил фотку +10, о себе +10 и т.п.)
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_skill (
            skillid int(10) unsigned NOT NULL auto_increment,
            
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			ownerModule varchar(32) NOT NULL DEFAULT '' COMMENT '',
			
			skill int(7) NOT NULL DEFAULT 0 COMMENT 'Рейтинг (сила)',
			
			upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			
			PRIMARY KEY (skillid),
			UNIQUE KEY skill (userid, ownerModule)
		)".$charset
    );

    if (!$updateManager->isInstall()){
        /* старая таблица
        $db->query_write("
            CREATE TABLE IF NOT EXISTS ".$pfx."urating_modcalc (
                `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
                `module` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
                `skill` int(7) NOT NULL DEFAULT 0 COMMENT 'Рейтинг (сила)',
                `upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
                UNIQUE KEY `modcalc` (`userid`, `module`)
            )".$charset
        );/**/

        $db->query_write("
            INSERT INTO ".$pfx."urating_skill
            (userid, ownerModule, skill, upddate)
            SELECT
                userid, `module`, skill, upddate
            FROM ".$pfx."urating_modcalc
        ");
        $db->query_write("DROP TABLE ".$pfx."urating_modcalc");

        /* старая таблица
        $db->query_write("
            CREATE TABLE IF NOT EXISTS ".$pfx."urating_user (
                `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
                `reputation` int(10) NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя',
                `voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА пользователя',
                `votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ пользователя',
                `votecount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во голосов за репутацию',
                `votedate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета репутации',
                `skill` int(10) NOT NULL DEFAULT 0 COMMENT 'Рейтинг (сила)',
                `upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
                UNIQUE KEY `user` (`userid`), KEY (`reputation`), KEY (`skill`)
            )".$charset
        );/**/
        $db->query_write("
            INSERT INTO ".$pfx."urating
            (userid, voting, skill, up, down, amount, votedate, upddate)
            SELECT
                userid, reputation, skill, voteup, votedown, votecount, votedate, upddate
            FROM ".$pfx."urating_user
        ");
        $db->query_write("DROP TABLE ".$pfx."urating_user");

        /* старая таблица
        $db->query_write("
            CREATE TABLE IF NOT EXISTS ".$pfx."urating_vote (
                `module` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
                `elementtype` varchar(50) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
                `elementid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
                `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Проголосовавший пользователь',
                `voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ЗА (возможно кол-во)',
                `votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ПРОТИВ (возможно кол-во)',
                `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата голоса',
                UNIQUE KEY `modvote` (`module`,`elementtype`,`elementid`,`userid`), KEY `userid` (`userid`), KEY `element` (`module`,`elementtype`,`elementid`)
            )".$charset
        );/**/
        $db->query_write("RENAME TABLE ".$pfx."urating_vote TO ".$pfx."urating_voteold");
    }

    // голоса за объект в модуле
    // за пользователя будет ownerModule='urating', ownerType='user', ownerid='{userid}'
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_vote (
            voteid int(10) unsigned NOT NULL auto_increment,
            
			ownerModule varchar(32) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			ownerType varchar(16) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			ownerid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
			
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Проголосовавший пользователь',
				
			up int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ЗА (возможно кол-во)',
			down int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ПРОТИВ (возможно кол-во)',

			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата голоса',
			
			PRIMARY KEY (voteid),
			UNIQUE KEY voteUser (ownerModule, ownertype, ownerid, userid),
			KEY voteOwner (ownerModule, ownertype, ownerid),
			KEY userid (userid)
		)".$charset
    );

    if (!$updateManager->isInstall()){
        $db->query_write("
            INSERT INTO ".$pfx."urating_vote
            (ownerModule, ownerType, ownerid, userid, up, down, dateline)
            SELECT
                `module`, elementtype, elementid, userid, voteup, votedown, dateline
            FROM ".$pfx."urating_voteold
        ");
        $db->query_write("DROP TABLE ".$pfx."urating_voteold");
    }

    // результат голосования за объект в модуле
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_voting (
            votingid int(10) unsigned NOT NULL auto_increment,

			ownerModule varchar(32) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			ownerType varchar(16) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			ownerid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
	
			voting int(10) NOT NULL DEFAULT 0 COMMENT 'Результат',
			up int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество ЗА',
			down int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество ПРОТИВ',
			voteAmount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество всего голосов',
			
			upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',

			PRIMARY KEY (votingid),
			UNIQUE KEY voting (ownerModule, ownerType, ownerid)
		)".$charset
    );

    if (!$updateManager->isInstall()){
        /* старая таблица
        $db->query_write("
            CREATE TABLE IF NOT EXISTS ".$pfx."urating_votecalc (
                `module` varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
                `elementtype` varchar(50) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
                `elementid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
                `voteval` int(10) NOT NULL DEFAULT 0 COMMENT 'Результат',
                `votecount` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество всего голосов',
                `voteup` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество ЗА',
                `votedown` int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество ПРОТИВ',
                `upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
                UNIQUE KEY `modvote` (`module`,`elementtype`,`elementid`),
                KEY `voteval` (`voteval`)
            )".$charset
        );/**/

        $db->query_write("
            INSERT INTO ".$pfx."urating_voting
            (ownerModule, ownerType, ownerid, voting, up, down, voteAmount, upddate)
            SELECT
                `module`, elementtype, elementid, voteval, voteup, votedown, votecount, upddate 
            FROM ".$pfx."urating_votecalc
        ");
        $db->query_write("DROP TABLE ".$pfx."urating_votecalc");
    }
}
