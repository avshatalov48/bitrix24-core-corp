<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

if (!function_exists('renderBIAnalyticsColumn'))
{
	function renderBIAnalyticsColumn(int $efficiency, array $arResult, bool $isActive): string
	{
		$disableClass = $isActive ? '' : '--disable';
		$comingSoon = Loc::getMessage('TASKS_FLOW_LIST_COMING_SOON');
		$efficiencyClass = '';
		$efficiencyText = Loc::getMessage('TASKS_FLOW_LIST_BI_ANALYTICS_GREAT');

		if ($efficiency < 70)
		{
			$efficiencyClass = '--danger';
			$efficiencyText = Loc::getMessage('TASKS_FLOW_LIST_BI_ANALYTICS_BADLY');
		}

		return <<<HTML
			<div class="tasks-flow__list-cell $disableClass">
				<div class="tasks-flow__list-analytics" data-hint="$comingSoon" data-hint-no-icon>
					<div class="tasks-flow__analytics-icon $efficiencyClass"></div>
					<div class="tasks-flow__analytics-text">$efficiencyText</div>
			</div>
		HTML;
	}
}
