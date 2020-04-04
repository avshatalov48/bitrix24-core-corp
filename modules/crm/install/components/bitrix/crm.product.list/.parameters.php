<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

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

$arSections = array();
$catalogID = isset($arCurrentValues['CATALOG_ID']) ? intval($arCurrentValues['CATALOG_ID']) : 0;
if($catalogID > 0)
{
	$arSections['0'] = GetMessage('CRM_SECTION_NOT_SELECTED');

	$rsSections = CIBlockSection::GetList(
		array('left_margin' => 'asc'),
		array(
			'IBLOCK_ID' => $catalogID,
			/*'GLOBAL_ACTIVE' => 'Y',*/
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array(
			'ID',
			'NAME',
			'DEPTH_LEVEL'
		)
	);

	while($arSection = $rsSections->GetNext())
	{
		$sectionID = $arSection['ID'];
		$arSections[$sectionID] = str_repeat(' . ', $arSection['DEPTH_LEVEL']).'['.$sectionID.'] '.$arSection['NAME'];
	}
}

$arComponentParameters = Array(
	'PARAMETERS' => array(	
		'PRODUCT_COUNT' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_PRODUCT_COUNT'),
			'TYPE' => 'STRING',
			'DEFAULT' => '20'
		),
		'CATALOG_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_CATALOG_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arCatalogs,
			'REFRESH' => 'Y'
		),
		'SECTION_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_SECTION_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arSections,
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
		)
	)	
);