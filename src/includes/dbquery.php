<?php
/**
 * @package Abricos
 * @subpackage URating
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class URatingQuery
 */
class URatingQuery {

    public static function Reputation(Ab_Database $db, $userid = 0){
        if (empty($userid)){
            $userid = Abricos::$user->id;
        }

        $sql = "
			SELECT *
			FROM ".$db->prefix."urating
			WHERE userid=".bkint($userid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function Vote(Ab_Database $db, URatingOwner $owner, $userid = 0){
        if (empty($userid)){
            $userid = Abricos::$user->id;
        }
        $sql = "
			SELECT *
			FROM ".$db->prefix."urating_vote
			WHERE ownerModule='".bkstr($owner->module)."' 
				AND ownerType='".bkstr($owner->type)."' 
				AND ownerid=".bkint($owner->ownerid)."
				AND userid=".bkint($userid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function VoteAppend(Ab_Database $db, URatingToVote $toVote){
        $vars = $toVote->vars;

        $sql = "
			INSERT INTO ".$db->prefix."urating_vote
			(ownerModule, ownerType, ownerid, userid, up, down, dateline) VALUES (
				'".bkstr($vars->module)."', 
				'".bkstr($vars->type)."',
				".bkint($vars->ownerid).", 
				".bkint(Abricos::$user->id).",
				".bkint($toVote->up).",
				".bkint($toVote->down).",
				".TIMENOW."
			 )
		";
        $db->query_write($sql);
    }

    public static function Voting(Ab_Database $db, URatingOwner $owner){
        $userid = Abricos::$user->id;

        if ($userid == 0){
            $sql = "
                SELECT r.*
                FROM ".$db->prefix."urating_voting r
                WHERE r.ownerModule='".bkstr($owner->module)."' 
                    AND r.ownerType='".bkstr($owner->type)."' 
                    AND r.ownerid=".bkint($owner->ownerid)."
                LIMIT 1
            ";
            return $db->query_first($sql);
        }

        $sql = "
            SELECT r.*
            FROM ".$db->prefix."urating_voting r
            LEFT JOIN ".$db->prefix."urating_vote v
                ON r.ownerModule=v.ownerModule
                    AND r.ownerType=v.ownerType
                    AND r.ownerid=v.ownerid
                    AND v.userid=".intval($userid)."
            WHERE r.ownerModule='".bkstr($owner->module)."' 
                AND r.ownerType='".bkstr($owner->type)."' 
                AND r.ownerid=".bkint($owner->ownerid)."
            LIMIT 1
        ";
        return $db->query_first($sql);
    }

    public static function VotingList(Ab_Database $db, $module, $type, $ownerids){
        $count = count($ownerids);
        if ($count === 0){
            return null;
        }

        $wha = array();
        for ($i = 0; $i < $count; $i++){
            $wha[] = "r.ownerid=".intval($ownerids[$i]);
        }

        $userid = Abricos::$user->id;

        if ($userid == 0){
            $sql = "
                SELECT r.*
                FROM ".$db->prefix."urating_voting r
                WHERE r.ownerModule='".bkstr($module)."' 
                    AND r.ownerType='".bkstr($type)."'
                    AND (".implode(" OR ", $wha).")
            ";
            return $db->query_read($sql);
        }

        $sql = "
            SELECT r.*
            FROM ".$db->prefix."urating_voting r
            LEFT JOIN ".$db->prefix."urating_vote v
                ON r.ownerModule=v.ownerModule
                    AND r.ownerType=v.ownerType
                    AND r.ownerid=v.ownerid
                    AND v.userid=".intval($userid)."
            WHERE r.ownerModule='".bkstr($module)."' 
                AND r.ownerType='".bkstr($type)."' 
                AND (".implode(" OR ", $wha).")
        ";
        return $db->query_read($sql);
    }

    public static function VotingUpdate(Ab_Database $db, URatingOwner $owner){
        $sql = "
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
            WHERE ownerModule='".bkstr($owner->module)."', 
                AND ownerType='".bkstr($owner->type)."',
                AND ownerid=".bkint($owner->ownerid)."
			GROUP BY ownerModule, ownerType, ownerid
			ON DUPLICATE KEY UPDATE
                voteCount=v.voteCount, 
                voteUpCount=v.voteUpCount, 
                voteAbstainCount=v.voteAbstainCount, 
                voteDownCount=v.voteDownCount,
                voting=v.voting, 
                votingUp=v.votingUp, 
                votingDown=v.votingDown, 
                votingDate=v.votingDate
        ";
        $db->query_write($sql);
    }

