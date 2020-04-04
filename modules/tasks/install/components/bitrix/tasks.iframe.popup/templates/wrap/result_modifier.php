<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// js extension to be registered instead of script.js

$folder = $this->GetFolder();
$extensionId = 'tasks_component_ext_'.rand(999, 9999999);

CJSCore::RegisterExt(
	$extensionId,
	array(
		'js'  => $folder.'/logic.js',
		//'css' => '/bitrix/js/tasks/css/tasks.css',
		'rel' =>  array(
			'clipboard',
			'tasks',
			'tasks_util',
			'tasks_component',
			'viewer' // it is necessary to show iframe's images in a top window
		),
		'lang' => $folder.'/lang/'.LANGUAGE_ID.'/template.php'
	)
);
CJSCore::Init($extensionId);