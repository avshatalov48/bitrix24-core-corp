<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class CopilotInCall extends Base
{
	protected const OPTION_NAME = 'copilot-button-in-call';

	protected int $numberOfViewsLimit = 3;

	private static bool $isWelcomeTourEnabled = false;
	protected ?int $entityTypeId = null;

	public function setEntityTypeId(?int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
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

	protected function canShow(): bool
	{
		if (!$this->isShowEnabled())
		{
			return false;
		}

		if ($this->isUserSeenTour())
		{
			$userId = Container::getInstance()->getContext()->getUserId();
			if (JobRepository::getInstance()->isUserHasJobs($userId))
			{
				return false;
			}

			return true;
		}

		return true;
	}

	protected function getSteps(): array
	{
		$entityName = CCrmOwnerType::ResolveName($this->entityTypeId);

		return [
			[
				'id' => 'copilot-button-in-call',
				'title' => Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_TITLE_MSGVER_1'),
				'text' => sprintf(
					'%s %s',
					Loc::getMessage("CRM_TOUR_COPILOT_IN_CALL_BODY_{$entityName}_MSGVER_1"),
					Loc::getMessage('CRM_TOUR_COPILOT_IN_CALL_BODY_MAIN_MSGVER_1'),
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
			'numberOfViewsLimit' => $this->numberOfViewsLimit,
			'isNumberOfViewsExceeded' => $this->isNumberOfViewsExceeded(),
			'additionalTourIdsForDisable' => [
				CopilotRunAutomatically::getInstance()->getOptionName(),
				CopilotRunManually::getInstance()->getOptionName(),
			],
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}

	protected function isShowEnabled(): bool
	{
		return AIManager::isAiCallProcessingEnabled()
			&& in_array(
				$this->entityTypeId,
				AIManager::SUPPORTED_ENTITY_TYPE_IDS,
				true
			)
		;
	}
}
