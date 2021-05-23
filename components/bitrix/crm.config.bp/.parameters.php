<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return;

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'mode' => Array('NAME' => GetMessage('CRM_BP_CX_MODE')),
			'entity_id' => Array('NAME' => GetMessage('CRM_BP_CX_ENTITY_ID')),
			'bp_id' => Array('NAME' => GetMessage('CRM_BP_CX_BP_ID')),
		),
		'SEF_MODE' => Array(
			'ENTITY_LIST_URL' => array(
				'NAME' => GetMessage('CRM_BP_CX_ENTITY_LIST'),
				'DEFAULT' => '',
				'VARIABLES' => array(),
			),
			'BP_LIST_URL' => array(
				'NAME' => GetMessage('CRM_BP_CX_BP_LIST'),
				'DEFAULT' => '#entity_id#/',
				'VARIABLES' => array('entity_id'),
			),
			'BP_EDIT_URL' => array(
				'NAME' => GetMessage('CRM_BP_CX_BP_EDIT'),
				'DEFAULT' => '#entity_id#/edit/#bp_id#/',
				'VARIABLES' => array('entity_id', 'bp_id'),
			),
		),
	),
);
?>
