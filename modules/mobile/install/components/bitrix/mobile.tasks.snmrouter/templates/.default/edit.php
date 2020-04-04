<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$APPLICATION->IncludeComponent(
	"bitrix:tasks.task",
	".default",
	array(
		"ID" => $arParams["TASK_ID"],
		"GROUP_ID" => "",
		"USER_ID" => $arParams["USER_ID"],
		"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_TASKS"],
		"PATH_TO_USER_TASKS" => $arParams["PATH_TO_USER_TASKS"],
		"PATH_TO_USER_TASKS_TASK" => $arParams["PATH_TO_USER_TASKS"],
		"PATH_TO_USER_TASKS_TEMPLATES" => "",
		"PATH_TO_USER_TEMPLATES_TEMPLATE" => "",
		"PATH_TO_USER_TASKS_VIEW" => $arParams["PATH_TO_USER_TASKS_TASK"],
		"PATH_TO_USER_TASKS_EDIT" => $arParams["PATH_TO_USER_TASKS_EDIT"],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => "",
		"PATH_TO_GROUP" => "",
		"PATH_TO_GROUP_TASKS" => "",

		"SET_NAV_CHAIN" => "N",
		"SET_TITLE" => "N",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],

		"SHOW_RATING" => "Y",
		"RATING_TYPE" => "like",

		"SUB_ENTITY_SELECT" => array(
			"TAG",
			"CHECKLIST",
			"REMINDER",
			"PROJECTDEPENDENCE",
			"TEMPLATE",
			"LOG",
			"ELAPSEDTIME",
			"TIMEMANAGER"
		),
		"AUX_DATA_SELECT" => array(
			"COMPANY_WORKTIME",
			"USER_FIELDS",
			"TEMPLATE"
		)

	),
	$this->__component,
	array("HIDE_ICONS" => "Y")
);