<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */
?>

<div class="task-public-workarea">
	<div class="task-public-sidebar"><? $APPLICATION->ShowViewContent("sidebar"); ?></div>
	<div class="task-public-content">

<?
$APPLICATION->IncludeComponent(
	"bitrix:tasks.task",
	"view",
	array(
		"ID" => isset($_REQUEST["task_id"]) ? intval($_REQUEST["task_id"]) : 0,
		"GROUP_ID" => "",
		"PATH_TO_USER_TASKS" => "",
		"PATH_TO_USER_TASKS_TASK" => "",
		"PATH_TO_GROUP_TASKS" => "",
		"PATH_TO_GROUP_TASKS_TASK" => "",
		"PATH_TO_USER_PROFILE" => "",
		"PATH_TO_GROUP" => "",
		"PATH_TO_USER_TASKS_PROJECTS_OVERVIEW" => "",
		"PATH_TO_USER_TASKS_TEMPLATES" => "",
		"PATH_TO_USER_TEMPLATES_TEMPLATE" => "",
		"SET_NAVCHAIN" => "N",
		"SET_TITLE" => "Y",
		"SHOW_RATING" => "Y",
		"RATING_TYPE" => "like",

		"PUBLIC_MODE" => true,
		"ENABLE_MENU_TOOLBAR" => "N",
		"SUB_ENTITY_SELECT" => array(
			"TAG",
			"CHECKLIST",
			"REMINDER",
			"TEMPLATE",
			"LOG",
			"ELAPSEDTIME",
			"DAYPLAN"
		),
		"AUX_DATA_SELECT" => array(
			"COMPANY_WORKTIME",
			"USER_FIELDS"
		)
	),
	null,
	array("HIDE_ICONS" => "Y")
);
?>
	</div>
</div>