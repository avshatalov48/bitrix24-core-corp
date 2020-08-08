<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '').' no-background no-all-paddings pagetitle-toolbar-field-view '
);
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";
?>

<?
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

//region FILTER

if (!$isBitrix24Template): ?>
<div class="recyclebin-interface-filter-container">
	<? endif ?>

	<div class="pagetitle-container pagetitle-flexible-space">
		<? $APPLICATION->IncludeComponent(
			"bitrix:main.ui.filter",
			"",
			array(
				"FILTER_ID" => $arParams['FILTER_ID'],
				"GRID_ID"   => $arParams["GRID_ID"],

				"FILTER"         => $arResult['FILTER']['FIELDS'],
				"FILTER_PRESETS" => $arResult['FILTER']['PRESETS'],

				"ENABLE_LABEL"          => true,
				'ENABLE_LIVE_SEARCH'    => $arParams['USE_LIVE_SEARCH'] == 'Y',
				'RESET_TO_DEFAULT_MODE' => true
			),
			$component,
			array("HIDE_ICONS" => true)
		); ?>
	</div>

	<? if (!$isBitrix24Template): ?>
</div>
<? endif ?>
<?php
//endregion

if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<?
if(\CModule::IncludeModule('bitrix24'))
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
	$APPLICATION->IncludeComponent(
		"bitrix:bitrix24.limit.lock",
		"",
		array(
//			"FEATURE_GROUP_NAME" => "recyclebin"
		)
	);
}
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'GRID_ID'   => $arParams['GRID_ID'],
		'HEADERS'   => $arResult['GRID']['HEADERS'],
		'SORT'      => isset($arParams['SORT']) ? $arParams['SORT'] : array(),
		'SORT_VARS' => isset($arParams['SORT_VARS']) ? $arParams['SORT_VARS'] : array(),
		'ROWS'      => $arResult['ROWS'],

		'AJAX_MODE'           => 'Y',
		//Strongly required
		"AJAX_OPTION_JUMP"    => "N",
		"AJAX_OPTION_STYLE"   => "N",
		"AJAX_OPTION_HISTORY" => "N",

		"ALLOW_COLUMNS_SORT"      => true,
		//					"ALLOW_ROWS_SORT"         => $arResult["CAN"]["SORT"],
		"ALLOW_COLUMNS_RESIZE"    => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT"              => true,
		"ALLOW_PIN_HEADER"        => true,
		"ACTION_PANEL"            => $arResult['GROUP_ACTIONS'],

		"SHOW_CHECK_ALL_CHECKBOXES" => true,
		"SHOW_ROW_CHECKBOXES"       => true,
		"SHOW_ROW_ACTIONS_MENU"     => true,
		"SHOW_GRID_SETTINGS_MENU"   => true,
		"SHOW_NAVIGATION_PANEL"     => true,
		"SHOW_PAGINATION"           => true,
		"SHOW_SELECTED_COUNTER"     => true,
		"SHOW_TOTAL_COUNTER"        => true,
		"SHOW_PAGESIZE"             => true,
		"SHOW_ACTION_PANEL"         => true,

		"MESSAGES" => $arResult['MESSAGES'],

		"ENABLE_COLLAPSIBLE_ROWS" => true,
		//		'ALLOW_SAVE_ROWS_STATE'=>true,

		"SHOW_MORE_BUTTON" => false,
		//		'~NAV_PARAMS' => $arResult['GET_LIST_PARAMS']['NAV_PARAMS'],
		'NAV_OBJECT'       => $arResult['NAV_OBJECT'],
		//		'NAV_STRING' => $arResult['NAV_STRING'],

		"TOTAL_ROWS_COUNT"  => $arResult['TOTAL_RECORD_COUNT'],
		//		"CURRENT_PAGE" => $arResult[ 'NAV' ]->getCurrentPage(),
		//		"ENABLE_NEXT_PAGE" => ($arResult[ 'NAV' ]->getPageSize() * $arResult[ 'NAV' ]->getCurrentPage()) < $arResult[ 'NAV' ]->getRecordCount(),
		"PAGE_SIZES"        => $arResult['PAGE_SIZES'],
		"DEFAULT_PAGE_SIZE" => 50
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>

<?php

$selectors = array();

foreach ($arResult['FILTER']['FIELDS'] as $filterItem)
{
	if (!(isset($filterItem['type']) &&
		  $filterItem['type'] === 'custom_entity' &&
		  isset($filterItem['selector']) &&
		  is_array($filterItem['selector'])))
	{
		continue;
	}

	$selector = $filterItem['selector'];
	$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
	$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;
	$selectorData['MODE'] = $selectorType;
	$selectorData['MULTI'] = $filterItem['params']['multiple'] && $filterItem['params']['multiple'] == 'Y';

	if (!empty($selectorData) && $selectorType == 'user')
	{
		$selectors[] = $selectorData;
	}
	if (!empty($selectorData) && $selectorType == 'group')
	{
		$selectors[] = $selectorData;
	}
}

if (!empty($selectors))
{
	\CUtil::initJSCore(
		array(
			'tasks_integration_socialnetwork'
		)
	);
}

if (!empty($selectors))
{
	?>
	<script type="text/javascript"><?
		foreach ($selectors as $groupSelector)
		{
		$selectorID = $groupSelector['ID'];
		$selectorMode = $groupSelector['MODE'];
		$fieldID = $groupSelector['FIELD_ID'];
		$multi = $groupSelector['MULTI'];
		?>BX.ready(
			function() {
				BX.FilterEntitySelector.create(
					"<?= \CUtil::JSEscape($selectorID)?>",
					{
						fieldId: "<?= \CUtil::JSEscape($fieldID)?>",
						mode: "<?= \CUtil::JSEscape($selectorMode)?>",
						multi: <?= $multi ? 'true' : 'false'?>
					}
				);
			}
		);<?
		}
		?></script><?
}
?>

<?php
$messages = [];
foreach($arResult['ENTITY_MESSAGES'] as $type=>$data)
{
	foreach($data['NOTIFY'] as $action => $message)
	{
		$messages[] = '"RECYCLEBIN_NOTIFY_'.$action.'_'.$type.'": "'.$message.'"';
	}
	foreach($data['CONFIRM'] as $action => $message)
	{
		$messages[] = '"RECYCLEBIN_CONFIRM_'.$action.'_'.$type.'": "'.$message.'"';
	}
}
?>

<script>
	BX.ready(function() {
		BX.message({
			'RECYCLEBIN_CONFIRM_RESTORE': "<?=Loc::getMessage('RECYCLEBIN_CONFIRM_RESTORE')?>",
			'RECYCLEBIN_CONFIRM_REMOVE': "<?=Loc::getMessage('RECYCLEBIN_CONFIRM_REMOVE')?>",
			'RECYCLEBIN_RESTORE_SUCCESS': "<?=Loc::getMessage('RECYCLEBIN_RESTORE_SUCCESS')?>",
			'RECYCLEBIN_DELETE_SUCCESS': "<?=Loc::getMessage('RECYCLEBIN_DELETE_SUCCESS')?>",
			'RECYCLEBIN_RESTORE_SUCCESS_MULTIPLE': "<?=Loc::getMessage('RECYCLEBIN_RESTORE_SUCCESS_MULTIPLE')?>",
			'RECYCLEBIN_DELETE_SUCCESS_MULTIPLE': "<?=Loc::getMessage('RECYCLEBIN_DELETE_SUCCESS_MULTIPLE')?>",
			<?=join(", \n", $messages)?>
		});

		BX.Recyclebin.List.gridId = '<?=$arParams['GRID_ID']?>';

	});
</script>