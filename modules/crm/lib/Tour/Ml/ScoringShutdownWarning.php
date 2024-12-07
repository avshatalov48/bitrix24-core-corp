<?php

namespace Bitrix\Crm\Tour\Ml;

use Bitrix\Crm\Ml\Scoring;
use Bitrix\Crm\Tour\Base;
use Bitrix\Main\Localization\Loc;

final class ScoringShutdownWarning extends Base
{
	protected const OPTION_NAME = 'crm-scoring-shutdown-warning';

	/**
	 * @inheritDoc
	 */
	protected function canShow(): bool
	{
		return
			!$this->isUserSeenTour()
			&& Scoring::isMlAvailable()
			&& Scoring::isEnabled()
			&& Scoring::isScoringAvailable()
			&& Scoring::isTrainingUsed()
		;
	}
	
	protected function getOptions(): array
	{
		return [
			'hideTourOnMissClick' => false,
			'steps' => [
				'popup' => [
					'width' => 480,
				],
			],
		];
	}

	public function getSteps(): array
	{
		return [
			[
				'id' => 'crm-scoring-shutdown-warning-step',
				'title' => Loc::getMessage('CRM_TOUR_SCORING_SHUTDOWN_WARNING_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_SCORING_SHUTDOWN_WARNING_TEXT'),
				'position' => 'top',
				'target' => '.crm-entity-widget-scoring',
			],
		];
	}
}
