<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\Service\Container;

class EntitiesChangesCollection extends \Bitrix\Main\Type\Dictionary
{
	public function add(EntityChange $entityChange)
	{
		$id = $entityChange->getIdentifier()->getHash();
		$existedChange = $this->get($id);
		/** @var $existedChange EntityChange */
		if ($existedChange)
		{
			$existedChange->applyNewChange($entityChange);
		}
		else
		{
			$this->set($id, $entityChange);
		}
	}

	public function significantlyChangedEntitiesForEntityResponsible(): self
	{
		$result = new self();
		/** @var $entityChange EntityChange */
		foreach ($this->values as $entityChange)
		{
			if (
				$entityChange->wasEntityAddedOrDeleted()
				|| $entityChange->isAssignedByChanged()
				|| $entityChange->isStageSemanticIdChanged()
				|| $entityChange->isCategoryIdChanged()
			)
			{
				$result->add($entityChange);
			}
		}

		return $result;
	}

	public function onlyCategoryChangedEntities(): self
	{
		$result = new self();

		foreach ($this->values as $entityChange)
		{
			if (
				$entityChange->isCategoryIdChanged()
				&& !is_null($entityChange->getOldCategoryId())
			)
			{
				$result->add($entityChange);
			}
		}
		return $result;
	}

	public function onlyIdleSupportedEntities(): self
	{
		$result = new self();

		foreach ($this->values as $entityChange)
		{
			$entityTypeId = $entityChange->getIdentifier()->getEntityTypeId();
			$factory = Container::getInstance()->getFactory($entityTypeId);

			$isIdleEnable = $factory && $factory
					->getCountersSettings()
					->isIdleCounterEnabled();

			if (!$isIdleEnable)
			{
				continue;
			}

			if (
				$entityChange->wasEntityAddedOrDeleted()
				|| $entityChange->isAssignedByChanged()
				|| $entityChange->isStageSemanticIdChanged()
				|| $entityChange->isCategoryIdChanged()
			)
			{
				$result->add($entityChange);
			}
		}
		return $result;
	}
}
