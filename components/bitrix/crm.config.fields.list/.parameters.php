<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return false;

$arEntityIds = CCrmFields::GetEntityTypes();
$arEntity = array();
foreach($arEntityIds as $key => $ar)
	$arEntity[$ar['ID']] = $ar['NAME'];
				
			
$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'FIELS_ENTITY_ID' => array(
			'PARENT' => 'ADDITIONAL_SETTINGS',
			'NAME' => GetMessage('CRM_FIELS_ENTITY_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arEntity,
			'DEFAULT' => 'CRM_LEAD',
		),
		'ENTITY_LIST_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_ENTITY_LIST_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'field.php',
		),
		'FIELDS_LIST_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_FIELDS_LIST_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'field.list.php?entity_id=#entity_id#',
		),
		'FIELD_EDIT_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_FIELD_EDIT_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'field.edit.php?entity_id=#entity_id#&field_id=#field_id#',
		),
	),
);
?>
