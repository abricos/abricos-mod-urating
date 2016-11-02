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

        $sql = "
            INSERT INTO ".$db->prefix."urating_voting
                (ownerModule, ownerType, ownerid, voting, up, down, voteAmount, upddate) 
            SELECT
                ownerModule, ownerType, ownerid, 
                (sum(up)-sum(down)) as voting,
                sum(up) as up,
                sum(down) as down,
                count(*) as voteAmount,
                ".TIMENOW." as upddate
            FROM ".$db->prefix."urating_voting v
            WHERE ownerModule='".bkstr($vars->module)."', 
                AND ownerType='".bkstr($vars->type)."',
                AND ownerid=".bkint($vars->ownerid)."
			GROUP BY ownerModule, ownerType, ownerid
			ON DUPLICATE KEY UPDATE
			    voting=v.voting,
			    up=v.up,
			    down=v.down,
			    voteAmount=v.voteAmount,
			    upddate=".TIMENOW."
		";
        $db->query_write($sql);
    }

    public static function Voting(Ab_Database $db, URatingOwner $owner){
        $sql = "
			SELECT *
			FROM ".$db->prefix."urating_voteing
			WHERE ownerModule='".bkstr($owner->module)."' 
				AND ownerType='".bkstr($owner->type)."' 
				AND ownerid=".bkint($owner->ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
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
