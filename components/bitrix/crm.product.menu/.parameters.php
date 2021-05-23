<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!CModule::IncludeModule('crm') || !CModule::IncludeModule('iblock'))
{
	return false;
}

global $APPLICATION;

$arComponentParameters = Array(
	'PARAMETERS' => array(
		'TYPE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PRODUCT_MENU_TYPE'),
			'TYPE' => 'LIST',
			'VALUES' =>
				array(
					'' => GetMessage('CRM_PRODUCT_MENU_TYPE_NOT_SELECTED'),
					'sections' => GetMessage('CRM_PRODUCT_MENU_TYPE_SECTIONS'),
					'list' => GetMessage('CRM_PRODUCT_MENU_TYPE_LIST'),
					'edit' => GetMessage('CRM_PRODUCT_MENU_TYPE_EDIT'),
					'show' => GetMessage('CRM_PRODUCT_MENU_TYPE_SHOW')
				)
		),
		'SECTION_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_SECTION_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		'PRODUCT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PRODUCT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		),
		'PATH_TO_PRODUCT_LIST' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PATH_TO_PRODUCT_LIST'),
			'TYPE' => 'STRING',
			'DEFAULT' => '?section_id=#section_id#'
		),
		'PATH_TO_PRODUCT_SHOW' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PATH_TO_PRODUCT_SHOW'),
			'TYPE' => 'STRING',
			'DEFAULT' => '?product_id=#product_id#&show'
		),
		'PATH_TO_PRODUCT_EDIT' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PATH_TO_PRODUCT_EDIT'),
			'TYPE' => 'STRING',
			'DEFAULT' => '?product_id=#product_id#&edit'
		),
		'PATH_TO_SECTION_LIST' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PATH_TO_SECTION_LIST'),
			'TYPE' => 'STRING',
			'DEFAULT' => '?section_id=#section_id#&sections'
		)
	)	
);