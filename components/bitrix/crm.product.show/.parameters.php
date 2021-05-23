<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!CModule::IncludeModule('crm') || !CModule::IncludeModule('iblock'))
{
	return false;
}

global $APPLICATION;

$arComponentParameters = Array(
	'PARAMETERS' => array(
		'PRODUCT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PRODUCT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		'PRODUCT_ID_PARAM' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PRODUCT_ID_PARAM'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'product_id'
		),
		'PATH_TO_PRODUCT_LIST' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PATH_TO_PRODUCT_LIST'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		)
	)	
);