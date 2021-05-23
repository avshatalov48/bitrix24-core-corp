<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

InitBVar($arParams['SHOW_SECTION_INFO']);
InitBVar($arParams['SHOW_FROM_ROOT']);

if ($arParams['SHOW_FROM_ROOT'])
{
	$arParams['MAX_DEPTH'] = intval($arParams['MAX_DEPTH']);
	$arParams['MAX_DEPTH'] = $arParams['MAX_DEPTH'] >= 0 ? $arParams['MAX_DEPTH'] : 2;
}
else
{
	$arParams['MAX_DEPTH'] = 0;
}

$arParams['MAX_DEPTH_FIRST'] = intval($arParams['MAX_DEPTH_FIRST']);
$arParams['MAX_DEPTH_FIRST'] = $arParams['MAX_DEPTH_FIRST'] >= 0 ? $arParams['MAX_DEPTH_FIRST'] : 2;

$arParams['COLUMNS'] = intval($arParams['COLUMNS']);
if ($arParams['COLUMNS'] <= 0) $arParams['COLUMNS'] = 2;

$arParams['COLUMNS_FIRST'] = intval($arParams['COLUMNS_FIRST']);
if ($arParams['COLUMNS_FIRST'] <= 0) $arParams['COLUMNS_FIRST'] = 2;

if (!function_exists('intr_TruncateTree'))
{
	function intr_TruncateTreeRecursive(&$arTree, $current_level, $max_depth, $counter)
	{
		if ($arTree[$current_level])
		{
			foreach ($arTree[$current_level] as $arSection)
			{
				intr_TruncateTreeRecursive($arTree, $arSection['ID'], $max_depth, $counter+1);
			}

			if ($counter >= $max_depth)
			{
				unset($arTree[$current_level]);
			}
		}
	}

	function intr_TruncateTree($arTree, $max_depth)
	{
		$arNewTree = $arTree;

		intr_TruncateTreeRecursive($arNewTree, 0, $max_depth, 0);

		return $arNewTree;
	}
}

if (!function_exists('intr_GetSubTree'))
{
	function intr_FillTreeRecursive(&$arNewTree, $arTree, $top_section, $current_section = 0)
	{
		if ($arTree[$current_section ? $current_section : $top_section])
		{
			$arNewTree[$current_section] = $arTree[$current_section ? $current_section : $top_section];
			foreach ($arNewTree[$current_section] as $arSection)
			{
				intr_FillTreeRecursive($arNewTree, $arTree, $top_section, $arSection['ID']);
			}
		}
	}

	function intr_GetSubTree($arTree, $current_section)
	{
		$arNewTree = array();

		if ($arTree[$current_section])
		{
			$arNewTree = array(0 => $arTree[$current_section]);
			intr_FillTreeRecursive($arNewTree, $arTree, $current_section);
		}

		return $arNewTree;
	}
}


$current_section = intval($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT']);

if (!$current_section)
{
	$arParams['MAX_DEPTH'] = $arParams['MAX_DEPTH_FIRST'];
	$arParams['COLUMNS'] = $arParams['COLUMNS_FIRST'];
}

$arResult["UF_DEPARTMENT_field"]['SETTINGS']['ACTIVE_FILTER'] = 'Y';

$obEnum = new CUserTypeIBlockSection;
$rsEnum = $obEnum->GetList($arResult["UF_DEPARTMENT_field"]);

$arResult['SECTIONS'] = array();
$bChainFinished = false;
while ($arRes = $rsEnum->Fetch())
{
	$arRes['IBLOCK_SECTION_ID'] = intval($arRes['IBLOCK_SECTION_ID']);

	if ($current_section && !$bChainFinished)
	{
		$arResult['SECTIONS_CHAIN'][$arRes['DEPTH_LEVEL']-1] = array($arRes['ID'], $arRes['NAME']);
		if ($current_section == $arRes['ID'])
		{
			$bChainFinished = true;
			if (true || $arParams['SET_TITLE'] == 'Y')
				$GLOBALS['APPLICATION']->SetTitle($arRes['NAME']);
			$arResult['SECTIONS_CHAIN'] = array_slice($arResult['SECTIONS_CHAIN'], 0, $arRes['DEPTH_LEVEL']);
		}
	}

	if (!is_array($arResult['SECTIONS'][$arRes['IBLOCK_SECTION_ID']]))
		$arResult['SECTIONS'][$arRes['IBLOCK_SECTION_ID']] = array();

	$arResult['SECTIONS'][$arRes['IBLOCK_SECTION_ID']][] = $arRes;
}

if (!$bChainFinished)
{
	$current_section = $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] = 0;
	$arResult['SECTIONS_CHAIN'] = array();
}

if (!$current_section && count($arResult['SECTIONS'][0]) == 1)
{
	$current_section = $arResult['SECTIONS'][0][0]['ID'];
	$arResult['TOP_SECTION'] = $current_section;
}

if ($arParams['SHOW_SECTION_INFO'] == 'Y' && $current_section)
{
	$dbRes = CIBlockSection::GetByID($current_section);
	if ($arResult['SECTION_DATA'] = $dbRes->Fetch())
	{
		if ($arResult['SECTION_DATA']['DETAIL_PICTURE'])
			$arResult['SECTION_DATA']['DETAIL_PICTURE'] = CFile::ShowImage($arResult['SECTION_DATA']['DETAIL_PICTURE']);
		elseif ($arResult['SECTION_DATA']['PICTURE'])
			$arResult['SECTION_DATA']['DETAIL_PICTURE'] = CFile::ShowImage($arResult['SECTION_DATA']['PICTURE']);
	}
}

