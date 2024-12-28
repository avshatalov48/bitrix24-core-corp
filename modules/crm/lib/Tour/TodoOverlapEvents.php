<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Service\Timeline\Item\Activity\ToDo;
use Bitrix\Crm\Tour\Base;
use Bitrix\Main\Localization\Loc;

class TodoOverlapEvents extends Base
{
	public const OPTION_NAME = 'aha-moment-todo-overlap-event';

	private ToDo $todo;

	protected function canShow(): bool
	{
		$hasTagOverlapEvent = isset($this->todo) && isset($this->todo->getTags()['overlapEvent']);

		return !$this->isUserSeenTour() && $hasTagOverlapEvent;
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => self::OPTION_NAME,
				'title' => Loc::getMessage('CRM_TOUR_OVERLAP_EVENT_MESSAGE_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_OVERLAP_EVENT_MESSAGE_TEXT'),
				'position' => 'top',
				'target' => '.crm-timeline__card-status',
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 350,
				],
			],
		];
	}

	public function setTodo(ToDo $todo): self
	{
		$this->todo = $todo;

		return $this;
	}
}