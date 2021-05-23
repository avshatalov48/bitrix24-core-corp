<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;

$pageId = "user_tasks";
include("util_menu.php");
include("util_profile.php");

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].$this->getFolder().'/result_modifier.php');

if (!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $arResult["VARIABLES"]["user_id"], "tasks"))
{
	echo Loc::getMessage('SU_T_TASKS_UNAVAILABLE', array(
		'#A_BEGIN#' => '<a href="'.str_replace(array("#user_id#", "#USER_ID#"), $arResult['VARIABLES']['user_id'], $arResult['PATH_TO_USER_FEATURES']).'">',
		'#A_END#' => '</a>'
	));
}
elseif (\CModule::IncludeModule('tasks'))
{
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.iframe.popup",
		"wrap",
		array(
			"ACTION" => $arResult["VARIABLES"]["action"] === "edit" ? "edit" : "view",
			"FORM_PARAMETERS" => array(
				"ID" => $arResult["VARIABLES"]["task_id"],
				"GROUP_ID" => "",
				"USER_ID" => $arResult["VARIABLES"]["user_id"],
				"PATH_TO_USER_TASKS" => $arResult["PATH_TO_USER_TASKS"],
				"PATH_TO_USER_TASKS_TASK" => $arResult["PATH_TO_USER_TASKS_TASK"],
				"PATH_TO_GROUP_TASKS" => $arParams["PATH_TO_GROUP_TASKS"],
				"PATH_TO_GROUP_TASKS_TASK" => "",
				"PATH_TO_USER_PROFILE" => $arResult["PATH_TO_USER"],
				"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
				"PATH_TO_USER_TASKS_PROJECTS_OVERVIEW" => $arResult["PATH_TO_USER_TASKS_PROJECTS_OVERVIEW"],
				"PATH_TO_USER_TASKS_TEMPLATES" => $arResult["PATH_TO_USER_TASKS_TEMPLATES"],
				"PATH_TO_USER_TEMPLATES_TEMPLATE" => $arResult["PATH_TO_USER_TEMPLATES_TEMPLATE"],
				"SET_NAVCHAIN" => $arResult["SET_NAV_CHAIN"],
				"SET_TITLE" => $arResult["SET_TITLE"],
				"SHOW_RATING" => $arParams["SHOW_RATING"],
				"RATING_TYPE" => $arParams["RATING_TYPE"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			)
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);
}
