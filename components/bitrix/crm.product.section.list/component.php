<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_ADD'] = $arResult['CAN_EDIT'] = $arResult['CAN_DELETE'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_SECTION_LIST'] = CrmCheckPath('PATH_TO_SECTION_LIST', $arParams['PATH_TO_SECTION_LIST'], '?section_id=#section_id#');
$sectionParam = isset($arParams['SECTION_ID_PARAM']) && !empty($arParams['SECTION_ID_PARAM']) ? $arParams['SECTION_ID_PARAM'] : 'section_id';

$catalogID = $arResult['CATALOG_ID'] = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;
if($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
	$arResult['CATALOG_ID'] = $catalogID;
}

$sectionID = 0;
if($this->GetParent())
{
	$sectionID = isset($arParams['SECTION_ID']) ? intval($arParams['SECTION_ID']) : 0;
}
else
{
	if(isset($_REQUEST[$sectionParam]))
	{
		$sectionID = intval($_REQUEST[$sectionParam]);
	}
	elseif(isset($arParams['SECTION_ID']) && !empty($arParams['SECTION_ID']))
	{
		$sectionID = intval($arParams['SECTION_ID']);
	}
}

$arResult['SECTION_ID'] = $sectionID;

$parentSectionID = 0;
if($sectionID > 0)
{
	$rsSection = CIBlockSection::GetList(
		array(),
		array(
			'IBLOCK_ID' => $catalogID,
			'ID' => $sectionID,
			/*'GLOBAL_ACTIVE'=>'Y',*/
			'CHECK_PERMISSIONS' => 'N'
		)
	);
	$arResult['SECTION'] = $rsSection->GetNext();
}
else
{
	$arResult['SECTION'] = false;
}

if($arResult['SECTION'])
{
	$parentSectionID = $arResult['PARENT_SECTION_ID'] = $arResult['SECTION']['IBLOCK_SECTION_ID'];
	$arResult['SECTION_PATH'] = array();
	$rsPath = CIBlockSection::GetNavChain($arResult['IBLOCK_ID'], $arResult['SECTION_ID']);
	while($arPath = $rsPath->Fetch())
	{
		$arResult['SECTION_PATH'][] = array(
			'NAME' => htmlspecialcharsbx($arPath['NAME']),
			'URL' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_SECTION_LIST'],
				array($sectionParam => $arPath['ID'])
			)
		);
	}
}
else
{
	$sectionID = $arResult['SECTION_ID'] = 0;
	$parentSectionID = $arResult['PARENT_SECTION_ID'] = 0;
}

$arResult['~SECTION_LIST_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_SECTION_LIST'],
	array($sectionParam => $sectionID)
);

$arResult['SECTION_LIST_URL'] = htmlspecialcharsbx($arResult['~SECTION_LIST_URL']);

$arResult['~PARENT_SECTION_LIST_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_SECTION_LIST'],
	array($sectionParam => $parentSectionID)
);

$arResult['PARENT_SECTION_LIST_URL'] = htmlspecialcharsbx($arResult['~PARENT_SECTION_LIST_URL']);

$arResult['GRID_ID'] = 'CRM_PRODUCT_SECTION_LIST';

