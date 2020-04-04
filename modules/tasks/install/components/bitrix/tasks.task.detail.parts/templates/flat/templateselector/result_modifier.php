<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$menuItems = array();

$commonUrl = CComponentEngine::MakePathFromTemplate($arParams['TEMPLATE_DATA']["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));

if(is_array($arResult['TEMPLATE_DATA']['DATA']['TEMPLATES']))
{
	foreach($arResult['TEMPLATE_DATA']['DATA']['TEMPLATES'] as $template)
	{
		$menuItems[] = array(
			'ID' => $template['ID'],
			'TITLE' => $template['TITLE'],
			'URL' => $commonUrl.(strpos($commonUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"]
		);
	}
}

$arResult['COMMON_URL'] = $commonUrl;
$arResult['MENU_ITEMS'] = $menuItems;