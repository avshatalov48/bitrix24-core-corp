<?php

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/intranet/classes/general/ratings_components.php");

IncludeModuleLangFile(__FILE__);

class CRatingsComponentsIntranet extends CAllRatingsComponentsIntranet
{
	public static function CalcSubordinateBonus($arConfigs)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$communityLastVisit = COption::GetOptionString("main", "rating_community_last_visit", '90');

		CRatings::AddComponentResults($arConfigs);

		$strSql = "DELETE FROM b_rating_component_results WHERE RATING_ID = '".intval($arConfigs['RATING_ID'])."' AND COMPLEX_NAME = '".$helper->forSql($arConfigs['COMPLEX_NAME'])."'";
		$connection->query($strSql);

		$strSql = "INSERT INTO b_rating_component_results (RATING_ID, MODULE_ID, RATING_TYPE, NAME, COMPLEX_NAME, ENTITY_ID, ENTITY_TYPE_ID, CURRENT_VALUE)
					SELECT
						'".intval($arConfigs['RATING_ID'])."'  RATING_ID,
						'".$helper->forSql($arConfigs['MODULE_ID'])."'  MODULE_ID,
						'".$helper->forSql($arConfigs['RATING_TYPE'])."'  RATING_TYPE,
						'".$helper->forSql($arConfigs['NAME'])."' RATING_NAME,
						'".$helper->forSql($arConfigs['COMPLEX_NAME'])."'  COMPLEX_NAME,
						RS.ENTITY_ID as ENTITY_ID,
						'".$helper->forSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
						(RU.BONUS+RS.VOTES)*".floatval($arConfigs['CONFIG']['COEFFICIENT'])."  CURRENT_VALUE
					FROM
						b_rating_subordinate RS
						LEFT JOIN b_user U ON U.ID = RS.ENTITY_ID AND U.ACTIVE = 'Y' AND U.LAST_LOGIN > " . $helper->addDaysToDateTime(-intval($communityLastVisit)) . "
						LEFT JOIN b_rating_user RU ON RU.RATING_ID = RS.RATING_ID AND RU.ENTITY_ID = RS.ENTITY_ID
					WHERE
						RS.RATING_ID = ".intval($arConfigs['RATING_ID'])."
						AND U.ID IS NOT NULL";
		$connection->query($strSql);

		return true;
	}
}
