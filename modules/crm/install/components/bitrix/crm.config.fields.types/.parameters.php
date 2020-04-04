<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return false;
			
$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'FIELDS_LIST_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_FIELDS_LIST_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'field.list.php?entity_id=#entity_id#'
		),
		'FIELD_EDIT_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_FIELD_EDIT_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'field.edit.php?entity_id=#entity_id#&field_id=#field_id#'
		)
	)
);
?>
