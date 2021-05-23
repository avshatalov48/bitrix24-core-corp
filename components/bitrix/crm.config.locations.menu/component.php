<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Sale\Location;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_LOCATIONS_LIST'] = CrmCheckPath('PATH_TO_LOCATIONS_LIST', $arParams['PATH_TO_LOCATIONS_LIST'], '');
$arParams['PATH_TO_LOCATIONS_ADD'] = CrmCheckPath('PATH_TO_LOCATIONS_ADD', $arParams['PATH_TO_LOCATIONS_ADD'], '?add');
$arParams['PATH_TO_LOCATIONS_EDIT'] = CrmCheckPath('PATH_TO_LOCATIONS_EDIT', $arParams['PATH_TO_LOCATIONS_EDIT'], '?loc_id=#loc_id#&edit');
$arParams['PATH_TO_LOCATIONS_IMPORT'] = CrmCheckPath('PATH_TO_LOCATIONS_IMPORT', $arParams['PATH_TO_LOCATIONS_IMPORT'], '?import');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$locID = isset($arParams['LOC_ID']) ? strval($arParams['LOC_ID']) : '';

$CrmPerms = new CCrmPerms($USER->GetID());

$locAdd = $locEdit = $locDelete = $locImport = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

if(CSaleLocation::isLocationProMigrated())
	$exists = intval($locID > 0) && CCrmLocations::CheckLocationExists($locID);
else
	$exists = intval($locID > 0) && is_array(CCrmLocations::GetByID($locID));

########################
########################
########################

// LIST
if ($arParams['TYPE'] == 'list')
{
	$parentId = false;

	// STEP UP IN LIST
	if(CSaleLocation::isLocationProEnabled())
	{
		$filterParentId = false;
		if(isset($_REQUEST['PARENT_ID']))
			$filterParentId = intval($_REQUEST['PARENT_ID']);
		else
		{
			$gridOpts = new CCrmGridOptions('CRM_LOC_LIST');
			$filter = $gridOpts->GetFilter(array());

			if(isset($filter['PARENT_ID']))
				$filterParentId = intval($filter['PARENT_ID']);
		}

		if($filterParentId !== false)
		{
			$res = Location\LocationTable::getByID($filterParentId)->fetch();

			if(!!$res['ID'])
			{
				$parentId = intval($res['PARENT_ID']);

				$arResult['BUTTONS'][] = array(
					'TEXT' => GetMessage('CRM_LOC_STEP_UP'),
					'TITLE' => GetMessage('CRM_LOC_STEP_UP_TITLE'),
					'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LOCATIONS_LIST'], array()).'?PARENT_ID='.$parentId,
					'ICON' => 'btn-list'
				);
			}
		}
	}

	// ADD IN LIST
	if ($locAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_LOC_ADD'),
			'TITLE' => GetMessage('CRM_LOC_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LOCATIONS_ADD'],
				array()
			).($filterParentId ? '?PARENT_ID='.$filterParentId : ''),
			'ICON' => 'btn-new'
		);
	}

	// IMPORT IN LIST
	if($locImport)
	{
		if(CSaleLocation::isLocationProEnabled())
		{
			$link = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LOCATIONS_IMPORT'], array());
		}
		else
		{
			$link = "javascript:(new BX.CDialog({
								'content_url':'/bitrix/components/bitrix/crm.config.locations.import/box.php',
								'width':'540',
								'height':'275',
								'resizable':false })).Show();";
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_LOCATIONS_IMPORT'),
			'TITLE' => GetMessage('CRM_LOCATIONS_IMPORT_TITLE'),
			'LINK' => $link,

			'ICON' => 'btn-new'
		);
	}
}
elseif($arParams['TYPE'] == 'edit')
{
	$parentId = false;

	if(CSaleLocation::isLocationProEnabled())
	{
		if($locID)
		{
			$res = Location\LocationTable::getByID($locID)->fetch();

			if(!!$res['ID'])
				$parentId = $res['PARENT_ID'];
		}
		elseif(isset($_REQUEST['PARENT_ID']))
		{
			$parentId = intval($_REQUEST['PARENT_ID']);
		}
	}

	// GO TO PARENT LIST IN EDIT
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_LOC_LIST'),
		'TITLE' => GetMessage('CRM_LOC_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LOCATIONS_LIST'], array()),//.($parentId !== false ? '?PARENT_ID='.$parentId : ''),
		'ICON' => 'btn-list'
	);

	// ADD IN EDIT
	if ($locAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_LOC_ADD'),
			'TITLE' => GetMessage('CRM_LOC_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LOCATIONS_ADD'],
				array()
			).($parentId !== false ? '?PARENT_ID='.$parentId : ''),
			'ICON' => 'btn-new'
		);
	}
}
elseif($arParams['TYPE'] == 'show')
{

}

// GO TO LIST ROOT FROM IMPORT
if ($arParams['TYPE'] == 'import')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_LOC_LIST'),
		'TITLE' => GetMessage('CRM_LOC_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LOCATIONS_LIST'], array()),
		'ICON' => 'btn-list'
	);
}

if ($locEdit && $arParams['TYPE'] == 'show' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_LOC_EDIT'),
		'TITLE' => GetMessage('CRM_LOC_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_LOCATIONS_EDIT'],
			array('loc_id' => $locID)
		),
		'ICON' => 'btn-edit'
	);
}

// DELETE
if ($locDelete && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_LOC_DELETE'),
		'TITLE' => GetMessage('CRM_LOC_DELETE_TITLE'),
		'LINK' => "javascript:loc_delete('".GetMessage('CRM_LOC_DELETE_DLG_TITLE')."', '".GetMessage('CRM_LOC_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_LOC_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LOCATIONS_EDIT'],
				array('loc_id' => $locID)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>