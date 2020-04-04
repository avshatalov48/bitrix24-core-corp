<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!CModule::IncludeModule('crm'))
{
	return false;
}

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

$arComponentParameters = array(
	'GROUPS' => array(),
	'PARAMETERS' => array(
		/*'CATALOG_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_CATALOG_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arCatalogs,
			'REFRESH' => 'Y'
		),*/
		'VARIABLE_ALIASES' => array(
			'product_id' => array(
				'NAME' => GetMessage('CRM_PRODUCT_ID_PARAM'),
				'DEFAULT' => 'product_id'
			),
			'section_id' => array(
				'NAME' => GetMessage('CRM_SECTION_ID_PARAM'),
				'DEFAULT' => 'section_id'
			),
			'field_id' => array(
				'NAME' => GetMessage('CRM_FIELD_ID_PARAM'),
				'DEFAULT' => 'field_id'
			),
			'file_id' => array(
				'NAME' => GetMessage('CRM_FILE_ID_PARAM'),
				'DEFAULT' => 'file_id'
			)
		),
		'SEF_MODE' => Array(
			'index' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_INDEX'),
				'DEFAULT' => 'index.php',
				'VARIABLES' => array()
			),
			'product_list' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_PRODUCT_LIST'),
				'DEFAULT' => 'list/#section_id#/',
				'VARIABLES' => array('section_id')
			),
			'product_edit' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_PRODUCT_EDIT'),
				'DEFAULT' => 'edit/#product_id#/',
				'VARIABLES' => array('product_id')
			),
			'product_show' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_PRODUCT_SHOW'),
				'DEFAULT' => 'show/#product_id#/',
				'VARIABLES' => array('product_id')
			),
			'section_list' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_SECTION_LIST'),
				'DEFAULT' => 'section_list/#section_id#/',
				'VARIABLES' => array('section_id')
			),
			'product_file' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_PRODUCT_FILE'),
				'DEFAULT' => 'file/#product_id#/#field_id#/#file_id#/',
				'VARIABLES' => array('product_id', 'field_id', 'file_id')
			)
		)
	)
);


?>