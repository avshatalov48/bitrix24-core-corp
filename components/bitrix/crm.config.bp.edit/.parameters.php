<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'BP_ENTITY_ID' => array(
			'PARENT' => 'ADDITIONAL_SETTINGS',
			'NAME' => GetMessage('CRM_BP_ENTITY_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["entity_id"]}',
		),	
		'BP_BP_ID' => array(
			'PARENT' => 'ADDITIONAL_SETTINGS',
			'NAME' => GetMessage('CRM_BP_BP_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["bp_id"]}',
		),
		'ENTITY_LIST_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_ENTITY_LIST_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'bp.php',
		),
		'BP_LIST_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_BP_LIST_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'bp.list.php?entity_id=#entity_id#',
		),
		'BP_EDIT_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_BP_EDIT_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'bp.edit.php?entity_id=#entity_id#&bp_id=#bp_id#',
		)
	)
);
?>
