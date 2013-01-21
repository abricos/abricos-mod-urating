<?php
/**
 * @package Abricos
 * @subpackage URating
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class URatingQuery {
	
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