//Processing of POST
if($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid())
{
	$action = isset($_POST['action']) ? $_POST['action'] : '';
	if($action === 'ADD' && $arResult['CAN_ADD'])
	{
		$sectionName = trim(isset($_POST['sectionName']) ? $_POST['sectionName'] : '', " \n\r\t");
		if(isset($sectionName[0]))
		{
			$section = new CIBlockSection();
			$section->Add(
				array(
					'IBLOCK_ID' => $catalogID,
					'NAME' => $sectionName,
					'IBLOCK_SECTION_ID' => $sectionID,
					'CHECK_PERMISSIONS' => 'N',
				)
			);
			if (!isset($_GET['AJAX_CALL']))
			{
				LocalRedirect($arResult['SECTION_LIST_URL']);
			}
		}
	}
	elseif($action === 'RENAME' && $arResult['CAN_EDIT'])
	{
		$renamedSectionID = isset($_POST['sectionID']) ? intval($_POST['sectionID']) : 0;
		$newSectionName = trim(isset($_POST['sectionName']) ? $_POST['sectionName'] : '', " \n\r\t");
		if($renamedSectionID > 0 && isset($newSectionName[0]))
		{
			$rsSections = CIBlockSection::GetList(
				array(),
				array(
					'IBLOCK_ID' => $catalogID,
					'ID' => $renamedSectionID,
					/*'GLOBAL_ACTIVE' => 'Y',*/
					'CHECK_PERMISSIONS' => 'N'
				)
			);
			if($rsSections->Fetch())
			{
				$section = new CIBlockSection();
				$section->Update(
					$renamedSectionID,
					array(
						'IBLOCK_ID' => $catalogID,
						'NAME' => $newSectionName,
					)
				);

				if (!isset($_GET['AJAX_CALL']))
				{
					LocalRedirect($arResult['SECTION_LIST_URL']);
				}
			}
		}
	}

	$gridActionName = 'action_button_'.$arResult['GRID_ID'];
	$gridAction = isset($_POST[$gridActionName]) ? $_POST[$gridActionName] : '';
	if($gridAction === 'delete' && $arResult['CAN_DELETE'])
	{
		$arIDs = isset($_POST['ID']) && is_array($_POST['ID']) ? $_POST['ID'] : array();
		if(!empty($arIDs))
		{
			$dbSection = CIBlockSection::GetList(
				array(),
				array(
					'IBLOCK_ID' => $catalogID,
					'ID' => $arIDs,
					/*'GLOBAL_ACTIVE'=>'Y',*/
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('ID')
			);

			$section = new CCrmProductSection();
			while($arSection = $dbSection->Fetch())
			{
				$section->Delete($arSection['ID']);
			}
		}

		if (!isset($_GET['AJAX_CALL']))
		{
			LocalRedirect($arResult['SECTION_LIST_URL']);
		}
	}
	elseif($gridAction === 'edit' && $arResult['CAN_EDIT'])
	{
		$section = new CIBlockSection();
		$arFields = isset($_POST['FIELDS']) && is_array($_POST['FIELDS']) ? $_POST['FIELDS'] : array();

		$arIDs = array_keys($arFields);
		if(!empty($arIDs))
		{
			$dbSection = CIBlockSection::GetList(
				array(),
				array(
					'IBLOCK_ID' => $catalogID,
					'ID' => $arIDs,
					/*'GLOBAL_ACTIVE'=>'Y',*/
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('ID')
			);

			$section = new CIBlockSection();
			while($arSection = $dbSection->Fetch())
			{
				$sectionID = $arSection['ID'];
				$arSectionFields = isset($arFields[$sectionID])
					? $arFields[$sectionID] : null;

				// Is only one field allowed: 'NAME'.
				if(!(is_array($arSectionFields) && isset($arSectionFields['NAME'])))
				{
					continue;
				}

				$section->Update(
					$sectionID,
					array(
						'NAME' => $arSectionFields['NAME'],
						'CHECK_PERMISSIONS' => 'N'
					)
				);
			}
		}

		if (!isset($_GET['AJAX_CALL']))
		{
			LocalRedirect($arResult['SECTION_LIST_URL']);
		}
	}
}
// Preparing of grid data
$gridOptions = new CGridOptions($arResult['GRID_ID']);

$arFilter = array(
	'IBLOCK_ID' => $catalogID,
	/*'GLOBAL_ACTIVE' => 'Y',*/
	'CHECK_PERMISSIONS' => 'N',
	'SECTION_ID' => $sectionID
);

$rsSections = CIBlockSection::GetList(
	array('left_margin' => 'asc'),
	$arFilter
);

$arNav = $gridOptions->GetNavParams(
	array(
		'nPageSize' => isset($arParams['SECTION_COUNT']) ? intval($arParams['SECTION_COUNT']) : 20
	)
);

$rsSections->NavStart($arNav['nPageSize']);
$arResult['SECTIONS'] = array();
while($arSection = $rsSections->GetNext())
{
	$arResult['SECTIONS'][] = $arSection;
}

$rsSections->bShowAll = false;
$arResult['NAV_OBJECT'] = $rsSections;

$this->IncludeComponentTemplate();
