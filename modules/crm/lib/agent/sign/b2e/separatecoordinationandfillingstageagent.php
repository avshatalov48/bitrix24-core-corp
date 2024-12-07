<?php

namespace Bitrix\Crm\Agent\Sign\B2e;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\FillingTrigger;
use Bitrix\Crm\Service\Container;

final class SeparateCoordinationAndFillingStageAgent extends AgentBase
{
	public const IS_DISABLED = true;
	private const OLD_STATUSES_MAP = [
		'COORDINATION' => ['COORDINATION_AND_FILLING'],
		'FILLING' => ['COORDINATION_AND_FILLING'],
	];

	private const OLD_STATUSES = [
		'COORDINATION_AND_FILLING',
	];

	private const NEW_STATUSES = [
		'COORDINATION' => '#00C9FA',
		'FILLING' => '#00C4FB',
	];

	private const DEFAULT_TRIGGERS = [
		CoordinationTrigger::class => 'COORDINATION',
		FillingTrigger::class => 'FILLING',
	];

	public static function doRun(): bool
	{
		if (self::IS_DISABLED)
		{
			return false;
		}

		$typeService = Container::getInstance()->getSignB2eTypeService();
		$statusService = Container::getInstance()->getSignB2eStatusService();
		$stageService = Container::getInstance()->getSignB2eStageService();
		$languageService = Container::getInstance()->getSignB2eLanguageService();
		$triggerService = Container::getInstance()->getSignB2eTriggerService();
		$itemService = Container::getInstance()->getSignB2eItemService();

		if (!$typeService->isCreated())
		{
			return false;
		}

		$defaultCategoryId = $typeService->getDefaultCategoryId();
		if (!$defaultCategoryId)
		{
			return false;
		}

		$statuses = array_map(
			fn (string $status) => $statusService->makeName($defaultCategoryId, $status),
			self::OLD_STATUSES
		);

		if (!$stageService->isStagesCreated($statuses))
		{
			return false;
		}

		$entityId = $stageService->getStageEntityId($defaultCategoryId);
		$defaultLanguage = $languageService->getDefaultLanguage();
		$languageService->loadTranslations(self::class, $defaultLanguage);
		$sort = 10;
		$newStagesOrder = [];
		foreach (self::OLD_STATUSES AS $oldStage)
		{
			$newStages = array_filter(self::OLD_STATUSES_MAP, fn(array $value) => in_array($oldStage, $value));
			$oldStatus = $stageService->getByStage($oldStage, $defaultCategoryId);
			if ($oldStatus === null)
			{
				continue;
			}
			foreach (array_keys($newStages) as $newStage)
			{
				$newStagesOrder[$newStage] = $oldStatus['SORT'] ?? 10;
			}
			$stageService->removeByStage($oldStage, $defaultCategoryId);
		}

		foreach (self::NEW_STATUSES as $newStatus => $color)
		{
			$status = $statusService->makeName($defaultCategoryId, $newStatus);
			$statusMessage = $languageService->getStatusMessage($newStatus, $defaultLanguage);
			$stage = [
				'ID' => '',
				'ENTITY_ID' => $entityId,
				'NAME' => $statusMessage,
				'NAME_INIT' => $statusMessage,
				'STATUS_ID' => $status,
				'SORT' => $newStagesOrder[$newStatus] ?? $sort,
				'SYSTEM' => 'N',
				'COLOR' => $color,
				'SEMANTICS' => '',
				'CATEGORY_ID' => $defaultCategoryId,
			];

			$sort += 10;
			$stageService->addStage($stage);
		}

		$triggers = $statusService->makeTriggerNames($defaultCategoryId, self::DEFAULT_TRIGGERS);
		$triggerService->addTriggers($triggers);

		//Set new stages for items
		foreach (self::OLD_STATUSES_MAP as $newStage => $oldStages)
		{
			$whereStageIds = array_map(
				fn (string $item) => $statusService->makeName($defaultCategoryId, $item),
				$oldStages
			);
			$newStageId = $statusService->makeName($defaultCategoryId, $newStage);
			$itemService->updateStageIdByStageIds($newStageId, $whereStageIds);
		}

		return true;
	}
}
