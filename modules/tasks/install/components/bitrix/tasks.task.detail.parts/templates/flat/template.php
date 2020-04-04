<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arResult["BLOCK"])
{
	CJSCore::RegisterExt(
		$arResult["EXTENSION_ID"],
		array(
			"js"  => array(
				$arResult["TEMPLATE_FOLDER"]."logic.js"
			),
			"css" => array(
				$arResult["TEMPLATE_FOLDER"]."style.css"
			),
			"rel" =>  array(
				"popup",
				"tasks",
				"tasks_util",
				"tasks_util_widget",
				"tasks_util_template",
				"tasks_util_draganddrop",
				"tasks_util_itemset",
				"tasks_util_datepicker",
				"tasks_itemsetpicker",
				"task_popups",
				"task_calendar",
				"tasks_dayplan",
				"date"
			),
			"lang" => $arResult["TEMPLATE_FOLDER"]."/lang/".LANGUAGE_ID."/template.php",
		)
	);
	CJSCore::Init($arResult["EXTENSION_ID"]);

	require($_SERVER["DOCUMENT_ROOT"].$arResult["TEMPLATE_FOLDER"]."template.php");
}