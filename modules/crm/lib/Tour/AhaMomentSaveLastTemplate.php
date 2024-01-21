<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Main\Localization\Loc;

class AhaMomentSaveLastTemplate extends Base
{
	public const OPTION_NAME = 'aha-moment-save-last-template';

	protected function canShow(): bool
	{
		return !$this->isUserSeenTour();
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'step-save-last-used-template',
				'title' => Loc::getMessage('CRM_TOUR_AHA_MOMENT_SAVE_LAST_TEMPLATE_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_AHA_MOMENT_SAVE_LAST_TEMPLATE_BODY'),
				'position' => 'left',
				'useDynamicTarget' => true,
				'eventName' => 'CrmActivityEmail::onShowTemplatesList',
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
			'showOverlayFromFirstStep' => true,
		];
	}
}