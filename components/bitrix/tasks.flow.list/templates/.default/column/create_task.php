<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;

if (!function_exists('renderCreateTaskColumn'))
{
	function renderCreateTaskColumn(Flow $flow, array $arResult, bool $isActive): string
	{
		$buttonColor = $isActive ? Bitrix\UI\Buttons\Color::SUCCESS : '';

		$buttonBuilder = new Bitrix\UI\Buttons\Button([
			'id' => 'tasks-flow-list-create-task-' . $flow->getId(),
			'dataset' => [
				'btn-uniqid' => 'tasks-flow-list-create-task-' . $flow->getId(),
			],
			'color' => $buttonColor,
			'noCaps' => true,
			'text' => Loc::getMessage('TASKS_FLOW_LIST_CREATE_TASK'),
			'size' => Bitrix\UI\Buttons\Size::EXTRA_SMALL,
			'click' => getClickAction($flow, $arResult, $isActive),
		]);
		$buttonBuilder->setRound();
		$buttonBuilder->addAttribute('id', 'tasks-flow-list-create-task-' . $flow->getId());
		$buttonBuilder->addAttribute('type', 'button');

		if ($flow->isImmutable())
		{
			$buttonBuilder->setDisabled();
			$tooltipText = Loc::getMessage(
				'TASKS_FLOW_LIST_CREATE_TASK_IMMUTABLE_DISTRIBUTION_TOOLTIP',
				['[br/]' => '<br>']
			);
			$tooltip = <<<HTML
				<span
					class="ui-hint"
					data-hint="$tooltipText"
					data-hint-html
				>
				</span>
			HTML;
		}

		$button = $buttonBuilder->render();
		if (isset($tooltip))
		{
			$button .= $tooltip;
		}

		$disableClass = $isActive ? '' : '--disable';

		return <<<HTML
			<div class="tasks-flow__list-cell $disableClass">
				<div class="tasks-flow__list-cell_line --start-line ">
					$button
				</div>
			</div>
		HTML;
	}

	if (!function_exists('getClickAction'))
	{
		function getClickAction(Flow $flow, array $arResult, bool $isActive): JsCode
		{
			$isFlowFeatureAvailable = $arResult['isFeatureEnabled'] || $arResult['canTurnOnTrial'];
			if (!$isFlowFeatureAvailable)
			{
				return new JsCode(
					'BX.Tasks.Flow.Grid.showFlowLimit()',
				);
			}

			if ($isActive)
			{
				$createButtonUri = getCreateButtonUri($flow, $arResult);

				return new JsCode(
					'BX.SidePanel.Instance.open("' . $createButtonUri->getUri() . '")',
				);
			}

			if ($flow->isDemo())
			{
				$userId = (int)$arResult['currentUserId'];
				$accessController = $flow->getAccessController($userId);

				if ($accessController->canUpdate())
				{
					$editFormParams = Json::encode([
						'flowId' => $flow->getId(),
						'isFeatureTrialable' => $arResult['isFeatureTrialable'],
					]);

					return new JsCode(
						"BX.Tasks.Flow.EditForm.createInstance({$editFormParams});",
					);
				}
			}

			return new JsCode(
				'BX.Tasks.Flow.Grid.showNotificationHint("flow-off", "'
				. Loc::getMessage('TASKS_FLOW_LIST_FLOW_OFF') . '")',
			);
		}
	}

	if (!function_exists('getCreateButtonUri'))
	{
		function getCreateButtonUri(Flow $flow, array $arResult): Uri
		{
			$flowData = $flow->toArray();

			$createButtonUri = new Uri(
				CComponentEngine::makePathFromTemplate(
					$arResult['pathToUserTasksTask'],
					[
						'action' => 'edit',
						'task_id' => 0,
					]
				)
			);

			$createButtonUri->addParams(['SCOPE' => ScopeDictionary::SCOPE_TASKS_FLOW]);
			$createButtonUri->addParams(['FLOW_ID' => $flow->getId()]);
			$createButtonUri->addParams(['GROUP_ID' => $flow->getGroupId()]);

			if ($flowData['templateId']) {
				$createButtonUri->addParams(['TEMPLATE' => $flowData['templateId']]);
			}

			$demoSuffix = $arResult['isFeatureTrialable'] ? 'Y' : 'N';

			$createButtonUri->addParams([
				'ta_cat' => 'task_operations',
				'ta_sec' => \Bitrix\Tasks\Helper\Analytics::SECTION['flows'],
				'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['flows_grid'],
				'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['flows_grid_button'],
				'p1' => 'isDemo_' . $demoSuffix,
			]);

			return $createButtonUri;
		}
	}
}
