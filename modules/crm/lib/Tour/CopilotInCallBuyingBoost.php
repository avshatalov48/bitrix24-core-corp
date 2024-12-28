<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Main\Localization\Loc;

final class CopilotInCallBuyingBoost extends CopilotInCall
{
	protected const OPTION_NAME = 'copilot-in-call-buying-boost';

	protected int $numberOfViewsLimit = 1;

	protected function canShow(): bool
	{
		return $this->isShowEnabled() && !$this->isUserSeenTour();
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'copilot-in-call-buying-boost',
				'title' => Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_BUY_BOOST_TITLE_MSGVER_1'),
				'text' => Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_BUY_BOOST_BODY_MSGVER_1'),
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Timeline.Call:onShowTourWhenNeedBuyBoost',
				'infoHelperCode' => AIManager::AI_PACKAGES_EMPTY_SLIDER_CODE,
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'showOverlayFromFirstStep' => true,
			'disableBannerDispatcher' => true,
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
		return parent::isShowEnabled()
			&& AIManager::isAiCallAutomaticProcessingAllowed()
			&& !AIManager::isBaasServiceHasPackage()
		;
	}
}
