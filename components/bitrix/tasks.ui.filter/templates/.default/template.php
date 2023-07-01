<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

$fltClass = $arParams['FILTER_CLASS'];

// get filter
$filter = $fltClass::getFilter();
$presets = $fltClass::getPresets();
$grid = $fltClass::getGrid();
$gridID = $fltClass::getGridId();
$gridFilter = $grid->GetFilter($filter);

// prepare custom fields
if (is_array($filter))
{
	$userSelectors = array();
	foreach ($filter as $filterItem)
	{
		// get only selector

		if (!(isset($filterItem['type'])
			&& $filterItem['type'] === 'custom_entity'
			&& isset($filterItem['selector'])
			&& is_array($filterItem['selector']))
		)
		{
			continue;
		}

		$selector = $filterItem['selector'];
		$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
		$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;

		if (!empty($selectorData) && $selectorType == 'user')
		{
			$userSelectors[] = $selectorData;
		}
	}

	// user selector
	if (!empty($userSelectors))
	{
		$componentName = "{$gridID}_FILTER_USER";
		$APPLICATION->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => strtolower($componentName),
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => SITE_DIR,
				'NAME_TEMPLATE' => $nameTemplate
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		?><script type="text/javascript"><?
		foreach ($userSelectors as $userSelector)
		{
			$selectorID = $userSelector['ID'];
			$fieldID = $userSelector['FIELD_ID'];
			?>BX.ready(
				function()
				{
					BX.FilterUserSelector.create(
						"<?=CUtil::JSEscape($selectorID)?>",
						{
							fieldId: "<?=CUtil::JSEscape($fieldID)?>",
							componentName: "<?=CUtil::JSEscape($componentName)?>"
						}
					);
				}
			);<?
		}
		?></script><?
	}
}

// filter
$viewID = isset($arParams['~RENDER_INTO_VIEW']) ? $arParams['~RENDER_INTO_VIEW'] : '';
if ($viewID === '')
{
	$viewID = 'inside_pagetitle';
}
$this->SetViewTarget($viewID, 0);
?><div class="pagetitle-container pagetitle-flexible-space"><?
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.filter',
	'',
	array(
		'GRID_ID' => $gridID,
		'FILTER_ID' => $gridID,
		'FILTER' => $filter,
		'FILTER_FIELDS' => $gridFilter,
		'FILTER_PRESETS' => $presets,
		'ENABLE_LIVE_SEARCH' => isset($arParams['~ENABLE_LIVE_SEARCH']) && $arParams['~ENABLE_LIVE_SEARCH'] === true,
		'ENABLE_LABEL' => true
	),
	$component,
	array('HIDE_ICONS' => true)
);
?></div><?
$this->EndViewTarget();