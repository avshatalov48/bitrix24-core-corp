<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'PARAMETERS' => array(		
		'ELEMENT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_ELEMENT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["company_id"]}'
		),
		'TYPE' => Array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_MENU_TYPE'),
			'TYPE' => 'LIST',
			'VALUES' => array('list' => 'LIST', 'show' => 'SHOW', 'edit' => 'EDIT'),
			'DEFAULT' => 'page'		
		)											
	)	
);
?>