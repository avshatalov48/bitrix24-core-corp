<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\UI\ScopeDictionary;

if (!function_exists('renderCreateTaskColumn'))
{
	function renderCreateTaskColumn(Flow $flow, array $arResult, bool $isActive): string
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

		if ($flowData['templateId'])
		{
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

		$buttonColor = $isActive ? Bitrix\UI\Buttons\Color::SUCCESS : '';

		if ($arResult['isFeatureEnabled'] || $arResult['canTurnOnTrial'])
		{
			$click = '';
			if ($isActive)
			{
				$click = new Bitrix\UI\Buttons\JsCode(
					'BX.SidePanel.Instance.open("' . $createButtonUri->getUri() . '")',
				);
			}
			else if (!$flow->isDemo())
			{
				$click = new Bitrix\UI\Buttons\JsCode(
					'BX.Tasks.Flow.Grid.showNotificationHint("flow-off", "'
					. Loc::getMessage('TASKS_FLOW_LIST_FLOW_OFF') . '")',
				);
			}
		}
		else
		{
			$click = new Bitrix\UI\Buttons\JsCode(
				'BX.Tasks.Flow.Grid.showFlowLimit()',
			);
		}

		$buttonBuilder = new Bitrix\UI\Buttons\Button([
			'id' => 'tasks-flow-list-create-task-' . $flow->getId(),
			'dataset' => [
				'btn-uniqid' => 'tasks-flow-list-create-task-' . $flow->getId(),
			],
			'color' => $buttonColor,
			'noCaps' => true,
			'text' => Loc::getMessage('TASKS_FLOW_LIST_CREATE_TASK'),
			'size' => Bitrix\UI\Buttons\Size::EXTRA_SMALL,
			'click' => $click,
		]);
		$buttonBuilder->setRound();
		$buttonBuilder->addAttribute('id', 'tasks-flow-list-create-task-' . $flow->getId());
		$buttonBuilder->addAttribute('type', 'button');

		$button = $buttonBuilder->render();

		$disableClass = $isActive ? '' : '--disable';

		return <<<HTML
			<div class="tasks-flow__list-cell $disableClass">
				<div class="tasks-flow__list-cell_line --start-line ">
					$button
				</div>
			</div>
		HTML;
	}
}
