<?php

namespace Bitrix\Crm\Counter\Monitor;

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

	public function getSignificantlyChangedEntities(): self
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
}
