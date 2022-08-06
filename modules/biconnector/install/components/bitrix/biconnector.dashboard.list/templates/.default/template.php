<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.icons',
]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view');
$this->SetViewTarget('inside_pagetitle');

if ($arResult['CAN_WRITE'])
{
?>
<div class="pagetitle-container pagetitle-align-right-container <?=$pagetitleAlignRightContainer?>">
	<a href="<?=$arParams['DASHBOARD_ADD_URL']?>" class="ui-btn ui-btn-primary"><?=Loc::getMessage('CT_BBDL_TOOLBAR_ADD')?></a>
</div>
<?php
}

$this->endViewTarget();

$arResult['HEADERS'] = [
	[
		'id' => 'ID',
		'name' => Loc::getMessage('CT_BBDL_COLUMN_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'NAME',
		'name' => Loc::getMessage('CT_BBDL_COLUMN_NAME'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'URL',
		'name' => Loc::getMessage('CT_BBDL_COLUMN_URL'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'DATE_CREATE',
		'name' => Loc::getMessage('CT_BBDL_COLUMN_DATE_CREATE'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'CREATED_BY',
		'name' => Loc::getMessage('CT_BBDL_COLUMN_CREATED_BY'),
		'default' => true,
		'editable' => false,
	],
];

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'COLUMNS' => $arResult['HEADERS'],
		'ROWS' => $arResult['ROWS'],
		'SORT' => $arResult['SORT'],
		'AJAX_MODE' => 'N',
		'ALLOW_ROWS_SORT' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'EDITABLE' => false,
	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
<script>
	BX.ready(function ()
	{
		if (BX.SidePanel.Instance)
		{
			BX.SidePanel.Instance.bindAnchors(top.BX.clone({
				rules: [
					{
						condition: [
							<?=CUtil::phpToJSObject($arParams['DASHBOARD_ADD_URL'])?>,
							<?=CUtil::phpToJSObject($arParams['DASHBOARD_LIST_URL'])?>
						]
					},
				]
			}));
		}
	});
</script>
<?php
if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}
