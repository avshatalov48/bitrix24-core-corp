<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllUser $USER */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

Extension::load("ui.icons");
Extension::load("ui.hint");

$containerId = 'crm-analytics-report-view-chart-grid' . ($arParams['IS_TRAFFIC'] ? '-traffic' : '');
?>
<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-report-chart-grid-wrapper">
	<?
	foreach ($arResult['GRID']['ROWS'] as $index => $row)
	{
		if (is_array($row['SOURCE_CODE']))
		{
			if (array_key_exists('code', $row['SOURCE_CODE']))
			{
				$sourceCaption = htmlspecialcharsbx($row['SOURCE_CODE']['caption']);
				$sourceCode = htmlspecialcharsbx($row['SOURCE_CODE']['code']);
				$sourceIconClass = htmlspecialcharsbx($row['SOURCE_CODE']['iconClass']);
				$sourceColor = htmlspecialcharsbx($row['SOURCE_CODE']['color']);
				$paddingClass = empty($row['SOURCE_CODE']['usePadding']) ? '' : 'crm-report-chart-grid-item-padding';

				$row['SOURCE_CODE'] = '<div class="crm-report-chart-grid-item ' . $paddingClass . '" style="white-space: nowrap">'
					. '<div class="crm-report-chart-modal-title-icon ' . $sourceIconClass . '">'
					. '<i ' . ($sourceColor ? 'style="background-color: ' . $sourceColor . '"' : '') . '></i>'
					. '</div>'
					. '<span>' . $sourceCaption . '</span>'
					. '</div>';

				$row['SOURCE_COLOR'] = '<div class="crm-report-chart-modal-title-icon" style="background: ' . $sourceColor . '; width: 19px; height: 19px;"></div>';
			}
			else
			{
				$userName = htmlspecialcharsbx($row['SOURCE_CODE']['NAME']);
				$userPath = htmlspecialcharsbx($row['SOURCE_CODE']['LINK']);
				$userIcon = htmlspecialcharsbx($row['SOURCE_CODE']['ICON']);

				$row['SOURCE_CODE'] = '<div class="crm-report-chart-grid-user">
					<div class="ui-icon ui-icon-common-user crm-report-chart-grid-user-icon">
						<i '
							. ($userIcon ? 'style="background-image: url('. $userIcon . ')" ' : '')
						. '></i>
					</div>
					<div class="crm-report-chart-grid-user-name">
						' . $userName . '
					</div>
				</div>';
			}
		}

		$colorCodes = ['ROI', 'CONVERSION'];
		foreach ($colorCodes as $colorCode)
		{
			if (is_array($row[$colorCode]))
			{
				$conversionColor = htmlspecialcharsbx($row[$colorCode]['color']);
				$conversionText = htmlspecialcharsbx($row[$colorCode]['text']);
				$conversionValue = htmlspecialcharsbx($row[$colorCode]['value']);
				$row[$colorCode] = '<div class="crm-report-chart-grid-rating">'
					. '<div class="crm-report-chart-grid-rating-icon" style="background: ' . $conversionColor . '"></div>'
					. ($conversionValue ? '<div class="crm-report-chart-grid-rating-value">' . $conversionValue . '</div>' : '')
					. '<div class="crm-report-chart-grid-rating-text" style="color: ' . $conversionColor . '">' . $conversionText . '</div>'
					. '</div>';
			}
		}

		$isSummaryRow = $row['ID'] && in_array($row['ID'], ['summary', 'summary-ad']);
		if ($isSummaryRow || strpos($row['ID'], 'user-id-') === 0)
		{
			foreach ($row as $key => $value)
			{
				if ($key == 'ID')
				{
					continue;
				}

				$addCss = $isSummaryRow ? 'crm-report-chart-grid-value-margin' : '';
				$value = '<div class="' . $addCss . ' crm-report-chart-grid-value-bold">'
					. $value
					. '</div>';
				$row[$key] = $value;
			}
		}
		elseif(array_key_exists('COSTS', $row) && $row['ID'])
		{
			$path = str_replace('#id#', $row['ID'], '/crm/tracking/expenses/#id#/?add=Y');
			$path = CUtil::JSEscape(htmlspecialcharsbx($path));
			$row['COSTS'] .= '<div><span onclick="BX.SidePanel.Instance.open(\'' . $path . '\', {width: 670});" '
				. 'class="crm-report-chart-grid-link-expenses">'
				. Loc::getMessage('CRM_REPORT_VC_W_C_CHART_EXPENSES_ADD')
				. '</span></div>';
		}

		foreach ($row as $key => $value)
		{
			if (!is_array($value) || empty($value['PATH']))
			{
				continue;
			}

			$path = CUtil::JSEscape(htmlspecialcharsbx($value['PATH']));
			$num = htmlspecialcharsbx($value['VALUE']);
			$value = '<div onclick="BX.SidePanel.Instance.open(\'' . $path . '\');" class="crm-report-chart-grid-value-link">' . $num . '</div>';
			$row[$key] = $value;
		}

		$arResult['GRID']['ROWS'][$index] = [
			'id' => $row['ID'],
			'columns' => $row,
			'actions' => []
		];
	}

	$APPLICATION->IncludeComponent(
		"bitrix:main.ui.grid",
		"",
		array(
			"GRID_ID" => $containerId . '-grid',
			"COLUMNS" => $arResult['GRID']['COLUMNS'],
			"ROWS" => $arResult['GRID']['ROWS'],
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_GRID_SETTINGS_MENU' => false,
			'SHOW_PAGINATION' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'SHOW_TOTAL_COUNTER' => false,
			'ACTION_PANEL' => [],
			"TOTAL_ROWS_COUNT" => null,
			'ALLOW_COLUMNS_SORT' => true,
			'ALLOW_COLUMNS_RESIZE' => true,
		)
	);
	?>
</div>

<script>
	BX.ready(function () {
		BX.Main.gridManager
			.getInstanceById('<?=\CUtil::JSEscape($containerId . '-grid')?>')
			.getRows()
			.getBodyChild()
			.forEach(function (row) {
				BX.bind(row.getNode(), 'mouseenter', function () {
					BX.addClass(row.getNode(), 'crm-report-chart-grid-show-expenses');
				});
				BX.bind(row.getNode(), 'mouseleave', function () {
					BX.removeClass(row.getNode(), 'crm-report-chart-grid-show-expenses');
				});
			});
	});
</script>