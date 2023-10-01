<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'crm.report.tracking.ad.report',
	'ui.info-helper',
]);

$rows = [];
$columnIds = array_column($arResult['COLUMNS'], 'id');
foreach ($arResult['ROWS'] as $item)
{
	$rowData = [];
	foreach ($columnIds as $columnId)
	{
		$value = htmlspecialcharsbx($item[$columnId]);
		if (in_array($columnId, ['outcome', 'income', 'cost']))
		{
			$value = $value ? \CCrmCurrency::MoneyToString($value, $item['currencyId']) : '0';
		}
		elseif (in_array($columnId, ['impressions', 'actions', 'leads', 'deals', 'successDeals']))
		{
			$value = $value ? number_format($value, 0, '.', ' ') : '0';
			if ($value && $columnId === 'successDeals' && $item['cost'])
			{
				$successDealCost = htmlspecialcharsbx($item['cost']);
				$successDealCost = \CCrmCurrency::MoneyToString($successDealCost, $item['currencyId']);
				$value = "<div>$value</div><div class=\"crm-tracking-report-source-row-text\">$successDealCost " . Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_GRID_UNIT'). "</div>";
			}
			if ($value && $columnId === 'actions' && $item['cpc'])
			{
				$cpc = htmlspecialcharsbx($item['cpc']);
				$cpc = \CCrmCurrency::MoneyToString($cpc, $item['currencyId']);
				$value = "<div>$value</div><div class=\"crm-tracking-report-source-row-text\">$cpc " . Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_GRID_UNIT'). "</div>";
			}
		}
		elseif (in_array($columnId, ['roi']))
		{
			if ($value != 0)
			{
				$value = $value . '%';
			}
			else
			{
				$value = '';
			}

			$roiClass = '';
			switch ($item['roiScale'])
			{
				case -1:
					$roiClass = 'crm-tracking-report-source-rating-warning';
					$roiText = Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_GRID_ROI_BAD');
					break;
				case 0:
					$roiClass = 'crm-tracking-report-source-rating-none';
					$roiText = Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_GRID_ROI_NONE');
					break;
				case 1:
					$roiClass = 'crm-tracking-report-source-rating-normal';
					$roiText = Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_GRID_ROI_NORM');
					break;
				case 2:
					$roiClass = 'crm-tracking-report-source-rating-success';
					$roiText = Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_GRID_ROI_GOOD');
					break;
			}

			$value = '
				<div class="crm-tracking-report-source-rating ' . $roiClass . '">
					<div class="crm-tracking-report-source-rating-icon"></div>
					' . ( !$value ? '' : '
					<div class="crm-tracking-report-source-rating-value">
						' . $value . '
					</div>
					') . '
					<div class="crm-tracking-report-source-rating-text">
						' . $roiText . '
					</div>
				</div>
			';
		}
		elseif (in_array($columnId, ['ctr', 'cpc']))
		{
			$value = $value ? $value . '%' : '';
		}

		$rowData[$columnId] = "<span>$value</span>";
	}

	$rowData['title'] = '<div class="crm-tracking-report-source-title">'
		. (Option::get('crm', 'tracking_ad_manage', 'N') === 'Y'
				? '<div class="crm-tracking-report-source-status"><div></div></div>'
				: ''
		)
		. '<div data-role="grid/activator" data-options="'
		. htmlspecialcharsbx(Json::encode(['level' => $item['nextLevel'], 'parentId' => $item['id'], 'enabled' => $item['enabled']]))
		. '">'
		. ($item['nextLevel'] !== null
			? '<span class="crm-tracking-report-source-link">' . htmlspecialcharsbx($item['title']) . '</span>'
			: htmlspecialcharsbx($item['title'])
		)
		. '</div></div>'
	;

	//$rowData['title'] = '<div><span class="" title="">' . ((int) $item['enabled']) . '</span>' . $rowData['title'] . '</div>';

	$rows[] = [
		'id' => $item['id'],
		'data' => $rowData,
	];
}


?>
<div class="crm-tracking-report-source">
<span data-role="grid/selector/title" class="crm-tracking-report-source-selector-text"><?=htmlspecialcharsbx($arResult['PARENT']['NAME'])?></span>
<span data-role="grid/selector" data-options="<?=htmlspecialcharsbx(Json::encode([
	'items' => (count($arResult['SIBLINGS']['LIST']) > 1 ? $arResult['SIBLINGS']['LIST'] : []),
]))?>" class="crm-tracking-report-source-selector<?=(count($arResult['SIBLINGS']['LIST']) > 1 ? '-link' : '')?>">
	<?=htmlspecialcharsbx($arResult['SIBLINGS']['CURRENT']['title'])?>
</span>
<?php

if ($arResult['FEATURE_CODE'])
{
	?><script>BX.UI.InfoHelper.show('<?=$arResult['FEATURE_CODE']?>');</script><?php
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'ID' => $arParams['GRID_ID'],
		'GRID_ID' => $arParams['GRID_ID'],
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS' => $rows,
		'TOTAL_ROWS_COUNT' =>count($rows),
		'SHOW_TOTAL_COUNTER' => true,
		'SHOW_NAVIGATION_PANEL' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_GRID_SETTINGS_MENU' => false,
		'SHOW_PAGINATION' => false,
		'ALLOW_COLUMNS_SORT' => false,
		'ALLOW_COLUMNS_RESIZE' => false,
		'AJAX_MODE' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N'
	]
);
?>
</div>
