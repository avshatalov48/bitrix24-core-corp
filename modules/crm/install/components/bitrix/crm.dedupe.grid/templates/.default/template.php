<?php

use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */
Bitrix\Main\UI\Extension::load("ui.icons.b24");
Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/crm-entity-show.css");

$arResult['ROWS'] = [];
foreach($arResult['ROW_DATA'] as $rowItem)
{
	$entityID = $rowItem['ENTITY_ID'];
	$rootEntityID = $rowItem['ROOT_ENTITY_ID'];

	$entityInfo = isset($arResult['ENTITY_INFOS'][$entityID]) ? $arResult['ENTITY_INFOS'][$entityID] : array();

	$multiFieldEntityTypes = CCrmFieldMulti::GetEntityTypes();
	$columns = [];
	foreach($arResult['HEADERS'] as $header)
	{
		$colName = $header['id'];
		if($colName === 'ORGANIZATION' || $colName === 'PERSON')
		{
			$imageUrl = '';
			$imageID = isset($entityInfo['IMAGE_FILE_ID']) ? $entityInfo['IMAGE_FILE_ID'] : 0;
			if($imageID > 0)
			{
				$imageInfo = CFile::ResizeImageGet(
					$imageID,
					array('width' => 50, 'height' => 50),
					BX_RESIZE_IMAGE_EXACT
				);
				$imageUrl = $imageInfo['src'];
			}

			$columns[$colName] = '<div class="crm-dedupe-grid-user"><span class="ui-icon ui-icon-common-user crm-dedupe-grid-user-icon">'
				.($imageUrl !== '' ? '<img class="" src="'. Uri::urnEncode(htmlspecialcharsbx($imageUrl)).'"/>' : '<i></i>')
				.'</span><a class="crm-dedupe-grid-user-name" href="'
				.htmlspecialcharsbx($entityInfo['SHOW_URL'])
				.'">'.htmlspecialcharsbx($entityInfo['TITLE'])
				.'</a></div>';
		}
		elseif($colName === 'MATCH')
		{
			$columns[$colName] = htmlspecialcharsbx($rowItem['MATCH_TEXT']);
		}
		elseif($colName === 'RESPONSIBLE')
		{
			$entityResponsibleName = isset($entityInfo['RESPONSIBLE_FULL_NAME']) ? $entityInfo['RESPONSIBLE_FULL_NAME'] : '';
			$entityResponsibleUrl = isset($entityInfo['RESPONSIBLE_URL']) ? $entityInfo['RESPONSIBLE_URL'] : '';
			if($entityResponsibleName !== '')
			{
				$entityResponsiblePhotoUrl = isset($entityInfo['RESPONSIBLE_PHOTO_URL']) ? $entityInfo['RESPONSIBLE_PHOTO_URL'] : '';
				$columns[$colName] = '<div class="crm-dedupe-grid-user crm-dedupe-grid-user-responsible">'
					.'<span class="ui-icon ui-icon-common-user crm-dedupe-grid-user-icon">'
					.($entityResponsiblePhotoUrl !== '' ? '<img class="" src="'. Uri::urnEncode(htmlspecialcharsbx($entityResponsiblePhotoUrl)).'"/>' : '<i></i>')
					.'</span><a href="'.htmlspecialcharsbx($entityResponsibleUrl) .'" target="_blank" class="crm-dedupe-grid-user-name">'
					.htmlspecialcharsbx($entityResponsibleName).'</a></div>';
			}
		}
		elseif($colName === 'PHONE' || $colName === 'EMAIL')
		{
			$columns[$colName] = \CCrmViewHelper::RenderListMultiField(
				$entityInfo,
				$colName,
				$arResult['ENTITY_TYPE_NAME'].'_'.$entityID.GetRandomCode(4),
				$multiFieldEntityTypes
			);
		}
	}

	$row = [
		'id' => $rowItem['ID'],
		'data' => $rowItem,
		'has_child' => $rootEntityID === $entityID,
		'parent_id' => $rootEntityID !== $entityID ? $rowItem['GROUP_KEY'] : '',
		'editable' => 'N',
		'columns' => $columns
	];
	$arResult['ROWS'][] = $row;
}

?>

<div class="crm-dedupe-grid-container">
<?php
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['ROWS'],
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'NAV_PARAMS' => $arResult['NAV_PARAMS'],
		'NAV_PARAM_NAME' => $arResult['NAV_PARAM_NAME'],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'PRESERVE_HISTORY' => false,
		'ENABLE_COLLAPSIBLE_ROWS' => true,
		'SHOW_PAGESIZE' => true,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
		'PAGE_SIZES' => [
			[ 'NAME' => '5', 'VALUE' => '5' ],
			[ 'NAME' => '10', 'VALUE' => '10' ],
			[ 'NAME' => '20', 'VALUE' => '20' ],
			[ 'NAME' => '50', 'VALUE' => '50' ],
			[ 'NAME' => '100', 'VALUE' => '100' ]
		],
	]
);
?>
</div>
