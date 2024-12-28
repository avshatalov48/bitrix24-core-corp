<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration;
use Bitrix\Tasks\Flow\Integration\AI\FlowCopilotFeature;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;

if (!function_exists('renderEfficiencyColumn'))
{
	function renderEfficiencyColumn(array $data, array $arResult, bool $isActive): string
	{
		/** @var Flow $flow */
		['flow' => $flow, 'efficiency' => $efficiency, 'isEnoughTasksForCopilot' => $isEnoughTasksForCopilot] = $data;
		$disableClass = $isActive ? '' : '--disable';
		$efficiencyChartClass = $efficiency < 70 ? '--danger' : '';

		if (Loader::includeModule('ai') && FlowCopilotFeature::isOn() && $isActive)
		{
			$efficiencyValueNode = getCopilotEfficiencyNode($arResult, $flow, (int)$efficiency, (bool)$isEnoughTasksForCopilot);
		}
		else
		{
			$efficiencyValueNode = getDefaultEfficiencyNode((int)$efficiency);
		}

		return <<<HTML
			<div class="tasks-flow__list-cell --middle $disableClass">
				<div
					class="tasks-flow__list-members_wrapper --link"
				>
					<div class="tasks-flow__list-cell_line --middle">
						<div class="tasks-flow__efficiency-chart $disableClass $efficiencyChartClass"></div>
					</div>
					{$efficiencyValueNode}
				</div>
			</div>
		HTML;
	}

	if (!function_exists('getCopilotEfficiencyNode'))
	{
		function getCopilotEfficiencyNode(array $arResult, Flow $flow, int $efficiency, bool $isEnoughTasksForCopilot): string
		{
			$userId = (int)$arResult['currentUserId'];

			if ($isEnoughTasksForCopilot)
			{
				$createTaskUri = TaskPathMaker::getPath([
					'task_id' => 0,
					'user_id' => $userId,
					'action' => PathMaker::EDIT_ACTION
				]);
				$createTaskUri = CUtil::JSEscape($createTaskUri);

				$flowId = $flow->getId();
				$accessController = $flow->getAccessController($userId);
				$canEditFlow = $accessController->canUpdate();

				$canEditFlow = Json::encode($canEditFlow);
				$onCopilotIconClick = "BX.Tasks.Flow.CopilotAdvice.show({
					flowId: {$flowId},
					flowEfficiency: {$efficiency},
					canEditFlow: {$canEditFlow},
					createTaskUrl: '{$createTaskUri}'
				})";
			}
			else
			{
				$onCopilotIconClick = 'BX.Tasks.Flow.NotEnoughTasksPopup.show(this)';
			}

			return <<<HTML
				<div class="tasks-flow__efficiency-copilot">
					<div class="tasks-flow__efficiency-copilot-pill" onclick="{$onCopilotIconClick}">
						<span class="tasks-flow__efficiency-copilot-icon-wrapper">
							<span class="ui-icon-set --copilot-ai tasks-flow__efficiency-copilot-icon"></span>
						</span>
						<span class="tasks-flow__efficiency-copilot-text">
							$efficiency%
						</span>
					</div>
				</div>
			HTML;
		}
	}

	if (!function_exists('getDefaultEfficiencyNode'))
	{
		function getDefaultEfficiencyNode(int $efficiency): string
		{
			$efficiencyText = Loc::getMessage('TASKS_FLOW_LIST_ABOUT_EFFICIENCY');

			return <<<HTML
				<div class="tasks-flow__list-members_info --link --efficiency">
					<span
						data-hint="$efficiencyText" 
						data-hint-no-icon
						data-hint-html
						data-hint-interactivity
					>
						$efficiency%
					</span>
				</div>
			HTML;
		}
	}
}
