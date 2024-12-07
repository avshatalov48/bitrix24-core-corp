<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.fonts.opensans',
	'biconnector.grid'
]);
$this->addExternalCss('/bitrix/css/main/font-awesome.css');

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view');

$limitManager = \Bitrix\BIConnector\LimitManager::getInstance();
$frame = isset($_GET['IFRAME']) && $_GET['IFRAME'] === 'Y' ? '&IFRAME=Y' : '';

$this->SetViewTarget('inside_pagetitle');
if (isset($_GET['over_limit']) && $_GET['over_limit'] === 'Y')
{
	?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<a href="?over_limit=<?php echo 'N' . $frame;?>" class="ui-btn ui-btn-light-border ui-btn-themes"><?=Loc::getMessage('CT_BBSU_SHOW_ALL')?></a>
		<a href="javascript:void(0)" onclick="BX.Main.gridManager.getInstanceById('<?php echo $arResult['GRID_ID']?>').reloadTable('POST')" class="ui-btn ui-btn-primary" title="<?=Loc::getMessage('CT_BBSU_REFRESH')?>"><i class="fa fa-refresh"></i></a>
	</div>
	<?php
}
elseif ($limitManager->getLimit() > 0)
{
	?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<a href="?over_limit=<?php echo 'Y' . $frame;?>" class="ui-btn ui-btn-light-border ui-btn-themes"><?=Loc::getMessage('CT_BBSU_SHOW_OVERLIMIT')?></a>
		<a href="javascript:void(0)" onclick="BX.Main.gridManager.getInstanceById('<?php echo $arResult['GRID_ID']?>').reloadTable('POST')" class="ui-btn ui-btn-primary" title="<?=Loc::getMessage('CT_BBSU_REFRESH')?>"><i class="fa fa-refresh"></i></a>
	</div>
	<?php
}
else
{
	?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<a href="javascript:void(0)" onclick="BX.Main.gridManager.getInstanceById('<?php echo $arResult['GRID_ID']?>').reloadTable('POST')" class="ui-btn ui-btn-primary" title="<?=Loc::getMessage('CT_BBSU_REFRESH')?>"><i class="fa fa-refresh"></i></a>
	</div>
	<?php
}
$this->EndViewTarget();

$arResult['HEADERS'] = [
	[
		'id' => 'ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'TIMESTAMP_X',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_TIMESTAMP_X'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'KEY_ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_ACCESS_KEY'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'SERVICE_ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_SERVICE_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'SOURCE_ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_SOURCE_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'ROW_NUM',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_ROW_NUM'),
		'default' => true,
		'editable' => false,
		'align' => 'right',
	],
	[
		'id' => 'DATA_SIZE',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_DATA_SIZE'),
		'default' => true,
		'editable' => false,
		'align' => 'right',
	],
	[
		'id' => 'REAL_TIME',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_REAL_TIME'),
		'default' => false,
		'editable' => false,
		'align' => 'right',
	],
	[
		'id' => 'FILTERS',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_FILTERS'),
		'default' => false,
		'editable' => false,
	],
	[
		'id' => 'FIELDS',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_FIELDS'),
		'default' => false,
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
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'ALLOW_ROWS_SORT' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'EDITABLE' => false,
		'STUB' => empty($arResult['ROWS']) ? $arResult['STUB'] : null,
		'SHOW_NAVIGATION_PANEL' => true,
		'NAV_OBJECT' => $arResult['NAV'],
		'NAV_PARAMS' => [
			'SEF_MODE' => 'N',
		],
		'NAV_PARAM_NAME' => 'page',
		'SHOW_PAGINATION' => true,
		'SHOW_PAGESIZE' => true,
		'PAGE_SIZES' => [
			['NAME' => 10, 'VALUE' => '10'],
			['NAME' => 20, 'VALUE' => '20'],
			['NAME' => 50, 'VALUE' => '50'],
		],

	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
<script>
	function showMore(btn, textToAdd)
	{
		const text = btn.previousSibling;
		text.textContent += textToAdd;
		btn.remove();
		return false;
	}
</script>
<?php
if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}
