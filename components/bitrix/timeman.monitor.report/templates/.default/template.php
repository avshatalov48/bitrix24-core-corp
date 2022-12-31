<?php
/**
 * @var array $arResult
 * @var array $arParams
 * @global \CMain $APPLICATION
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load(['ui.design-tokens', 'timeman.monitor-report']);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view'
);

$this->SetViewTarget('inside_pagetitle', 0);
?>

<div class="pagetitle-container pagetitle-flexible-space">
<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.filter',
		'',
		[
			'GRID_ID' => $arResult['GRID_ID'],
			'FILTER_ID' => $arResult['FILTER_ID'],
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
			'ENABLE_LIVE_SEARCH' => false,
			'DISABLE_SEARCH' => true,
			'ENABLE_LABEL' => true,
		],
	);
?>
</div>

<?php
$this->EndViewTarget();

function getEmployeeLayout($employeeName, $employeeIcon, $employeeLink): string
{
	return '
		<div class="timeman-pwt-report-employee-container">
			<div class="timeman-pwt-report-employee">
				<div class="ui-icon ui-icon-common-user timeman-pwt-report-employee-icon">
					<i '. ($employeeIcon ? 'style="background-image: url(\''.Uri::urnEncode($employeeIcon) . '\')" ' : ''). '></i>
				</div>
				<a class="timeman-pwt-report-text" href="' . $employeeLink . '">
					' . htmlspecialcharsbx($employeeName) . '
				</a>
			</div>
		</div>
	';
}

function wrapLink($value, $userId, $dateLog): string
{
	return '
		<div 
			class="timeman-pwt-report-link" 
			onclick="BX.Timeman.MonitorReport.openViewer(event);"
			data-user="'.(int)$userId.'"
			data-date="'.htmlspecialcharsbx($dateLog).'"
		>
			' . $value . '
		</div>
	';
}

function getDateWorkTimeLayout($date, $workTime): string
{
	return '
		<div class="timeman-pwt-report-row-container">
			<div class="timeman-pwt-report-row-title">
				' . htmlspecialcharsbx($date) . '
			</div>
			<div class="timeman-pwt-report-timeline-legend">
				<div 
					class="
						timeman-pwt-report-timeline-legend-item-marker 
						timeman-pwt-report-timeline-legend-item-marker-working
					">
				</div>
				<div class="timeman-pwt-report-row-subtitle">'
					. Loc::getMessage('TIMEMAN_PWT_REPORT_GRID_WORK_TIME')
					. ': '
					. htmlspecialcharsbx($workTime)
					. '
				</div>
			</div>
		</div>
	';
}

function getStringLayout($value): string
{
	return '<div class="timeman-pwt-report-text">' . htmlspecialcharsbx($value) . '</div>';
}

function getTimelineChart(array $chartData): string
{
	ob_start();
?>
	<div class="timeman-pwt-report-timeline">
		<div class="timeman-pwt-report-timeline-chart">
			<div class="timeman-pwt-report-timeline-chart-outline">
				<div class="timeman-pwt-report-timeline-chart-outline-background"></div>
			</div>
			<div class="timeman-pwt-report-timeline-chart-container">
				<?php
					foreach ($chartData as $interval)
					{
						echo getTimelineItem($interval);
					}
				?>
			</div>
		</div>
	</div>
<?php

	return ob_get_clean();
}

function getTimelineItem(array $interval): string
{
	$intervalItemClass = 'timeman-pwt-report-timeline-chart-interval-item';

	if ($interval['type'])
	{
		$intervalItemClass .= ' timeman-pwt-report-timeline-chart-interval-item-' . $interval['type'];
	}

	if ($interval['isFirst'] && $interval['isLast'])
	{
		$intervalItemClass .= ' timeman-pwt-report-timeline-chart-interval-round';
	}
	elseif ($interval['isFirst'])
	{
		$intervalItemClass .= ' timeman-pwt-report-timeline-chart-interval-first';
	}
	elseif ($interval['isLast'])
	{
		$intervalItemClass .= ' timeman-pwt-report-timeline-chart-interval-last';
	}

	$intervalWidth = $interval['fixedSize'] ? '50px' : $interval['size'] . '%';

	ob_start();
?>

	<div class="timeman-pwt-report-timeline-chart-interval" style="width: <?=$intervalWidth?>;">
		<div class="<?=$intervalItemClass?>"></div>
		<div class="timeman-pwt-report-timeline-chart-interval-marker-container">
			<?php if ($interval['showStartMarker']): ?>
				<div
					class="
						timeman-pwt-report-timeline-chart-interval-marker
						timeman-pwt-report-timeline-chart-interval-marker-start
					"
				>
					<div class="timeman-pwt-report-timeline-chart-interval-marker-line"></div>
					<div class="timeman-pwt-report-timeline-chart-interval-marker-title">
						<?=$interval['startFormatted']?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($interval['showFinishMarker']): ?>
				<div
					class="
						timeman-pwt-report-timeline-chart-interval-marker
						timeman-pwt-report-timeline-chart-interval-marker-finish
					"
				>
					<div class="timeman-pwt-report-timeline-chart-interval-marker-line"></div>
					<div class="timeman-pwt-report-timeline-chart-interval-marker-title">
						<?=$interval['finishFormatted']?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php

	return ob_get_clean();
}

$rows = [];
foreach ($arResult['ROWS'] as $row)
{
	if ($row['pwt_custom']['is_placeholder'])
	{
		$row['columns']['COLUMN_1'] = wrapLink(
			getDateWorkTimeLayout($row['columns']['DATE'], $row['columns']['WORK_TIME']),
			$row['columns']['USER_ID'],
			$row['columns']['DATE_LOG'],
		);
		$row['columns']['COLUMN_2'] = wrapLink(
			getTimelineChart($row['columns']['CHART_DATA']),
			$row['columns']['USER_ID'],
			$row['columns']['DATE_LOG']
		);
	}
	else
	{
		$row['columns']['COLUMN_1'] = getEmployeeLayout(
			$row['columns']['EMPLOYEE_NAME'],
			$row['columns']['EMPLOYEE_ICON'],
			$row['columns']['EMPLOYEE_LINK']
		);
		$row['columns']['COLUMN_2'] = getStringLayout($row['columns']['WORKING_HOURS']);
	}

	$rows[] = $row;
}

$totalContainer = '
	<div class="main-grid-panel-content">
		<span class="main-grid-panel-content-title">
			' . Loc::getMessage('TIMEMAN_PWT_REPORT_GRID_ROW_COUNT') . ':
		</span>&nbsp;
		<a href="#" onclick="BX.TimemanPwtReport.onShowTotalClick(event);">
			' . Loc::getMessage('TIMEMAN_PWT_REPORT_GRID_SHOW_ROW_COUNT') . '
		</a>
	</div>
';
?>

<div class="timeman-pwt-report-grid">
<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		[
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'ROWS' => $rows,
			'SORT' => $arResult['SORT'],
			'NAV_OBJECT' => $arResult['NAV_OBJECT'],
			'ALLOW_COLUMNS_SORT' => false,
			'ALLOW_SORT' => true,
			'ALLOW_PIN_HEADER' => true,
			'SHOW_PAGINATION' => true,
			'SHOW_PAGESIZE' => true,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_CHECK_ALL_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'ENABLE_COLLAPSIBLE_ROWS' => true,
			'PAGE_SIZES' => [
				['NAME' => '10', 'VALUE' => '10'],
				['NAME' => '20', 'VALUE' => '20'],
				['NAME' => '50', 'VALUE' => '50'],
				['NAME' => '100', 'VALUE' => '100'],
			],
			'SHOW_ACTION_PANEL' => false,
			'AJAX_MODE' => 'Y',
			'AJAX_ID' => CAjax::GetComponentID(
				'bitrix:timeman.pwt.report',
				'',
				''
			),
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
			'TOTAL_ROWS_COUNT_HTML' => $totalContainer,
		],
	);
?>
</div>

<script>
	BX.ready(function() {
		BX.TimemanPwtReport = new BX.TimemanPwtReport({});
	});
</script>
