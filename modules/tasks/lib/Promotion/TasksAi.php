<?php

namespace Bitrix\Tasks\Promotion;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Bitrix24\LeftMenuPreset;
use Bitrix\Tasks\Integration\Bitrix24\Portal;

class TasksAi extends AbstractPromotion
{
	public function getPromotionType(): PromotionType
	{
		return PromotionType::TASKS_AI;
	}

	public function shouldShow(int $userId): bool
	{
		if (!Loader::includeModule('ai') || $this->isViewed($userId))
		{
			return false;
		}

		$tasksAiPresetCode = (new LeftMenuPreset())->getTasksAiCode();
		$currentPresetCode = Option::get('intranet', 'left_menu_preset');

		if (is_null($tasksAiPresetCode) || $currentPresetCode !== $tasksAiPresetCode)
		{
			return false;
		}

		$region = Application::getInstance()->getLicense()->getRegion();

		if ($region === 'cn')
		{
			return false;
		}

		$portalCreateDate = (new Portal())->getCreationDateTime();
		$suitablePortalCreationDate = $this->getMinimumSuitablePortalCreationDate();

		return $portalCreateDate?->getTimestamp() > $suitablePortalCreationDate->getTimestamp();
	}

	private function getMinimumSuitablePortalCreationDate(): DateTime
	{
		return new DateTime('2024-11-26', 'Y-m-d');
	}
}