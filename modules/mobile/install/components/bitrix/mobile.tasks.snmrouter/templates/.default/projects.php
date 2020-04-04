<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$arParams = array_merge($arParams, array(
	'SHOW_SECTIONS_BAR' => 'Y',
	'SHOW_SECTION_COUNTERS' => 'Y'
));

$APPLICATION->IncludeComponent(
	"bitrix:tasks.projects_overview.old",
	"",
	array(
		"USER_ID"            => $arParams["USER_ID"],
		"NAME_TEMPLATE"      => $arParams["NAME_TEMPLATE"],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS'],
		'PATH_TO_GROUP'      => str_replace("//", "/", "/".SITE_DIR."mobile/log/?group_id=#group_id#"),
		"PATH_TO_USER"       => $arParams["PATH_TEMPLATE_TO_USER_PROFILE"],
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		"PATH_TO_TASKS"      => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS'],
			array('USER_ID' => $arParams["USER_ID"])
		),
/*		"PATH_TO_REPORTS" => CComponentEngine::MakePathFromTemplate(
			$arResult["PATH_TO_USER_TASKS_REPORT"],
			array('user_id' => $arResult["VARIABLES"]["user_id"])
		),
		"PATH_TO_TASKS_REPORT_CONSTRUCT" => CComponentEngine::MakePathFromTemplate(
			$arResult["PATH_TO_USER_TASKS_REPORT_CONSTRUCT"],
			array('user_id' => $arResult["VARIABLES"]["user_id"])
		),
		"PATH_TO_TASKS_REPORT_VIEW" => CComponentEngine::MakePathFromTemplate(
			$arResult["PATH_TO_USER_TASKS_REPORT_VIEW"],
			array('user_id' => $arResult["VARIABLES"]["user_id"])
		),
		"PATH_TO_USER_TASKS_TEMPLATES" => $arResult["PATH_TO_USER_TASKS_TEMPLATES"]*/
	),
	$this->__component
);
