<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Main\Localization\Loc;

final class CopilotInCallAutomatically extends CopilotInCall
{
	protected const OPTION_NAME = 'copilot-in-call-automatically-v2';

	protected int $numberOfViewsLimit = 1;

	protected function canShow(): bool
	{
		if (
			!$this->isShowEnabled()
			|| $this->isUserSeenTour()
		)
		{
			return false;
		}

		return true;
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'copilot-in-call-automatically',
				'title' => Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_AUTO_TITLE_MSGVER_1'),
				'text' => Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_AUTO_BODY_MSGVER_1'),
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Timeline.Call:onShowTourWhenManualStartTooMuch',
				'article' => 18799442,
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'showOverlayFromFirstStep' => true,
			'hideTourOnMissClick' => true,
			'disableBannerDispatcher' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}

	protected function isShowEnabled(): bool
	{
		return parent::isShowEnabled()
			&& AIManager::isAiCallAutomaticProcessingAllowed()
			&& AIManager::isBaasServiceHasPackage()
		;
	}
}
