<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Main\Localization\Loc;

final class CopilotRunManually extends CopilotInCall
{
	protected const OPTION_NAME = 'copilot-button-in-call-manually';

	private static bool $isWelcomeTourEnabled = false;
	protected int $numberOfViewsLimit = 1;

	protected function canShow(): bool
	{
		return !(!$this->isShowEnabled() || $this->isUserSeenTour());
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => self::OPTION_NAME,
				'title' => Loc::getMessage('CRM_TOUR_COPILOT_RUN_MANUALLY_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_COPILOT_RUN_MANUALLY_TEXT'),
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Timeline.Call:onShowTourWhenCopilotManuallyStart',
				'article' => 18799442,
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'showOverlayFromFirstStep' => true,
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}

	protected function isShowEnabled(): bool
	{
		return
			parent::isShowEnabled()
			&& AIManager::isAiCallAutomaticProcessingAllowed()
			&& !AIManager::isBaasServiceHasPackage()
		;
	}

	public function isWelcomeTourEnabled(): bool
	{
		if (static::$isWelcomeTourEnabled)
		{
			return false;
		}

		static::$isWelcomeTourEnabled = true;

		return $this->isShowEnabled() && !$this->isUserSeenTour();
	}
}
