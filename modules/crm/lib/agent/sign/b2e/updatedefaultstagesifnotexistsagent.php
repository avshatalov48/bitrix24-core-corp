<?php

namespace Bitrix\Crm\Agent\Sign\B2e;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\FillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;

final class UpdateDefaultStagesIfNotExistsAgent extends AgentBase
{
	private const OLD_STATUSES_MAP = [
		'COORDINATION' => ['COORDINATION_AND_FILLING', 'NOTSIGNED'],
		'SIGNING' => ['PROCESSING', 'SEMISIGNED', 'SENT', 'SIGNING'],
		'COMPLETED' => ['SIGNED', 'COMPLETED'],
	];
	private const NEW_STATUSES = [
		'DRAFT' => '#00A9F4',
		'COORDINATION' => '#00C9FA',
		'FILLING' => '#00C4FB',
		'SIGNING' => '#00D3E2',
		'COMPLETED' => '#FEA300',
		'ARCHIVE' => '#7BD500',
		'FAILURE' => '#FF5752',
	];

	private const SYSTEM_STATUSES = [
		'DRAFT',
		'ARCHIVE',
		'FAILURE',
	];

	private const SEMANTIC_STATUSES = [
		'ARCHIVE' => PhaseSemantics::SUCCESS,
		'FAILURE' => PhaseSemantics::FAILURE,
	];

	private const DEFAULT_TRIGGERS = [
		CoordinationTrigger::class => 'COORDINATION',
		FillingTrigger::class => 'FILLING',
		SigningTrigger::class => 'SIGNING',
		CompletedTrigger::class => 'COMPLETED',
	];

	public static function doRun(): bool
	{
		$typeService = Container::getInstance()->getSignB2eTypeService();
		$statusService = Container::getInstance()->getSignB2eStatusService();
		$stageService = Container::getInstance()->getSignB2eStageService();
		$languageService = Container::getInstance()->getSignB2eLanguageService();
		$triggerService = Container::getInstance()->getSignB2eTriggerService();
		$itemService = Container::getInstance()->getSignB2eItemService();
		$defaultLanguage = $languageService->getDefaultLanguage();

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
			fn(string $status) => $statusService->makeName($defaultCategoryId, $status),
			array_keys(self::NEW_STATUSES)
		);

		$isStagesCreatedByNamesWithIncorrectLanguage = false;
		if ($defaultLanguage !== 'en')
		{
			$languageService->loadTranslations(self::class, 'en');
			$statusNamesEn = array_map(
				fn(string $status) => $languageService->getStatusMessage($status, 'en'),
				array_keys(self::NEW_STATUSES)
			);
			$isStagesCreatedByNamesWithIncorrectLanguage = $stageService->isStagesCreatedByNames($statusNamesEn, $defaultCategoryId);
		}

		if ($stageService->isStagesCreated($statuses) && $isStagesCreatedByNamesWithIncorrectLanguage === false)
		{
			return false;
		}

		$entityId = $stageService->getStageEntityId($defaultCategoryId);
		$languageService->loadTranslations(self::class, $defaultLanguage);
		$stageService->removeStagesByEntityId($entityId);
		$triggerService->removeAll();
		$sort = 10;
		foreach (self::NEW_STATUSES as $newStatus => $color)
		{
			$status = $statusService->makeName($defaultCategoryId, $newStatus);
			$statusMessage = $languageService->getStatusMessage($newStatus, $defaultLanguage);

			$stage = [
				'ENTITY_ID' => $entityId,
				'NAME' => $statusMessage,
				'NAME_INIT' => $statusMessage,
				'STATUS_ID' => $status,
				'SORT' => $sort,
				'SYSTEM' => in_array($newStatus, self::SYSTEM_STATUSES) ? 'Y' : 'N',
				'COLOR' => $color,
				'CATEGORY_ID' => $defaultCategoryId,
			];

			if (self::SEMANTIC_STATUSES[$newStatus] ?? null)
			{
				$stage['SEMANTICS'] = self::SEMANTIC_STATUSES[$newStatus];
			}

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

		return false;
	}
}
