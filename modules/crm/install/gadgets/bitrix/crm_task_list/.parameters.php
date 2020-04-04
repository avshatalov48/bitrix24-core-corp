<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$arParameters = Array(
	'PARAMETERS'=> Array(					
	),
	'USER_PARAMETERS'=> Array(
		'TASK_TYPE_LIST' => Array(
			'NAME' => GetMessage('GD_CRM_TASK_ENTITY_TYPE'),
			'TYPE' => 'LIST',
			'VALUES' => array(
				'' => '',
				'LEAD' => GetMessage('GD_CRM_TASK_TYPE_LEAD'), 
				'CONTACT' => GetMessage('GD_CRM_TASK_TYPE_CONTACT'), 
				'COMPANY' => GetMessage('GD_CRM_TASK_TYPE_COMPANY'), 
				'DEAL' => GetMessage('GD_CRM_TASK_TYPE_DEAL')
			),
			'DEFAULT' => ''		
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
				'CREATED_DATE' => GetMessage('GD_CRM_COLUMN_CREATED_DATE'), 
				'CHANGED_DATE' => GetMessage('GD_CRM_COLUMN_CHANGED_DATE'), 
				'DATE_START' => GetMessage('GD_CRM_COLUMN_DATE_START'),
				'CLOSED_DATE' => GetMessage('GD_CRM_COLUMN_CLOSED_DATE')							
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
		'TASK_COUNT' => Array(
			'NAME' => GetMessage('GD_CRM_TASK_LIST_EVENT_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 5
		)		
	)
);

?>
