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
		
		'DETAIL_URL' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
			'NAME' => GetMessage('INTR_ISHU_PARAM_DETAIL_URL'),
			'PARENT' => 'BASE'
		),
		
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);
?>