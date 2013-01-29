<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class URatingQuery {
	
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
				module as m, 
				count(*) as cnt
			FROM ".$db->prefix."urating_elementvote
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
			FROM ".$db->prefix."urating_elementvote
			WHERE module='".bkstr($modname)."' 
				AND elementtype='".bkstr($eltype)."' 
				AND elementid=".bkint($elid)."
				AND userid=".bkint($userid)."
			LIMIT 1
		";
		return $db->query_first($sql);
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