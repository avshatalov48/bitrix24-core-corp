<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
	return;

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'mode' => Array('NAME' => GetMessage('CRM_PERMS_CX_MODE')),
			'role_id' => Array('NAME' => GetMessage('CRM_PERMS_CX_ROLE_ID')),
		),
		'SEF_MODE' => Array(
			'PATH_TO_ENTITY_LIST' => array(
				'NAME' => GetMessage('CRM_PERMS_CX_ENTITY_LIST'),
				'DEFAULT' => '',
				'VARIABLES' => array(),
			),
			'PATH_TO_ROLE_EDIT' => array(
				'NAME' => GetMessage('CRM_PERMS_CX_ROLE_EDIT'),
				'DEFAULT' => '#role_id#/edit/',
				'VARIABLES' => array('role_id'),
			)
		)
	)
);
?>
