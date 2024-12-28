<?php

namespace Bitrix\Crm\Tour\Bizproc;

use Bitrix\Crm\Tour\Base;
use Bitrix\Main\Localization\Loc;

class WorkflowStarted extends Base
{
	public const OPTION_NAME = 'workflow-started-timeline';
	protected const ARTICLE_CODE = 22792842;

	protected function canShow(): bool
	{
		return !$this->isUserSeenTour();
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'on-after-started-workflow',
				'title' => Loc::getMessage('CRM_TOUR_WORKFLOW_STARTED_TITLE') ?? '',
				'text' => Loc::getMessage('CRM_TOUR_WORKFLOW_STARTED_TEXT') ?? '',
				'article' => self::ARTICLE_CODE,
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Timeline.Bizproc::onAfterWorkflowStarted',
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}
}
