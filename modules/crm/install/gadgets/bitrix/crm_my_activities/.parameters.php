<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arParameters = array(
	'PARAMETERS'=> array(					
	),
	'USER_PARAMETERS'=> array(	
		'ITEM_COUNT' => array(
			'NAME' => GetMessage('GD_CRM_MY_ACTIVITIES_ITEM_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 5
		),
		'PATH_TO_FULL_VIEW' => array(
			'NAME' => GetMessage('GD_CRM_MY_ACTIVITIES_PATH_TO_FULL_VIEW'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		)
	)
);

?>
