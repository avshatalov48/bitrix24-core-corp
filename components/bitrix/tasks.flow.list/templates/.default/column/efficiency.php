<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

if (!function_exists('renderEfficiencyColumn'))
{
	function renderEfficiencyColumn(int $efficiency, array $arResult, bool $isActive): string
	{
		$disableClass = $isActive ? '' : '--disable';
		$efficiencyClass = $efficiency < 70 ? '--danger' : '';
		$efficiencyText = Loc::getMessage('TASKS_FLOW_LIST_ABOUT_EFFICIENCY');

		return <<<HTML
			<div class="tasks-flow__list-cell --middle $disableClass">
				<div
					class="tasks-flow__list-members_wrapper --link" 
					data-hint="$efficiencyText" 
					data-hint-no-icon
					data-hint-html
					data-hint-interactivity
				>
					<div class="tasks-flow__list-cell_line --middle">
						<div class="tasks-flow__efficiency-chart $disableClass $efficiencyClass"></div>
					</div>
					<div class="tasks-flow__list-members_info --link --efficiency">
						$efficiency%
					</div>
				</div>
			</div>
		HTML;
	}
}