if ($arParams['SHOW_FROM_ROOT'] != 'Y' && $current_section)
{
	$arResult['SECTIONS'] = intr_GetSubTree($arResult['SECTIONS'], $current_section);
}

if ($arParams['MAX_DEPTH'] > 0)
{
	$arResult['SECTIONS'] = intr_TruncateTree($arResult['SECTIONS'], $arParams['MAX_DEPTH']);
}

$arRemove = array($arParams['FILTER_NAME'].'_UF_DEPARTMENT');
if (defined('BX_AJAX_PARAM_ID')) $arRemove[] = BX_AJAX_PARAM_ID;
if (is_array($arResult['SECTIONS_CHAIN']))
{
	foreach ($arResult['SECTIONS_CHAIN'] as $key => $arItem)
	{
		$arItem[2] = $GLOBALS['APPLICATION']->GetCurPageParam(
			$arParams['FILTER_NAME'].'_UF_DEPARTMENT='.$arItem[0],
			$arRemove
		);

		$GLOBALS['APPLICATION']->AddChainItem($arItem[1], $arItem[2]);
		$arResult['SECTIONS_CHAIN'][$key] = $arItem;
	}
}

$IBLOCK_PERMISSION = CIBlock::GetPermission(COption::GetOptionInt('intranet', 'iblock_structure'));
if ($arParams['bAdmin'] = $IBLOCK_PERMISSION >= 'U')
{
	$arParams['addUrl'] = "/bitrix/admin/iblock_section_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=intranet&type=".COption::GetOptionString('intranet', 'iblock_type')."&IBLOCK_ID=".COption::GetOptionInt('intranet', 'iblock_structure');

	if ($current_section > 0)
	{
		$arParams['editUrl'] = $arParams['addUrl']."&ID=".$current_section;
		$arParams['addUrl'] .= "&IBLOCK_SECTION_ID=".$current_section;
	}

	$arParams['editAllUrl'] = "/bitrix/admin/iblock_section_admin.php?lang=".LANGUAGE_ID."&type=".COption::GetOptionString('intranet', 'iblock_type')."&IBLOCK_ID=".COption::GetOptionInt('intranet', 'iblock_structure')."&find_section_section=".intval($current_section);
}

if ($GLOBALS['APPLICATION']->GetShowIncludeAreas() && $GLOBALS['USER']->IsAdmin())
{
	// define additional icons for Site Edit mode
	if ($current_section > 0)
	{
		$arIcons = array(
			array(
				'URL' => "javascript:".$GLOBALS['APPLICATION']->GetPopupLink(
					array(
					'URL' => "/bitrix/admin/iblock_section_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=intranet&type=".COption::GetOptionString('intranet', 'iblock_type')."&IBLOCK_ID=".COption::GetOptionInt('intranet', 'iblock_structure')."&ID=".$current_section."&back_url=".urlencode($_SERVER["REQUEST_URI"]),
						'PARAMS' => array(
							'width' => 700,
							'height' => 500,
							'resize' => false,
						)
					)
				),
				'ICON' => 'bx-context-toolbar-edit-icon',
				'TITLE' => GetMessage("INTR_ISS_TPL_SEC_ICON_EDIT"),
			),
		);
	}

	$arIcons[] = array(
			'URL' => "javascript:".$GLOBALS['APPLICATION']->GetPopupLink(
				array(
					'URL' => "/bitrix/admin/iblock_section_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=intranet&type=".COption::GetOptionString('intranet', 'iblock_type')."&IBLOCK_ID=".COption::GetOptionInt('intranet', 'iblock_structure')."&IBLOCK_SECTION_ID=".$current_section."&back_url=".urlencode($_SERVER["REQUEST_URI"]),
					'PARAMS' => array(
						'width' => 700,
						'height' => 500,
						'resize' => false,
					)
				)
			),
			'ICON' => 'bx-context-toolbar-edit-icon',
			'TITLE' => GetMessage("INTR_ISS_TPL_SEC_ICON_ADD"),
		);

	$this->__component->AddIncludeAreaIcons($arIcons);
}

if ($arParams['bAdmin']):
	global $INTRANET_TOOLBAR;

	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	CJSCore::Init(array('intranet_structure'));

	if (isset($_GET['structure_UF_DEPARTMENT']))
		$arStructureParams["IBLOCK_SECTION_ID"] = intval($_GET['structure_UF_DEPARTMENT']);  //parent department
	$arStructureParams["ACTION"] = "ADD";
	$INTRANET_TOOLBAR->AddButton(array(
		'ONCLICK' => "BX.IntranetStructure.ShowForm(".CUtil::PhpToJSObject($arStructureParams).")",
		"TEXT" => GetMessage('INTR_ABSC_TPL_ADD_ENTRY'),
		"ICON" => 'add',
		"SORT" => 1000,
	));

	if (intval($current_section) > 0)
	{
		$arStructureParams["ACTION"] = "EDIT";
		$arStructureParams["UF_DEPARTMENT_ID"] = isset($_GET['structure_UF_DEPARTMENT']) ? intval($_GET['structure_UF_DEPARTMENT']) : $arResult["TOP_SECTION"];
		$INTRANET_TOOLBAR->AddButton(array(
			'ONCLICK' => "BX.IntranetStructure.ShowForm(".CUtil::PhpToJSObject($arStructureParams).")",
			"TEXT" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRY'),
			"ICON" => 'edit',
			"SORT" => 1050,
		));
	}

	$INTRANET_TOOLBAR->AddButton(array(
		'HREF' => $arParams['editAllUrl'],
		"TEXT" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRIES'),
		'ICON' => 'settings',
		"SORT" => 1100,
	));
endif;
?>