    public static function OwnerConfigList(Ab_Database $db){
        $sql = "
            SELECT *
            FROM ".$db->prefix."urating_ownerConfig
            ORDER BY ownerModule, ownerType
        ";
        return $db->query_read($sql);
    }

    public static function OwnerConfigSave(Ab_Database $db, URatingOwnerConfig $config){
        $sql = "
            INSERT INTO ".$db->prefix."urating_ownerConfig
            (ownerModule, ownerType, votingPeriod) VALUES (
                '".bkstr($config->module)."', 
                '".bkstr($config->type)."',
                ".intval($config->votingPeriod)." 
            ) ON DUPLICATE KEY UPDATE
                votingPeriod=".intval($config->votingPeriod)."
        ";
        $db->query_write($sql);
        return $db->insert_id();
    }

    /**
     * Количество используемых голосов за прошедшие сутки
     *
     * @param Ab_Database $db
     * @param unknown_type $userid
     */
    public function ElementVoteCountDayByUser(Ab_Database $db, $userid){
        $day = 60 * 60 * 24;
        $t1 = intval(round(TIMENOW / $day) * $day);
        $sql = "
			SELECT
				module as id,
				count(*) as cnt
			FROM ".$db->prefix."urating_vote
			WHERE userid=".bkint($userid)." AND dateline>".$t1."
			GROUP BY module
		";
        return $db->query_first($sql);
    }

    public static function UserReputationUpdate(Ab_Database $db, $userid, $votecount, $voteup, $votedown){
        $sql = "
			INSERT INTO ".$db->prefix."urating_user
				(userid, reputation, votecount, voteup, votedown, votedate) VALUES (
				".bkint($userid).",
				".bkint($voteup - $votedown).",
				".bkint($votecount).",
				".bkint($voteup).",
				".bkint($votedown).",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				reputation=".bkint($voteup - $votedown).",
				votecount=".bkint($votecount).",
				voteup=".bkint($voteup).",
				votedown=".bkint($votedown).",
				votedate=".TIMENOW."
		";
        $db->query_write($sql);
    }

    public static function CalculateUserList(Ab_Database $db, $sqls){
        $sql = "
			SELECT 
				DISTINCT uu.uid, uu.m
			FROM (
				".implode(" UNION ", $sqls)."
			) uu
			ORDER BY uu.uid
		";
        return $db->query_read($sql);
    }

    public static function UserRatingModuleUpdate(Ab_Database $db, $userid, $module, $skill){
        $sql = "
			INSERT INTO ".$db->prefix."urating_modcalc
			(userid, module, skill, upddate) VALUES (
				".bkint($userid).",
				'".bkstr($module)."',
				".bkint($skill).",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				skill=".bkint($skill).",
				upddate=".TIMENOW."
		";
        $db->query_write($sql);
    }

    public static function UserRatingCalculateList(Ab_Database $db, $userid){
        if (!is_array($userid)){
            $userid = array($userid);
        }
        if (count($userid) == 0){
            return null;
        }
        $awh = array();
        foreach ($userid as $id){
            array_push($awh, "userid=".bkint($id));
        }
        $sql = "
			SELECT 
				userid as id,
				sum(skill) as skill
			FROM ".$db->prefix."urating_modcalc
			WHERE ".implode(" OR ", $awh)."
			GROUP BY userid
		";
        return $db->query_read($sql);
    }

    public static function UserRatingUpdate(Ab_Database $db, $userid, $skill){
        $sql = "
			INSERT INTO ".$db->prefix."urating_user
				(userid, skill, upddate) VALUES (
				".bkint($userid).",
				".bkint($skill).",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				skill=".bkint($skill).",
				upddate=".TIMENOW."
		";
        $db->query_write($sql);
    }

    public static function UserRatingClear(Ab_Database $db, $userid){
        if (!is_array($userid)){
            $userid = array($userid);
        }
        if (count($userid) == 0){
            return null;
        }
        $awh = array();
        foreach ($userid as $id){
            array_push($awh, "userid=".bkint($id));
        }

        $sql = "
			DELETE FROM ".$db->prefix."urating_modcalc
			WHERE ".implode(" OR ", $awh)."
		";
        $db->query_write($sql);
    }
}
