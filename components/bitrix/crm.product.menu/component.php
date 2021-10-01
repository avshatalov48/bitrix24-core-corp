<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global CDatabase $DB
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog;
use Bitrix\Crm;

if (!Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!Loader::includeModule('iblock'))
{
	ShowError(GetMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arResult['LIST_SECTION_ID'] =
	isset($_REQUEST['list_section_id'])
		? intval($_REQUEST['list_section_id'])
		: (isset($arParams['SECTION_ID']) ? intval($arParams['SECTION_ID']) : 0);

$isCopyMode = $arResult['IS_COPY_MODE'] = (isset($_REQUEST['copy']) && !empty($_REQUEST['copy']));

$arParams['CATALOG_ID'] = (int)($arParams['CATALOG_ID'] ?? 0);
$arParams['PRODUCT_COUNT'] = isset($arParams['PRODUCT_COUNT']) ? (int)$arParams['PRODUCT_COUNT'] : 20;
$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '?#section_id#');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], '?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], '?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_IMPORT'] = CrmCheckPath('PATH_TO_PRODUCT_IMPORT', $arParams['PATH_TO_PRODUCT_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_SECTION_LIST'] = CrmCheckPath('PATH_TO_SECTION_LIST', $arParams['PATH_TO_SECTION_LIST'], '?#section_id#&sections');
$arParams['PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
	'PATH_TO_PRODUCT_FILE', $arParams['~PATH_TO_PRODUCT_FILE'],
	$APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$sectionID = (int)($arParams['SECTION_ID'] ?? 0);
$productID = (int)($arParams['PRODUCT_ID'] ?? 0);

$CrmPerms = new CCrmPerms($USER->GetID());

$productAdd = $sectionAdd = $productEdit = $productCopy = $productDelete = $bImport = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
$productShow = $sectionShow = $permToExport = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');

if ($productAdd)
{
	if (Loader::includeModule('catalog'))
	{
		$productLimit = Catalog\Config\State::getExceedingProductLimit($arParams['CATALOG_ID']);
	}
	else
	{
		$productLimit = Crm\Config\State::getExceedingProductLimit($arParams['CATALOG_ID']);
	}
	$productAdd = empty($productLimit);
}

$exists = $productID > 0 && CCrmProduct::Exists($productID);

if($arParams['TYPE'] === 'sections')
{
	$arSection = false;

	if($sectionShow && $sectionID > 0)
	{
		$rsSection = CIBlockSection::GetList(
			array(),
			array(
				//'IBLOCK_ID' => ,
				'ID' => $sectionID,
				/*'GLOBAL_ACTIVE'=>'Y',*/
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			array('ID', 'IBLOCK_SECTION_ID')
		);

		$arSection = $rsSection->Fetch();
	}

	if($arSection)
	{
		$parentSectionID = isset($arSection['IBLOCK_SECTION_ID']) ? intval($arSection['IBLOCK_SECTION_ID']) : 0;

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_MOVE_UP'),
			'TITLE' => GetMessage('CRM_MOVE_UP_TITLE'),
			'LINK' =>  CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_SECTION_LIST'],
				array('section_id' => $parentSectionID)
			),
			'ICON' => 'btn-parent-section',
		);
	}

	if($sectionAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_ADD_PRODUCT_SECTION'),
			'TITLE' => GetMessage('CRM_ADD_PRODUCT_SECTION_TITLE'),
			'LINK' => "javascript:BX.CrmProductSectionManager.getDefault().addSection();",
			'ICON' => 'btn-add-section',
		);
	}

	if($productShow)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_LIST'),
			'TITLE' => GetMessage('CRM_PRODUCT_LIST_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_LIST'],
				array('section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0)
			),
			'ICON' => 'btn-list'
		);
	}
}
else
{
//	if ($arParams['TYPE'] === 'list')
//	{
//		if($arSection)
//		{
//			$parentSectionID = isset($arSection['IBLOCK_SECTION_ID']) ? intval($arSection['IBLOCK_SECTION_ID']) : 0;
//
//			$arResult['BUTTONS'][] = array(
//				'TEXT' => GetMessage('CRM_MOVE_UP'),
//				'TITLE' => GetMessage('CRM_MOVE_UP_TITLE'),
//				'LINK' =>  CComponentEngine::MakePathFromTemplate(
//					$arParams['PATH_TO_PRODUCT_LIST'],
//					array('section_id' => $parentSectionID)
//				),
//				'ICON' => 'btn-parent-section',
//			);
//		}
//	}

	if ($arParams['TYPE'] !== 'list')
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_LIST'),
			'TITLE' => GetMessage('CRM_PRODUCT_LIST_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_LIST'],
				array('section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0)
			),
			'ICON' => 'btn-list'
		);
	}

	if ($productAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_ADD'),
			'TITLE' => GetMessage('CRM_PRODUCT_ADD_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_EDIT'],
					array('product_id' => 0)
				),
				array('list_section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0)
			),
			'ICON' => 'btn-new'
		);
	}

	if ($productEdit && $arParams['TYPE'] == 'show' && $exists && !$isCopyMode)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_EDIT'),
			'TITLE' => GetMessage('CRM_PRODUCT_EDIT_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_EDIT'],
					array('product_id' => $productID)
				),
				array('list_section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0)
			),
			'ICON' => 'btn-edit'
		);
	}

	if ($productShow && $arParams['TYPE'] == 'edit' && $exists && !$isCopyMode)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_SHOW'),
			'TITLE' => GetMessage('CRM_PRODUCT_SHOW_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_SHOW'],
					array('product_id' => $productID)
				),
				array('list_section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0)
			),
			'ICON' => 'btn-view'
		);
	}

	if ($productCopy && !$isCopyMode && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_COPY'),
			'TITLE' => GetMessage('CRM_PRODUCT_COPY_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_EDIT'],
					array('product_id' => $productID)
				),
				array(
					'list_section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0,
					'copy' => 1
				)
			),
			'ICON' => 'btn-copy'
		);
	}

	if ($productDelete && !$isCopyMode && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_DELETE'),
			'TITLE' => GetMessage('CRM_PRODUCT_DELETE_TITLE'),
			'LINK' => "javascript:product_delete('".GetMessage('CRM_PRODUCT_DELETE_DLG_TITLE')."', '".
				GetMessage('CRM_PRODUCT_DELETE_DLG_MESSAGE')."', '".
				GetMessage('CRM_PRODUCT_DELETE_DLG_BTNTITLE')."', '".
				CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_PRODUCT_EDIT'],
						array(
							'product_id' => $productID
						)
					),
					array(
						'delete' => '',
						'list_section_id' =>
							isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0,
						'sessid' => bitrix_sessid()
					)
				)."')",
			'ICON' => 'btn-delete'
		);
	}

	/*if($sectionShow)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_SECTIONS'),
			'TITLE' => GetMessage('CRM_PRODUCT_SECTIONS_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_SECTION_LIST'],
				array('section_id' => $sectionID)
			),
			'ICON' => 'btn-edit-sections'
		);
	}*/

	if($sectionAdd && $arParams['TYPE'] === 'list')
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_ADD_PRODUCT_SECTION'),
			'TITLE' => GetMessage('CRM_ADD_PRODUCT_SECTION_TITLE'),
			'LINK' => "javascript:BX.CrmProductSectionManager.getDefault().addSection();",
			'ICON' => 'btn-add-section',
		);
	}

	// import
	if ($bImport && $arParams['TYPE'] === 'list')
	{
		$arResult['BUTTONS'][] = array('NEWBAR' => true);
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_IMPORT'),
			'TITLE' => GetMessage('CRM_PRODUCT_IMPORT_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_IMPORT'], array()),
				array(
					'list_section_id' => isset($arResult['LIST_SECTION_ID']) ? intval($arResult['LIST_SECTION_ID']) : 0
				)
			),
			'ICON' => 'btn-crm-product-import'
		);
	}

	if ($permToExport && $arParams['TYPE'] === 'list')
	{
		if($bImport)
		{
			$arResult['BUTTONS'][] = array('SEPARATOR' => true);
		}

		$entityType = 'PRODUCT';
		$stExportId = 'EXPORT_'.$entityType;
		$componentName = 'bitrix:crm.product.list';

		$arResult['EXPORT_CSV_PARAMS'] = [
			'id' => $stExportId. '_CSV',
			'controller' => 'bitrix:crm.api.export',
			'queue' => [
				[
					'action' => 'dispatcher',
				],
			],
			'params' => [
				'SITE_ID' => SITE_ID,
				'ENTITY_TYPE' => $entityType,
				'EXPORT_TYPE' => 'csv',
				'COMPONENT_NAME' => $componentName,
				'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
					$componentName,
					array(
						'CATALOG_ID' => $arParams['CATALOG_ID'],
						'SECTION_ID' => $arParams['SECTION_ID'],
						'PRODUCT_COUNT' => $arParams['PRODUCT_COUNT'],
						'PATH_TO_INDEX' => $arParams['PATH_TO_INDEX'],
						'PATH_TO_PRODUCT_LIST' => $arParams['PATH_TO_PRODUCT_LIST'],
						'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],
						'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
						'PATH_TO_PRODUCT_FILE' => $arParams['PATH_TO_PRODUCT_FILE'],
						'PATH_TO_SECTION_LIST' => $arParams['PATH_TO_SECTION_LIST']
					)
				),
			],
			'optionsFields' => array(
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('CRM_PRODUCT_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
				'INCLUDE_SUBSECTIONS' => array(
					'name' => 'INCLUDE_SUBSECTIONS',
					'type' => 'checkbox',
					'title' => Loc::getMessage('CRM_PRODUCT_EXPORT_INCLUDE_SUBSECTIONS'),
					'value' => 'N'
				)
			),
			'messages' => array(
				'DialogTitle' => Loc::getMessage('CRM_PRODUCT_EXPORT_CSV_TITLE'),
				'DialogSummary' => Loc::getMessage('CRM_PRODUCT_STEXPORT_SUMMARY'),
			),
			'dialogMaxWidth' => 650,
		];

		// clone params for excel export
		$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
		$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
		$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
		$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('CRM_PRODUCT_EXPORT_EXCEL_TITLE');


		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('CRM_PRODUCT_EXPORT_CSV_TITLE'),
			'TEXT' => Loc::getMessage('CRM_PRODUCT_EXPORT_CSV'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
			'ICON' => 'btn-crm-product-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => Loc::getMessage('CRM_PRODUCT_EXPORT_EXCEL_TITLE'),
			'TEXT' => Loc::getMessage('CRM_PRODUCT_EXPORT_EXCEL'),
			'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
			'ICON' => 'btn-crm-product-export'
		);

		unset($entityType, $stExportId, $randomSequence, $stExportManagerId);
	}
}

$this->IncludeComponentTemplate();
