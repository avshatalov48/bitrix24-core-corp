<?php

namespace Bitrix\Crm\Agent\Stage\Service;

use Bitrix\Crm\Agent\Stage\RepairServiceBase;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Factory;

final class SemanticsRepair extends RepairServiceBase
{
	protected function repair(int $entityTypeId): ?int
	{
		if (!$this->isSuitableEntity($entityTypeId))
		{
			return null;
		}

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
			if ($this->isCorrectSemantics($item))
			{
				continue;
			}

			$semantics = PhaseSemantics::PROCESS;

			$stageId = $item->getStageId();
			if ($stageId !== null)
			{
				$semantics = $factory->getStageSemantics($stageId) ?? PhaseSemantics::PROCESS;
			}

			$item
				->setStageSemanticId($semantics)
				->save(false)
			;
		}

		/** @var Item $lastItem */
		$lastItem = end($items);

		return $lastItem->getId();
	}

	protected function getSelect(Factory $factory): array
	{
		return [
			Item::FIELD_NAME_ID,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_STAGE_SEMANTIC_ID,
		];
	}

	protected function getFilter(Factory $factory): array
	{
		return [
			Item::FIELD_NAME_STAGE_SEMANTIC_ID => '',
		];
	}

	private function isCorrectSemantics(Item $item): bool
	{
		return PhaseSemantics::isDefined($item->getStageSemanticId());
	}

	private function isSuitableEntity(int $entityTypeId): bool
	{
		return
			$entityTypeId === \CCrmOwnerType::Lead
			|| $entityTypeId === \CCrmOwnerType::Deal
		;
	}
}
