<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

//$arComponentProps = CComponentUtil::GetComponentProps('bitrix:crm.lead.list', $arCurrentValues);

$arParameters = Array(
	'PARAMETERS'=> Array(						
	),
	'USER_PARAMETERS'=> Array(
		'EVENT_TYPE_LIST' => Array(
			'NAME' => GetMessage('GD_CRM_EVENT_TYPE'),
			'TYPE' => 'LIST',
			'VALUES' => array(
				'' => '',
				'LEAD' => GetMessage('GD_CRM_EVENT_TYPE_LEAD'), 
				'CONTACT' => GetMessage('GD_CRM_EVENT_TYPE_CONTACT'), 
				'COMPANY' => GetMessage('GD_CRM_EVENT_TYPE_COMPANY'), 
				'DEAL' => GetMessage('GD_CRM_EVENT_TYPE_DEAL')
			),
			'DEFAULT' => ''		
		),
		'EVENT_COUNT' => Array(
			'NAME' => GetMessage('GD_CRM_EVENT_LIST_EVENT_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => 5
		)		
	)
);

?>
