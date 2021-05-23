<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @var \Bitrix\Crm\PresetListComponent $component */
/** @global CMain $APPLICATION */
global $APPLICATION;

?><div class="crm-dup-control-type-info" style="margin-bottom: 10px; max-width: none;"><?
echo htmlspecialcharsbx(Loc::getMessage('CRM_PRESET_UFIELDS_NOTE'));
?></div><?

$toolbarButtons = array(
	array(
		'TEXT'=>Loc::getMessage('CRM_PRESET_UFIELDS_TOOLBAR_LIST'),
		'TITLE'=>Loc::getMessage('CRM_PRESET_UFIELDS_TOOLBAR_LIST_TITLE',
			array('#NAME#' => htmlspecialcharsbx($arResult['ENTITY_TYPE_NAME']))),
		'LINK'=>str_replace(
			array('#entity_type#'),
			array($arResult['ENTITY_TYPE_ID']),
			$arResult['PRESET_LIST_URL']
		),
		'ICON'=>'btn-view-elements'
	)
);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.toolbar',
	'',
	array(
		'BUTTONS' => $toolbarButtons
	),
	$component, array('HIDE_ICONS' => 'Y')
);

$rows = array();
foreach($arResult['LIST_DATA'] as $key => &$listRow)
{
	$row = array();
	foreach ($listRow as $fName => $fValue)
	{
		if(is_array($fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		elseif(preg_match("/[;&<>\"]/", $fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		else
			$row[$fName] = $fValue;
		$row["~".$fName] = $fValue;
	}
	$presetEditUrl = str_replace(
		array('#entity_type#', '#preset_id#'),
		array($arResult['ENTITY_TYPE_ID'], $key),
		$arResult['PRESET_EDIT_URL']
	);
	$gridDataItem = array(
		'id' => $key,
		'data' => $row,
		'actions' => array(
			array(
				'ICONCLASS' => 'delete',
				'TEXT' => Loc::getMessage('CRM_PRESET_UFIELDS_ACTION_MENU_DELETE'),
				'ONCLICK' => "javascript:bxGrid_".$arResult['GRID_ID'].".DeleteItem('".$key."', '".Loc::getMessage("CRM_PRESET_UFIELDS_ACTION_MENU_DELETE_CONF")."')"
			)
		),
		'columns' => array(),
		'editable' => array()
	);
	$rows[] = $gridDataItem;
	unset($gridDataItem);
}
unset($listRow);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $rows,
		'ACTIONS' => array('delete' => true),
		'ACTION_ALL_ROWS' => true,
		'FOOTER' => array(array('title' => Loc::getMessage('CRM_ALL'), 'value' => $arResult['ROWS_COUNT'])),
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_OPTION_SHADOW' => $arResult['AJAX_OPTION_SHADOW'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP']
	),
	$component, array('HIDE_ICONS' => 'Y')
);
