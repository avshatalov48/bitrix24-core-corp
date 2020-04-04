<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->ShowViewContent("task_menu");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$bodyClass = $bodyClass ? $bodyClass." page-one-column" : "page-one-column";
$APPLICATION->SetPageProperty("BodyClass", $bodyClass);

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'USER_ID' => $arResult['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],
		'SECTION_URL_PREFIX' => '',
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
		'MARK_SECTION_REPORTS' => 'Y',
		'MARK_TEMPLATES' => 'N',
		'MARK_ACTIVE_ROLE' => 'N'
	),
	$component,
	array('HIDE_ICONS' => true)
);

$APPLICATION->IncludeComponent(
	"bitrix:report.list",
	"",
	array(
		"USER_ID" => $arResult["USER_ID"],
		"GROUP_ID" => $arParams["GROUP_ID"],
		"PATH_TO_REPORT_LIST" => $arParams["PATH_TO_TASKS_REPORT"],
		"PATH_TO_REPORT_CONSTRUCT" => $arParams["PATH_TO_TASKS_REPORT_CONSTRUCT"],
		"PATH_TO_REPORT_VIEW" => $arParams["PATH_TO_TASKS_REPORT_VIEW"],
		"REPORT_HELPER_CLASS" => "CTasksReportHelper"
	),
	false
);

?>