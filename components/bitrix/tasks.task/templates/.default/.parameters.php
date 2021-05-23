<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/*
	"GROUPS" => array(
		"FILTER_SETTINGS" => array(
			"NAME" => Loc::getMessage("T_IBLOCK_DESC_FILTER_SETTINGS"),
		),

	// BASE, VISUAL, DATA_SOURCE, ADDITIONAL
*/

$arTemplateParameters = array(

	// path

	"PATH_TO_USER_TASKS" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_USER_TASKS"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),
	"PATH_TO_GROUP_TASKS" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_GROUP_TASKS"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),
	"PATH_TO_GROUP_TASKS_TASK" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_GROUP_TASKS_TASK"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),

	"PATH_TO_USER_PROFILE" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_USER_PROFILE"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),
	"PATH_TO_GROUP" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_GROUP"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),

	/*
	"PATH_TO_USER_TASKS_TEMPLATES" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_USER_TASKS_TEMPLATES"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),

	"PATH_TO_USER_TEMPLATES_TEMPLATE" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_PATH_TO_USER_TASKS_TEMPLATES"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	),
	*/

	// additional

	"SET_NAVCHAIN" => Array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_SET_NAVCHAIN"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y"
	),
	"SET_TITLE" => Array( // the default description will be used
	),

	"SHOW_RATING" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_SHOW_RATING"),
		/*
		"TYPE" => "LIST",
		"VALUES" => Array(
			"" => Loc::getMessage("SHOW_RATING_CONFIG"),
			"Y" => Loc::getMessage("MAIN_YES"),
			"N" => Loc::getMessage("MAIN_NO"),
		),
		*/
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
		"MULTIPLE" => "N",
		"PARENT" => "ADDITIONAL_SETTINGS",
	),
	"RATING_TYPE" => Array(
		"NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_RATING_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => Array(
			"" => Loc::getMessage("TASKS_TASK_TEMPLATE_RATING_TYPE_CONFIG"),
			"like" => Loc::getMessage("TASKS_TASK_TEMPLATE_RATING_TYPE_LIKE_TEXT"),
			"like_graphic" => Loc::getMessage("TASKS_TASK_TEMPLATE_RATING_TYPE_LIKE_GRAPHIC"),
			"standart_text" => Loc::getMessage("TASKS_TASK_TEMPLATE_RATING_TYPE_STANDART_TEXT"),
			"standart" => Loc::getMessage("TASKS_TASK_TEMPLATE_RATING_TYPE_STANDART_GRAPHIC"),
		),
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"PARENT" => "ADDITIONAL_SETTINGS",
	),

    "REDIRECT_ON_SUCCESS" => Array(
        "PARENT" => "ADDITIONAL_SETTINGS",
        "NAME" => Loc::getMessage("TASKS_TASK_TEMPLATE_REDIRECT_ON_SUCCESS"),
        "TYPE" => "CHECKBOX",
        "DEFAULT" => "Y"
    ),
		/*
		"TASK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => Loc::getMessage("INTL_TASK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => array("group" => Loc::getMessage("INTL_TASK_TYPE_GROUP"), "user" => Loc::getMessage("INTL_TASK_TYPE_USER")),
		),
		"TASK_VAR" => Array(
			"NAME" => Loc::getMessage("INTL_TASK_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"USER_VAR" => Array(
			"NAME" => Loc::getMessage("INTL_USER_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"GROUP_VAR" => Array(
			"NAME" => Loc::getMessage("INTL_GROUP_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"ACTION_VAR" => Array(
			"NAME" => Loc::getMessage("INTL_ACTION_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PAGE_VAR" => Array(
			"NAME" => Loc::getMessage("INTL_PAGE_VAR"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "VARIABLE_ALIASES",
		),
		"PATH_TO_GROUP_TASKS" => Array(
			"NAME" => Loc::getMessage("INTL_PATH_TO_GROUP_TASKS"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_GROUP_TASKS_TASK" => Array(
			"NAME" => Loc::getMessage("INTL_PATH_TO_GROUP_TASKS_TASK"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_GROUP_TASKS_VIEW" => Array(
			"NAME" => Loc::getMessage("INTL_PATH_TO_GROUP_TASKS_VIEW"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_FORUM_SMILE" => Array(
			"NAME" => Loc::getMessage("INTL_PATH_TO_FORUM_SMILE"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/bitrix/images/forum/smile/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"URL_TEMPLATES_PROFILE_VIEW" => Array(
			"PARENT" => "URL_TEMPLATES",
			"NAME" => Loc::getMessage("INTL_PATH_TO_PROFILE_VIEW"),
			"TYPE" => "STRING",
			"DEFAULT" => ""),
		"SHOW_RATING" => Array(
			"NAME" => Loc::getMessage("SHOW_RATING"),
			"TYPE" => "LIST",
			"VALUES" => Array(
				"" => Loc::getMessage("SHOW_RATING_CONFIG"),
				"Y" => Loc::getMessage("MAIN_YES"),
				"N" => Loc::getMessage("MAIN_NO"),
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"RATING_TYPE" => Array(
			"NAME" => Loc::getMessage("RATING_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => Array(
				"" => Loc::getMessage("RATING_TYPE_CONFIG"),
				"like" => Loc::getMessage("RATING_TYPE_LIKE_TEXT"),
				"like_graphic" => Loc::getMessage("RATING_TYPE_LIKE_GRAPHIC"),
				"standart_text" => Loc::getMessage("RATING_TYPE_STANDART_TEXT"),
				"standart" => Loc::getMessage("RATING_TYPE_STANDART_GRAPHIC"),
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",),
		"SET_NAVCHAIN" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => Loc::getMessage("INTL_SET_NAVCHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"
		),
		"SET_TITLE" => Array(),
		"ITEMS_COUNT" => Array(
			"NAME" => Loc::getMessage("INTL_ITEM_COUNT"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "20",
			"COLS" => 3,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		*/
		//"CACHE_TIME"  =>  array("DEFAULT" => 36000000)
);