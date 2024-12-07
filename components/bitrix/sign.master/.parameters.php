<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

$arComponentParameters = [
	'PARAMETERS' => [
		'PAGE_URL_EDIT' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_PAGE_URL_EDIT'),
			'TYPE' => 'STRING'
		],
		'OPEN_URL_AFTER_CLOSE' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_OPEN_URL_AFTER_CLOSE'),
			'TYPE' => 'STRING'
		],
		'CRM_ENTITY_TYPE_ID' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_ENTITY_TYPE_ID'),
			'TYPE' => 'STRING'
		],
		'ENTITY_TYPE_ID' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_ENTITY_TYPE_ID'),
			'TYPE' => 'STRING'
		],
		'CATEGORY_ID' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_CATEGORY_ID'),
			'TYPE' => 'STRING'
		],
		'VAR_STEP_ID' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_VAR_STEP_ID'),
			'TYPE' => 'STRING'
		],
		'VAR_DOC_ID' => [
			'NAME' => getMessage('SIGN_CMP_MASTER_PAR_VAR_DOC_ID'),
			'TYPE' => 'STRING'
		]
	]
];
