<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!CModule::IncludeModule('crm') || !CModule::IncludeModule('iblock'))
{
	return false;
}

global $APPLICATION;

$arCatalogs = array();
$arCatalogs['0'] = GetMessage('CRM_CATALOG_NOT_SELECTED');
$rsCatalogs = CCrmCatalog::GetList(
	array('NAME' => 'ASC'),
	array('ACTIVE' => 'Y'),
	array('ID', 'NAME')
);

while ($arCatalog = $rsCatalogs->Fetch())
{
	$catalogID = $arCatalog['ID'];
	$arCatalogs[$catalogID] = '['.$catalogID.'] '.$arCatalog['NAME'];
}

$arComponentParameters = Array(
	'PARAMETERS' => array(
		'CATALOG_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_CATALOG_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arCatalogs,
			'REFRESH' => 'Y'
		),
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
		)
	)	
);