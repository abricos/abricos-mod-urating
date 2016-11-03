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

            skill int(10) NOT NULL DEFAULT 0 COMMENT '',
			skillDate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',

			UNIQUE KEY userid (userid),
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
			
			skill int(7) NOT NULL DEFAULT 0 COMMENT '',
			skillDate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			
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
            (userid, ownerModule, skill, skillDate)
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
            (userid, skill, skillDate)
            SELECT
                userid, skill, upddate
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
    // за пользователя будет ownerModule='uprofile', ownerType='user', ownerid='{userid}'
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_vote (
            voteid int(10) unsigned NOT NULL auto_increment,
            
			ownerModule varchar(32) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			ownerType varchar(16) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			ownerid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
			
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Проголосовавший пользователь',
			
			vote int(5) NOT NULL DEFAULT 0 COMMENT 'Голос (возможно кол-во)',
			voteDate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата голоса',
			
			PRIMARY KEY (voteid),
			UNIQUE KEY voteUser (ownerModule, ownerType, ownerid, userid),
			KEY voteOwner (ownerModule, ownerType, ownerid),
			KEY userid (userid),
			KEY voteDate (voteDate)
		)".$charset
    );

    if (!$updateManager->isInstall()){
        $db->query_write("
            INSERT INTO ".$pfx."urating_vote
            (ownerModule, ownerType, ownerid, userid, vote, voteDate)
            SELECT
                `module`, elementtype, elementid, userid, 
                voteup+votedown*(-1), dateline
            FROM ".$pfx."urating_voteold
        ");
        $db->query_write("DROP TABLE ".$pfx."urating_voteold");

        $db->query_write("
            UPDATE ".$pfx."urating_vote
            SET ownerModule='uprofile'
            WHERE ownerModule='urating'
        ");
    }

    // результат голосования за объект в модуле
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_voting (
            votingid int(10) unsigned NOT NULL auto_increment,

			ownerModule varchar(32) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			ownerType varchar(16) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			ownerid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор элемента',
			
            voteCount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Всего голосов',
			voteUpCount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Всего голосов ЗА',
			voteAbstainCount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Всего воздержалось',
			voteDownCount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Всего голосов ПРОТИВ',

			voting int(10) NOT NULL DEFAULT 0 COMMENT 'Результат',
			votingUp int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Результат ЗА',
			votingDown int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Результат ПРОТИВ',
			votingDate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',

			PRIMARY KEY (votingid),
			UNIQUE KEY voting (ownerModule, ownerType, ownerid)
		)".$charset
    );

    if (!$updateManager->isInstall()){
        $db->query_write("DROP TABLE ".$pfx."urating_votecalc");

        $db->query_write("
            INSERT INTO ".$db->prefix."urating_voting (
                ownerModule, ownerType, ownerid, 
                voteCount, voteUpCount, voteAbstainCount, voteDownCount,
                voting, votingUp, votingDown, votingDate
            ) 
            SELECT
                ownerModule, ownerType, ownerid,
                COUNT(*) as voteCount,
                SUM(IF(vote>0,1,0)) AS voteUpCount,
                SUM(IF(vote=0,1,0)) AS voteAbstainCount,
                SUM(IF(vote<0,1,0)) AS voteDownCount,
                
                SUM(vote) as voting,
                SUM(IF(vote>0,vote,0)) AS votingUp,
                SUM(IF(vote<0,vote,0)) AS votingDown,
                MAX(voteDate) as votingDate
            FROM ".$db->prefix."urating_vote v
			GROUP BY ownerModule, ownerType, ownerid
        ");
    }

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."urating_ownerConfig (
            configid int(10) unsigned NOT NULL auto_increment,

			ownerModule varchar(32) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
			ownerType varchar(16) NOT NULL DEFAULT '' COMMENT 'Тип элемента в модуле',
			
			votingPeriod int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Срок голосования в секундах',

			PRIMARY KEY (configid),
			UNIQUE KEY config (ownerModule, ownerType)
		)".$charset
    );
}
