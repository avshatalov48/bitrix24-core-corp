<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// js extension to be registered instead of script.js 

$folder = $this->GetFolder();

if((string) $arParams['TEMPLATE_DATA']['ID'] != '')
{
	$arResult['TEMPLATE_DATA'] = $arParams['TEMPLATE_DATA'];
}
else
{
	$arResult['TEMPLATE_DATA'] = array(
		'ID' => md5($folder),
	);
}

$extensionId = md5('tasks_component_ext_'.$arResult['TEMPLATE_DATA']['ID']);

CJSCore::RegisterExt(
	$extensionId,
	array(
		'js'  => $folder.'/logic.js',
		'rel' =>  array('tasks_ui_itemset'),
		'css' => array('/bitrix/js/main/core/css/core_tags.css')
		//'lang' => $folder.'/lang/'.LANGUAGE_ID.'/template.php'
	)
);
CJSCore::Init($extensionId);