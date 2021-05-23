<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'PATH_TO_ENTITY_LIST' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_PERMS_CX_ENTITY_LIST'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'perms.php',
		),
		'PATH_TO_ROLE_EDIT' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => GetMessage('CRM_PERMS_CX_ROLE_EDIT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'role.edit.php?role_id=#role_id#',
		)
	)
);
?>