<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponent $component */

Loc::loadMessages(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '') . ' no-all-paddings pagetitle-toolbar-field-view '
);
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

\Bitrix\Main\UI\Extension::load('recyclebin.deletion-manager');
?>

<?php
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

//region FILTER

if (!$isBitrix24Template): ?>
<div class="recyclebin-interface-filter-container">
	<?php
	endif ?>

	<div class="pagetitle-container pagetitle-flexible-space">
		<?php
		$APPLICATION->IncludeComponent(
			"bitrix:main.ui.filter",
			"",
			[
				"FILTER_ID" => $arParams['FILTER_ID'],
				"GRID_ID" => $arParams["GRID_ID"],

				"FILTER" => $arResult['FILTER']['FIELDS'],
				"FILTER_PRESETS" => $arResult['FILTER']['PRESETS'],

				"ENABLE_LABEL" => true,
				'ENABLE_LIVE_SEARCH' => $arResult['FILTER']['USE_LIVE_SEARCH'] === 'Y',
				'RESET_TO_DEFAULT_MODE' => true,
				'THEME' => Bitrix\Main\UI\Filter\Theme::LIGHT,
			],
			$component,
			["HIDE_ICONS" => true]
		); ?>
	</div>

	<?php
	if (!$isBitrix24Template): ?>
</div>
<?php
endif ?>
<?php
//endregion
ob_start();
?>
<div class="recyclebin-list-page-nav">
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.pagenavigation',
		'grid',
		[
			'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);
	?>
</div>
<?php
$navigationHtml = ob_get_clean();
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<?php
if (CModule::IncludeModule('bitrix24'))
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
	$APPLICATION->IncludeComponent(
		"bitrix:bitrix24.limit.lock",
		"",
		[]
	);
}
?>
<div id="batchDeletionWrapper"></div>
<?php
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arParams['GRID_ID'],
		'HEADERS' => $arResult['GRID']['HEADERS'],
		'SORT' => $arParams['SORT'] ?? [],
		'SORT_VARS' => $arParams['SORT_VARS'] ?? [],
		'ROWS' => $arResult['ROWS'],

		'AJAX_MODE' => 'Y',
		//Strongly required
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "N",
		"AJAX_OPTION_HISTORY" => "N",

		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_COLUMNS_RESIZE" => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT" => true,
		"ALLOW_PIN_HEADER" => true,
		"ACTION_PANEL" => $arResult['GROUP_ACTIONS'],

		"SHOW_CHECK_ALL_CHECKBOXES" => true,
		"SHOW_ROW_CHECKBOXES" => true,
		"SHOW_ROW_ACTIONS_MENU" => true,
		"SHOW_GRID_SETTINGS_MENU" => true,
		"SHOW_NAVIGATION_PANEL" => true,
		"SHOW_PAGINATION" => true,
		"SHOW_SELECTED_COUNTER" => true,
		"SHOW_TOTAL_COUNTER" => false,
		"SHOW_PAGESIZE" => true,
		"SHOW_ACTION_PANEL" => true,

		"MESSAGES" => $arResult['MESSAGES'],

		"ENABLE_COLLAPSIBLE_ROWS" => true,
		"SHOW_MORE_BUTTON" => false,
		'NAV_STRING' => $navigationHtml,
		"PAGE_SIZES" => $arResult['PAGE_SIZES'],
		"DEFAULT_PAGE_SIZE" => 50,
	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>

<?php

$selectors = [];

foreach ($arResult['FILTER']['FIELDS'] as $filterItem)
{
	if (
		!(isset($filterItem['type'])
			&& $filterItem['type'] === 'custom_entity'
			&& isset($filterItem['selector'])
			&& is_array($filterItem['selector']))
	)
	{
		continue;
	}

	$selector = $filterItem['selector'];
	$selectorType = $selector['TYPE'] ?? '';
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
	CUtil::initJSCore(
		[
			'tasks_integration_socialnetwork',
		]
	);
}

if (!empty($selectors))
{
	?>
	<script><?php
		foreach ($selectors as $groupSelector)
		{
		$selectorID = $groupSelector['ID'];
		$selectorMode = $groupSelector['MODE'];
		$fieldID = $groupSelector['FIELD_ID'];
		$multi = $groupSelector['MULTI'];
		?>BX.ready(
			function() {
				BX.FilterEntitySelector.create(
					"<?= CUtil::JSEscape($selectorID)?>",
					{
						fieldId: "<?= CUtil::JSEscape($fieldID)?>",
						mode: "<?= CUtil::JSEscape($selectorMode)?>",
						multi: <?= $multi ? 'true' : 'false'?>,
					},
				);
			},
		);<?php
		}
		?></script><?php
}
?>

<?php
$messages = [];
foreach ($arResult['ENTITY_MESSAGES'] as $type => $data)
{
	foreach ($data['NOTIFY'] as $action => $message)
	{
		$messages[] = '"RECYCLEBIN_NOTIFY_' . $action . '_' . $type . '": "' . $message . '"';
	}
	foreach ($data['CONFIRM'] as $action => $message)
	{
		$messages[] = '"RECYCLEBIN_CONFIRM_' . $action . '_' . $type . '": "' . $message . '"';
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
			<?=implode(", \n", $messages)?>
		});

		const gridId = '<?= CUtil::JSEscape($arParams['GRID_ID']) ?>';
		const moduleId = '<?= CUtil::JSEscape($arParams['MODULE_ID']) ?>';
		BX.Recyclebin.List.gridId = gridId;

		BX.Recyclebin.DeletionManager.getInstance(
			gridId,
			{
				moduleId,
				containerId: 'batchDeletionWrapper',
			}
		);

	});
</script>