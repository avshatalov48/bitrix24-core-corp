<?php

namespace Bitrix\Crm\Tour\Bizproc;

use Bitrix\Crm\Tour\Base;
use Bitrix\Crm\Tour\Config;
use Bitrix\Main\Localization\Loc;

class WorkflowTaskAddInTimeline extends Base
{
	public const OPTION_NAME = 'workflow-task-add-in-timeline';
	protected const ARTICLE_CODE = 22792842;

	protected function canShow(): bool
	{
		return !$this->isUserSeenTour();
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'on-after-created-task',
				'title' => Loc::getMessage('CRM_TOUR_WORKFLOW_TASK_ADD_IN_TIMELINE_TITLE') ?? '',
				'text' => Loc::getMessage('CRM_TOUR_WORKFLOW_TASK_ADD_IN_TIMELINE_TEXT') ?? '',
				'article' => self::ARTICLE_CODE,
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Timeline.Bizproc::onAfterCreatedTask',
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'disableBannerDispatcher' => true,
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}
}
