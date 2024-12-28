<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!function_exists('renderBIAnalyticsColumn'))
{
	function renderBIAnalyticsColumn(array $data, array $arResult, bool $isActive): string
	{
		$disableClass = $isActive ? '' : '--disable';
		$efficiencyClass = '';
		$efficiencyText = Loc::getMessage('TASKS_FLOW_LIST_BI_ANALYTICS_GREAT');

		$efficiency = $data['efficiency'];

		if ($efficiency < 70)
		{
			$efficiencyClass = '--danger';
			$efficiencyText = Loc::getMessage('TASKS_FLOW_LIST_BI_ANALYTICS_BADLY');
		}

		$dashboards = $data['dashboards'];
		$flowId = $data['flowId'];

		$onclick = getBIOnclick($dashboards, $flowId);
		$hint = getBIEmptyHint($dashboards);

		return <<<HTML
			<div class="tasks-flow__list-cell $disableClass">
				<div class="tasks-flow__list-analytics" $hint>
					<div class="tasks-flow__analytics-icon $efficiencyClass"></div>
					<div class="tasks-flow__analytics-text" onclick='$onclick'>$efficiencyText</div>
			</div>
		HTML;
	}
}

if (!function_exists('getBIOnclick'))
{
	function getBIOnclick(array $dashboards, int $flowId): string
	{
		if (empty($dashboards))
		{
			return '';
		}

		$jsonDashboards = Json::encode($dashboards);

		$manyDashboards = count($dashboards) > 1;

		if ($manyDashboards)
		{
			return "
				BX.Tasks.Flow.BIAnalytics.create({
					dashboards: {$jsonDashboards},
					target: this,
					flowId: {$flowId},
				}).openMenu();
			";
		}

		return "
			BX.Tasks.Flow.BIAnalytics.create({
				dashboards: {$jsonDashboards},
				target: this,
				flowId: {$flowId},
			}).openFirstDashboard();
		";
	}
}

if (!function_exists('getBIEmptyHint'))
{
	function getBIEmptyHint(array $dashboards): string
	{
		if (!empty($dashboards))
		{
			return '';
		}

		return 'data-hint="'
			. Loc::getMessage('TASKS_FLOW_LIST_BI_ANALYTICS_EMPTY_DASHBOARDS')
			. '" data-hint-no-icon';
	}
}
