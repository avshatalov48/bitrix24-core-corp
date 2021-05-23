<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	'PARAMETERS' => array(
		'ID' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => "={\$USER->GetID()}",
			'NAME' => GetMessage('INTR_ISHU_PARAM_ID'),
			'PARENT' => 'BASE'
		),
		
		'NUM_ENTRIES' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => "10",
			'NAME' => GetMessage('INTR_ISHU_PARAM_NUM_ENTRIES'),
			'PARENT' => 'BASE'
		),
		
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);
?>