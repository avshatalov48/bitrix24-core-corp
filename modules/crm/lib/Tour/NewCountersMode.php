<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;

class NewCountersMode extends Base
{
	use OtherTourChecker;

	public const OPTION_NAME = 'new-counters-mode';

	protected function canShow(): bool
	{
		return
			Crm::isUniversalActivityScenarioEnabled()
			&& !$this->isUserSeenTour()
			&& $this->isUserSeenOtherTour(ActivityViewMode::getInstance())
		;
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'step-new-counters',
				'title' => Loc::getMessage('CRM_TOUR_NEW_COUNTERS_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_NEW_COUNTERS_TEXT'),
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.EntityCounterPanel::onShowNewCountersTour',
			],
		];
	}
}
