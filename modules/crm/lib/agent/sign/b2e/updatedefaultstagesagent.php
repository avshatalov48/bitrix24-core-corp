<?php

namespace Bitrix\Crm\Agent\Sign\B2e;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationAndFillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use LogicException;

final class UpdateDefaultStagesAgent extends AgentBase
{
	public const IS_DISABLED = true;

	private const OLD_STATUSES_MAP = [
		'COORDINATION_AND_FILLING' => ['NOTSIGNED'],
		'SIGNING' => ['PROCESSING', 'SEMISIGNED', 'SENT'],
		'COMPLETED' => ['SIGNED'],
	];
	private const NEW_STATUSES = [
		'DRAFT' => '#00A9F4',
		'COORDINATION_AND_FILLING' => '#00C9FA',
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
		SigningTrigger::class => 'SIGNING',
		CoordinationAndFillingTrigger::class => 'COORDINATION_AND_FILLING',
		CompletedTrigger::class => 'COMPLETED',
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

		$statuses = array_map(fn ($status) => $statusService->makeName($defaultCategoryId, $status),
			array_keys(self::NEW_STATUSES));

		if ($stageService->isStagesCreated($statuses))
		{
			return false;
		}

		$entityId = $stageService->getStageEntityId($defaultCategoryId);
		$defaultLanguage = $languageService->getDefaultLanguage();
		$languageService->loadTranslations(self::class, $defaultLanguage);
		$stageService->removeStagesByEntityId($entityId);
		$triggerService->removeAll();
		$sort = 10;
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
				'SORT' => $sort,
				'SYSTEM' => in_array($newStatus, self::SYSTEM_STATUSES) ? 'Y' : 'N',
				'COLOR' => $color,
				'SEMANTICS' => self::SEMANTIC_STATUSES[$newStatus] ?? '',
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
			$whereStageIds = array_map(static function ($item) use ($defaultCategoryId, $statusService) {
				return $statusService->makeName($defaultCategoryId, $item);
			}, $oldStages);
			$newStageId = $statusService->makeName($defaultCategoryId, $newStage);
			$itemService->updateStageIdByStageIds($newStageId, $whereStageIds);
		}

		return true;
	}
}
