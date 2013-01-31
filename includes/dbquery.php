<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class URatingQuery {
	
	/**
	 * Количество используемых голосов за прошедшие сутки
	 * @param Ab_Database $db
	 * @param unknown_type $userid
	 */
	public function ElementVoteCountDayByUser(Ab_Database $db, $userid){
		$day = 60*60*24;
		$t1 = intval(round(TIMENOW/$day)*$day);
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
	
	public static function ElementVoteByUser(Ab_Database $db, $modname, $eltype, $elid, $userid){
		$sql = "
			SELECT 
				module as m,
				elementtype as tp,
				elementid as elid,
				userid as uid,
				voteup as vup,
				votedown as vdown,
				dateline as dl
			FROM ".$db->prefix."urating_vote
			WHERE module='".bkstr($modname)."' 
				AND elementtype='".bkstr($eltype)."' 
				AND elementid=".bkint($elid)."
				AND userid=".bkint($userid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementVoteAppend(Ab_Database $db, $modname, $eltype, $elid, $userid, $voteup, $votedown){
		// добавление голоса
		$sql = "
			INSERT IGNORE INTO ".$db->prefix."urating_vote
			(module, elementtype, elementid, userid, voteup, votedown, dateline) VALUES
			(
				'".bkstr($modname)."', 
				'".bkstr($eltype)."',
				".bkint($elid).", 
				".bkint($userid).",
				".bkint($voteup).",
				".bkint($votedown).",
				".TIMENOW."
			 )
		";
		$db->query_write($sql);
		
		// подсчет итога
		$sql = "
			SELECT
				count(*) as votecount, 
				sum(voteup) as voteup,
				sum(votedown) as votedown
			FROM ".$db->prefix."urating_vote
			WHERE module='".bkstr($modname)."' 
				AND elementtype='".bkstr($eltype)."' 
				AND elementid=".bkint($elid)."
			GROUP BY module, elementtype, elementid
		";
		$row = $db->query_first($sql);
		
		// запись результата
		$sql = "
			INSERT INTO ".$db->prefix."urating_votecalc
			(module, elementtype, elementid, votecount, voteup, votedown, upddate) VALUES
			(
				'".bkstr($modname)."',
				'".bkstr($eltype)."',
				".bkint($elid).",
				".bkint($row['votecount']).",
				".bkint($row['voteup']).",
				".bkint($row['votedown']).",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				votecount=".bkint($row['votecount']).",
				voteup=".bkint($row['voteup']).",
				votedown=".bkint($row['votedown']).",
				upddate=".TIMENOW."
		";
		$db->query_write($sql);
	}
	
	public static function ElementVoteCalc(Ab_Database $db, $modname, $eltype, $elid){
		$sql = "
			SELECT
				votecount as cnt,
				voteup as up,
				votedown as down
			FROM ".$db->prefix."urating_votecalc
			WHERE module='".bkstr($modname)."'
				AND elementtype='".bkstr($eltype)."'
				AND elementid=".bkint($elid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function UserReputation(Ab_Database $db, $userid){
		$sql = "
			SELECT
				userid as id,
				reputation as rep,
				votecount as vcnt,
				skill
			FROM ".$db->prefix."urating_user
			WHERE userid=".bkint($userid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function UserReputationUpdate(Ab_Database $db, $userid, $votecount, $voteup, $votedown){
		$sql = "
			INSERT INTO ".$db->prefix."urating_user
				(userid, reputation, votecount, voteup, votedown, votedate) VALUES (
				".bkint($userid).",
				".bkint($voteup-$votedown).",
				".bkint($votecount).",
				".bkint($voteup).",
				".bkint($votedown).",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				reputation=".bkint($voteup-$votedown).",
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
	
	public static function UserSkillModuleUpdate(Ab_Database $db, $userid, $module, $skill){
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
	
	public static function UserSkillCalculateList(Ab_Database $db, $userid){
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
	
	public static function UserSkillUpdate(Ab_Database $db, $userid, $skill){
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
	
	public static function UserSkillClear(Ab_Database $db, $userid){
		if (!is_array($userid)){
			$userid = array($userid);
		}
		if (count($userid) == 0){ return null; }
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

?>