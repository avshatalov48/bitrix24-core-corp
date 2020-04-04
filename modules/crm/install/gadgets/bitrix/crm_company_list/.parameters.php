<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return ;

$arParameters = Array(
	'PARAMETERS'=> Array(
	),
	'USER_PARAMETERS'=> Array(
		'TYPE_ID' => Array(
			'NAME' => GetMessage('GD_CRM_COLUMN_COMPANY_TYPE'),
			'TYPE' => 'LIST',
			'VALUES' => array('' => '') + CCrmStatus::GetStatusListEx('COMPANY_TYPE'),
			'MULTIPLE' => 'Y',
			'DEFAULT' => array()
		),
		'ONLY_MY' => Array(
			'NAME' => GetMessage('GD_CRM_ONLY_MY'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N'
		),
		'SORT' => Array(
			'NAME' => GetMessage('GD_CRM_SORT'),
			'TYPE' => 'LIST',
			'VALUES' => array(
				'DATE_CREATE' => GetMessage('GD_CRM_COLUMN_DATE_CREATE'),
				'DATE_MODIFY' => GetMessage('GD_CRM_COLUMN_DATE_MODIFY'),
				'STAGE_ID' => GetMessage('GD_CRM_COLUMN_COMPANY_TYPE')
			),
			'DEFAULT' => 'DATE_CREATE'
		),
		'SORT_BY' => Array(
			'NAME' => GetMessage('GD_CRM_SORT_BY'),
			'TYPE' => 'LIST',
			'VALUES' => array(
				'ASC' => GetMessage('GD_CRM_SORT_ASC'),
				'DESC' => GetMessage('GD_CRM_SORT_DESC')
			),
			'DEFAULT' => 'DESC'
		),
		'COMPANY_COUNT' => Array(
			'NAME' => GetMessage('GD_CRM_COMPANY_LIST_COMPANY_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 5
		)
	)
);

?>