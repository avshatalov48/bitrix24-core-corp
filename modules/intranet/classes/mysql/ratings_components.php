<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/intranet/classes/general/ratings_components.php");
IncludeModuleLangFile(__FILE__);

class CRatingsComponentsIntranet extends CAllRatingsComponentsIntranet
{
	function CalcSubordinateBonus($arConfigs)
	{
		global $DB;

		$err_mess = (CRatings::err_mess())."<br>Function: CalcSubordinateBonus<br>Line: ";

		$communityLastVisit = COption::GetOptionString("main", "rating_community_last_visit", '90');

		CRatings::AddComponentResults($arConfigs);

		$strSql = "DELETE FROM b_rating_component_results WHERE RATING_ID = '".IntVal($arConfigs['RATING_ID'])."' AND COMPLEX_NAME = '".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "INSERT INTO b_rating_component_results (RATING_ID, MODULE_ID, RATING_TYPE, NAME, COMPLEX_NAME, ENTITY_ID, ENTITY_TYPE_ID, CURRENT_VALUE)
					SELECT
						'".IntVal($arConfigs['RATING_ID'])."'  RATING_ID,
						'".$DB->ForSql($arConfigs['MODULE_ID'])."'  MODULE_ID,
						'".$DB->ForSql($arConfigs['RATING_TYPE'])."'  RATING_TYPE,
						'".$DB->ForSql($arConfigs['NAME'])."'  NAME,
						'".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'  COMPLEX_NAME,
						RS.ENTITY_ID as ENTITY_ID,
						'".$DB->ForSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
						(RU.BONUS+RS.VOTES)*".floatval($arConfigs['CONFIG']['COEFFICIENT'])."  CURRENT_VALUE
					FROM
						b_rating_subordinate RS
						LEFT JOIN b_user U ON U.ID = RS.ENTITY_ID AND U.ACTIVE = 'Y' AND U.LAST_LOGIN > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
						LEFT JOIN b_rating_user RU ON RU.RATING_ID = RS.RATING_ID AND RU.ENTITY_ID = RS.ENTITY_ID
					WHERE
						RS.RATING_ID = ".IntVal($arConfigs['RATING_ID'])."
						AND U.ID IS NOT NULL";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}
}
?>