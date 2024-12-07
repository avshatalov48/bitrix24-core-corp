<?php

namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Tasks\Grid\Task\Row\Content;
use Bitrix\Tasks\Kanban\StagesTable;

class StageId extends Content
{
	public function prepare(): string
	{
		$taskId = (int)($this->getRowDataByName('ID'));
		$stageId = (int)($this->getRowDataByName('STAGE_ID'));
		$groupId = (int)($this->getRowDataByName('GROUP_ID'));

		if (!$groupId || !$taskId)
		{
			return '<span></span>';
		}

		$stages = StagesTable::getGroupStages($groupId, true);

		if (empty($stages))
		{
			return '<span></span>';
		}

		$currentStage = StagesTable::getGroupStageById($stageId, $groupId);
		if (is_null($currentStage))
		{
			return '<span></span>';
		}

		$content = '';

		$this->setParameter('APPLY_CURRENT_STAGE', true);
		$this->setParameter('CURRENT_STAGE', $currentStage);
		$this->setParameter('TASK_ID', $taskId);

		foreach ($stages as $stage)
		{
			$content .= $this->getStageContent($stage);
			if ((int)$currentStage['ID'] === (int)$stage['ID'])
			{
				$this->setParameter('APPLY_CURRENT_STAGE', false);
			}
		}
		$titleContent = $this->getTitleContent($currentStage['TITLE']);

		return "<div class='tasks-grid-stage-wrap'><div class='tasks-grid-stage-container'>{$content}</div>{$titleContent}</div>";
	}

	private function getStageContent(array $stage): string
	{
		$stageId = (int)$stage['ID'];
		$currentStage = $this->getParameter('CURRENT_STAGE');
		$taskId = $this->getParameter('TASK_ID');
		$title = htmlspecialcharsbx($stage['TITLE']);
		$currentColor = '#' . htmlspecialcharsbx($currentStage['COLOR']);
		$color = '#' . htmlspecialcharsbx($stage['COLOR']);
		$selected = $stageId === (int)$currentStage['ID'] ? 'Y' : 'N';
		$canChangeStage = $this->getParameters()['CAN_SORT_STAGES'] ?? false;

		$onClick = "BX.PreventDefault();";
		$class = 'tasks-grid-stage-step';

		if ($canChangeStage)
		{
			$onClick .= " BX.Tasks.GridActions.onStageSwitch({$taskId}, {$stageId}, \"{$color}\");";
			$class .= ' --editable';
		}

		$result = "<div title=\"{$title}\" class=\"{$class}\" data-stage-id=\"{$stageId}\" data-selected=\"{$selected}\" onclick='{$onClick}'";
		if ($this->getParameter('APPLY_CURRENT_STAGE'))
		{
			$result .= ' style="background-color:' . $currentColor . ';">';
		}
		else
		{
			$result .= '>';
		}

		$result .= '<div class="tasks-grid-stage-step-btn"></div></div>';

		return $result;
	}

	private function getTitleContent(string $title): string
	{
		$title = htmlspecialcharsbx($title);

		return "<div class='tasks-grid-stage-title'>{$title}</div>";
	}
}