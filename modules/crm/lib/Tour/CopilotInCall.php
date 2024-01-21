<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class CopilotInCall extends Base
{
	protected const OPTION_NAME = 'copilot-button-in-call';

	private static bool $isCopilotTourShown = false;
	private ?int $entityTypeId = null;

	public function setEntityTypeId(?int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function isCopilotTourCanShow(): bool
	{
		if (static::$isCopilotTourShown)
		{
			return false;
		}

		static::$isCopilotTourShown = true;
		
		return $this->canShow();
	}

	protected function canShow(): bool
	{
		return AIManager::isAiCallProcessingEnabled()
			&& in_array($this->entityTypeId, AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
			&& !$this->isUserSeenTour()
		;
	}

	protected function getSteps(): array
	{
		$entityName = CCrmOwnerType::ResolveName($this->entityTypeId);

		return [
			[
				'id' => 'copilot-button-in-call',
				'title' => Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_TITLE'),
				'text' => sprintf(
					'%s %s',
					Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_BODY_MAIN'),
					Loc::getMessage("CRM_TOUR_COPILOT_IN_CALL_BODY_$entityName"),
				),
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Timeline.Call:onShowCopilotTour',
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
}
