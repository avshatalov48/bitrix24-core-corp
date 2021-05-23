<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule('crm'))
	return false;
$arComponentParameters = Array(
	'PARAMETERS' => array(	
		'ENTITY_TYPE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_ENTITY_TYPE'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'N',
			'DEFAULT' => '',
			'VALUES' =>Array(
				''=>'',
				'INVOICE'=>GetMessage('CRM_ENTITY_TYPE_INVOICE')
			)
		),
		'ENTITY_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_ENTITY_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '',
		),	
		'EVENT_COUNT' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_EVENT_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20',
		),
//		'EVENT_ENTITY_LINK' => Array(
//			'PARENT' => 'BASE',
//			'NAME' => GetMessage('CRM_EVENT_ENTITY_LINK'),
//			'TYPE' => 'LIST',
//			'MULTIPLE' => 'N',
//			'DEFAULT' => 'N',
//			'VALUES' =>Array(
//				'Y'=>GetMessage('MAIN_YES'),
//				'N'=>GetMessage('MAIN_NO')
//			)
//		),
		"NAME_TEMPLATE" => array(
			"TYPE" => "LIST",
			"NAME" => GetMessage("CRM_NAME_TEMPLATE"),
			"VALUES" => CComponentUtil::GetDefaultNameTemplates(),
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),						
	)	
);
?>