<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//use Bitrix\Main\Localization\Loc;
//Loc::loadMessages(dirname(__FILE__).'/template.php');

// js extension to be registered instead of script.js 

$folder = $this->GetFolder();
$extensionId = 'tasks_component_ext_'.rand(999, 9999999);

CJSCore::RegisterExt(
	$extensionId,
	array(
		'js'  => $folder.'/logic.js',
		'css' => '/bitrix/js/tasks/css/tasks.css',
		'rel' =>  array(
			'tasks',
			'tasks_util',
			'popup',
			'viewer' // it is necessary to show iframe's images in a top window
		),
		'lang' => $folder.'/lang/'.LANGUAGE_ID.'/template.php'
	)
);
CJSCore::Init($extensionId);

$arResult['CALLBACKS'] = array(
    'ADD' => (string) $arParams['ON_TASK_ADDED'] == '' || $arParams['ON_TASK_ADDED'] == 'BX.DoNothing' ? false : $arParams['ON_TASK_ADDED'],
    'UPDATE' => (string) $arParams['ON_TASK_CHANGED'] == '' || $arParams['ON_TASK_CHANGED'] == 'BX.DoNothing' ? false : $arParams['ON_TASK_CHANGED'],
    'DELETE' => (string) $arParams['ON_TASK_DELETED'] == '' || $arParams['ON_TASK_DELETED'] == 'BX.DoNothing' ? false : $arParams['ON_TASK_DELETED'],
);