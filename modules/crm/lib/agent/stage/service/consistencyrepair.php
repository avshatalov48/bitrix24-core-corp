<?php

namespace Bitrix\Crm\Agent\Stage\Service;

use Bitrix\Crm\Agent\Stage\RepairServiceBase;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\StatusTable;

final class ConsistencyRepair extends RepairServiceBase
{
	protected function repair(int $entityTypeId): ?int
	{
		$factory = $this->getFactory($entityTypeId);
		if ($factory === null)
		{
			return null;
		}

		$items = $this->getItems($factory);
		if (empty($items))
		{
			return null;
		}

		foreach ($items as $item)
		{
			$itemStageId = $item->getStageId();
			$categoryId = $item->isCategoriesSupported() ? $item->getCategoryId() : null;
			$availableStages = $factory->getStages($categoryId);

			if ($availableStages->isEmpty())
			{
				$newStatuses = $this->createRequiredStatusesByDealDefaults($factory, $categoryId);
				foreach ($newStatuses as $newStatus)
				{
					$availableStages->add($newStatus);
				}
			}

			if (!in_array($itemStageId, $availableStages->getStatusIdList(), true))
			{
				$correctStage = $this->resolveCorrectStageBySemantics($availableStages, $item);

				$item->setStageId($correctStage->getStatusId());

				if ($item->hasField(Item::FIELD_NAME_STAGE_SEMANTIC_ID))
				{
					$semantics = PhaseSemantics::isDefined($correctStage->getSemantics())
						? $correctStage->getSemantics()
						: PhaseSemantics::PROCESS
					;

					$item->setStageSemanticId($semantics);
				}

				$item->save(false);
			}
		}

		/** @var Item $lastItem */
		$lastItem = end($items);

		return $lastItem->getId();
	}

	protected function getSelect(Factory $factory): array
	{
		$select = [
			Item::FIELD_NAME_ID,
			Item::FIELD_NAME_STAGE_ID,
		];

		if ($factory->isCategoriesSupported())
		{
			$select[] = Item::FIELD_NAME_CATEGORY_ID;
		}

		if ($factory->getFieldsCollection()->hasField(Item::FIELD_NAME_STAGE_SEMANTIC_ID))
		{
			$select[] = Item::FIELD_NAME_STAGE_SEMANTIC_ID;
		}

		return $select;
	}

	/**
	 * @param Factory $factory
	 * @param int|null $categoryId
	 * @return EO_Status[]
	 */
	private function createRequiredStatusesByDealDefaults(Factory $factory, ?int $categoryId = null): array
	{
		$entityTypeId = $factory->getEntityTypeId();
		$statusEntityId = $factory->getStagesEntityId($categoryId);

		$isDeal = $entityTypeId === \CCrmOwnerType::Deal;
		$isDynamic = \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId);

		$prefix = match(true){
			$isDeal => DealCategory::prepareStageNamespaceID($categoryId),
			$isDynamic => \CCrmStatus::getDynamicEntityStatusPrefix($entityTypeId, $categoryId),
			default => '',
		};

		$defaultDealStatuses = \CCrmStatus::GetDefaultDealStages($prefix);
		$basedStatuses = $this->filterRequiredStatuses($defaultDealStatuses);

		$statuses = [];
		foreach ($basedStatuses as $basedStatus)
		{
			$status = StatusTable::createObject()
				->setEntityId($statusEntityId)
				->setName($basedStatus['NAME'])
				->setStatusId($basedStatus['STATUS_ID'])
				->setSort($basedStatus['SORT'])
				->setSystem($basedStatus['SYSTEM'])
				->setColor($basedStatus['COLOR'])
				->setSemantics($basedStatus['SEMANTICS'] ?? PhaseSemantics::PROCESS)
			;

			$status->save();

			$statuses[] = $status;
		}

		return $statuses;
	}

	private function filterRequiredStatuses(array $statuses): array
	{
		$processStatus = null;
		$successStatus = null;
		$failureStatus = null;

		foreach ($statuses as $status)
		{
			$semantics = $status['SEMANTICS'] ?? PhaseSemantics::PROCESS;
			match ($semantics){
				PhaseSemantics::PROCESS => $processStatus ??= $status,
				PhaseSemantics::SUCCESS => $successStatus ??= $status,
				PhaseSemantics::FAILURE => $failureStatus ??= $status,
			};
		}

		return [
			$processStatus,
			$successStatus,
			$failureStatus,
		];
	}

	/**
	 * @param EO_Status_Collection $stages
	 * @param Item $item
	 * @return EO_Status
	 */
	private function resolveCorrectStageBySemantics(EO_Status_Collection $stages, Item $item): EO_Status
	{
		if (!$item->hasField(Item::FIELD_NAME_STAGE_SEMANTIC_ID))
		{
			return $stages->getAll()[0];
		}

		$itemSemantics = $item->getStageSemanticId() ?? PhaseSemantics::PROCESS;
		foreach ($stages->getAll() as $stage)
		{
			$stageSemantics = $stage->getSemantics() ?? PhaseSemantics::PROCESS;
			if ($itemSemantics === $stageSemantics)
			{
				return $stage;
			}
		}

		return $stages->getAll()[0];
	}
}
