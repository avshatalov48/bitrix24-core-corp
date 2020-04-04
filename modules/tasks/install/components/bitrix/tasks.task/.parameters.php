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

$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"ID" => Array(
			"NAME" => Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "DATA_SOURCE",
		),
		"SUB_ENTITY_SELECT" => array(
			"PARENT" => "BASE",
			"NAME" => Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_SELECT"),
			"MULTIPLE" => "Y",
			"TYPE" => "LIST",
			"VALUES" => array(
				'TAG' =>			Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_TAG"),
				'CHECKLIST' => 		Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_CHECKLIST"),
				'REMINDER' => 		Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_REMINDER"),
				'LOG' => 			Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_LOG"),
				'ELAPSEDTIME' => 	Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_ELAPSEDTIME"),
				'TEMPLATE' => 		Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_TEMPLATE"),
				'PROJECTDEPENDENCE' => 	Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_PROJECTDEPENDENCE"),
				'RELATEDTASK' => 	Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_RELATEDTASK"),
				'DAYPLAN' => 	    Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_SUB_ENTITY_TIMEMAN"),
			),
		),
		"AUX_DATA_SELECT" => array(
			"PARENT" => "BASE",
			"NAME" => Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_AUX_DATA_SELECT"),
			"MULTIPLE" => "Y",
			"TYPE" => "LIST",
			"VALUES" => array(
				'COMPANY_WORKTIME' => 	Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_AUX_DATA_COMPANY_WORKTIME"),
				'USER_FIELDS' => 		Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_AUX_DATA_USER_FIELDS"),
				'TEMPLATE' => 			Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_AUX_DATA_TEMPLATE"),
			),
		),

		"GROUP_ID" => array(
			"NAME" => Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_GROUP_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "DATA_SOURCE",
		),
		"USER_ID" => array(
			"NAME" => Loc::getMessage("TASKS_TASK_COMPONENT_PARAM_USER_ID"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "DATA_SOURCE",
		),
	)
);