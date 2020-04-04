<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (CModule::IncludeModule('crm'))
{
	$aMenuLinksExt = array(
		Array(
			GetMessage("CRM_EVENT_LIST"),
			"#SITE_DIR#crm/events/index.php",
			Array(),
			Array(),
			""
		),
		Array(
			GetMessage("CRM_TASK_LIST"),
			"#SITE_DIR#crm/events/task/",
			Array(),
			Array(),
			"CModule::IncludeModule('task')"
		)
	);

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>