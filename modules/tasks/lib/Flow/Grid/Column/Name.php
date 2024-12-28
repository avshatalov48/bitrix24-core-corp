<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Grid\Preload\ProjectPreloader;
use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Util\View;
use CTaskListState;

final class Name extends Column
{
	private ProjectPreloader $projectPreloader;

	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): array
	{
		$project = $this->projectPreloader->get($flow->getGroupId());

		return [
			'flowId' => $flow->getId(),
			'flowName' => $flow->getName(),
			'isPinned' => $params['isPinned'] ?? false,
			'pinText' => ($params['isPinned']
				? Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME_UNPIN')
				: Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME_PIN')
			),
			'groupId' => $flow->getGroupId(),
			'groupName' => (
				$flow->isDemo()
					? Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME_DEMO_PROJECT')
					: $project['name']
			),
			'dateLabel' => Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME_LABEL_DATE'),
			'date' => (
				$flow->isDemo() ?
					Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME_DEMO_DATE')
					: DatePresenter::createFromSeconds($flow->getPlannedCompletionTime())->getFormatted()
			),
			'groupLabel' => Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME_LABEL_GROUP'),
			'hidden' => $project['hidden'],
			'view' => $this->getProjectView((int)$params['userId'], $flow->getGroupId()),
			'demo' => $flow->isDemo(),
		];
	}

	private function init(): void
	{
		$this->id = 'NAME';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_NAME');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = true;
		$this->width = 230;

		$this->projectPreloader = new ProjectPreloader();
	}

	private function getProjectView(int $userId, int $projectId): string
	{
		// if no state, that's mean what user has never visited the group, show kanban instead of list
		$view = View::KANBAN->view();

		// no way to batch preload this data...
		$state = CTaskListState::getInstance($userId, $projectId);
		if ($state->hasState())
		{
			$view = View::fromState($state->getViewMode())->view();
		}

		return $view;
	}
}
