<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

$arComponentParameters = [
	'PARAMETERS' => [
		'VARIABLE_ALIASES' => [
			'page' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_VAR_PAGE'),
				'DEFAULT' => 'page'
			],
			'doc_id' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_VAR_DOC_ID'),
				'DEFAULT' => 'doc_id'
			],
		],
		'SEF_MODE' => [
			'main_page' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_MAIN_PAGE'),
				'DEFAULT' => '',
				'VARIABLES' => []
			],
			'kanban' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_KANBAN'),
				'DEFAULT' => 'kanban/',
				'VARIABLES' => []
			],
			'list' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_LIST'),
				'DEFAULT' => 'list/',
				'VARIABLES' => []
			],
			'mysafe' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_MYSAFE'),
				'DEFAULT' => 'mysafe/',
				'VARIABLES' => []
			],
			'contactList' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_COUNTERPARTY_CONTACT_LIST'),
				'DEFAULT' => 'contact/',
				'VARIABLES' => []
			],
			'document' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_DOCUMENT'),
				'DEFAULT' => 'doc/#doc_id#/',
				'VARIABLES' => ['doc_id']
			],
			'edit' => [
				'NAME' => getMessage('SIGN_CMP_START_PAR_URL_EDIT'),
				'DEFAULT' => 'edit/#doc_id#/',
				'VARIABLES' => ['doc_id']
			],
		]
	]
];
