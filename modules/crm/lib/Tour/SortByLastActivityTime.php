<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;

class SortByLastActivityTime extends Base
{
	use OtherTourChecker;

	protected const OPTION_NAME = 'sort-by-last-activity';

	protected function canShow(): bool
	{
		return !$this->isUserSeenTour();
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'step-sort-by-last-activity-time',
				'title' => Loc::getMessage('CRM_TOUR_SBLA_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_SBLA_TEXT'),
				'useDynamicTarget' => true,
				'eventName' => 'Kanban.Grid::onShowSortByLastActivityTour',
			],
		];
	}
}
