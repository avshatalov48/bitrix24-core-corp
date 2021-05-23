<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return;

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'mode' => Array('NAME' => GetMessage('CRM_FIELD_CX_MODE')),
			'entity_id' => Array('NAME' => GetMessage('CRM_FIELD_CX_ENTITY_ID')),
			'field_id' => Array('NAME' => GetMessage('CRM_FIELD_CX_FIELD_ID')),
		),
		'SEF_MODE' => Array(
			'ENTITY_LIST_URL' => array(
				'NAME' => GetMessage('CRM_FIELD_CX_ENTITY_LIST'),
				'DEFAULT' => '',
				'VARIABLES' => array(),
			),
			'FIELDS_LIST_URL' => array(
				'NAME' => GetMessage('CRM_FIELD_CX_FIELDS_LIST'),
				'DEFAULT' => '#entity_id#/',
				'VARIABLES' => array('entity_id'),
			),
			'FIELD_EDIT_URL' => array(
				'NAME' => GetMessage('CRM_FIELD_CX_FIELD_EDIT'),
				'DEFAULT' => '#entity_id#/edit/#field_id#/',
				'VARIABLES' => array('entity_id', 'field_id'),
			),
		),
	),
);
?>
