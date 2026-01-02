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
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\FlowCopilotFeature;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataStatus;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;

if (!function_exists('renderEfficiencyColumn'))
{
	function renderEfficiencyColumn(array $data, array $arResult, bool $isActive): string
	{
		/** @var Flow $flow */
		['flow' => $flow, 'efficiency' => $efficiency, 'adviceInfo' => $adviceInfo] = $data;
		$disableClass = $isActive ? '' : '--disable';
		$efficiencyChartClass = $efficiency <= Configuration::getMaxValueForLowEfficiency() ? '--danger' : '';

		if ($isActive && Loader::includeModule('ai') && FlowCopilotFeature::isOn())
		{
			$efficiencyValueNode = getCopilotEfficiencyNode($arResult, $flow, (int)$efficiency, $adviceInfo);
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
		function getCopilotEfficiencyNode(array $arResult, Flow $flow, int $efficiency, array $adviceInfo): string
		{
			$userId = (int)$arResult['currentUserId'];

			$isAdviceExists = $adviceInfo['IS_ADVICE_EXISTS'];
			$isShouldShowBoostPopup = false;
			$isAdviceFetching = false;
			$isAdviceErrorOccurred = false;

			if (!$isAdviceExists)
			{
				$isShouldShowBoostPopup = $adviceInfo['STATUS'] === CollectedDataStatus::LIMIT_EXCEEDED;

				$isAdviceFetching = in_array(
					$adviceInfo['STATUS'],
					[CollectedDataStatus::COLLECTED, CollectedDataStatus::COLLECTING],
					true
				);

				$isAdviceErrorOccurred = $adviceInfo['STATUS'] === CollectedDataStatus::ERROR;
			}

			 switch (true) {
				case $isAdviceErrorOccurred:
					$onCopilotIconClick = "BX.Tasks.Flow.CopilotAdviceErrorPopup.show(
						this, 
						BX.Tasks.Flow.CopilotAdviceErrorTypes.UnexpectedError
					);";

					break;
				case $isAdviceFetching:
					$onCopilotIconClick = "BX.Tasks.Flow.CopilotAdviceErrorPopup.show(
						this, 
						BX.Tasks.Flow.CopilotAdviceErrorTypes.AdviceFetching
					);";

					break;
				case $isShouldShowBoostPopup:
					$onCopilotIconClick = "BX.loadExt('baas.store').then(function(exports) {
						BX.Baas.Store.Widget.getInstance().bind(this).show();
					}.bind(this));";

					break;
				case $isAdviceExists:
					$createTaskUri = TaskPathMaker::getPath([
						'task_id' => 0,
						'user_id' => $userId,
						'action' => PathMaker::EDIT_ACTION
					]);
					$createTaskUri = CUtil::JSEscape($createTaskUri);

					$accessController = $flow->getAccessController($userId);
					$canEditFlow = $accessController->canUpdate();

					$canEditFlow = Json::encode($canEditFlow);
					$isFeatureTrialable = Json::encode($arResult['isFeatureTrialable']);
					$onCopilotIconClick = "BX.Tasks.Flow.CopilotAdvice.show({
						flowId: {$flow->getId()},
						flowEfficiency: {$efficiency},
						canEditFlow: {$canEditFlow},
						createTaskUrl: '{$createTaskUri}',
						isFeatureTrialable: '{$isFeatureTrialable}',
					});";

					break;
				default:
					$onCopilotIconClick = "BX.Tasks.Flow.CopilotAdviceErrorPopup.show(
						this, 
						BX.Tasks.Flow.CopilotAdviceErrorTypes.NotEnoughTasks
					);";

					break;
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
