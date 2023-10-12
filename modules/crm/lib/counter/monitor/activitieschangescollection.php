<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\ItemIdentifier;

class ActivitiesChangesCollection extends \Bitrix\Main\Type\Dictionary
{
	public function add(ActivityChange $activityChange)
	{
		$id = $activityChange->getId();
		$existedChange = $this->get($id);
		/** @var $existedChange ActivityChange */
		if ($existedChange)
		{
			$existedChange->applyNewChange($activityChange);
		}
		else
		{
			$this->set($id, $activityChange);
		}
	}

	public function getSignificantlyChangedActivities(bool $withResponsible = false): self
	{
		$result = new self();
		/** @var $activityChange ActivityChange */
		foreach ($this->values as $activityChange)
		{
			if (
				$activityChange->isIncomingChannelChanged()
				|| $activityChange->isDeadlineChanged()
				|| $activityChange->isCompletedChanged()
				|| $activityChange->areBindingsChanged()
				|| $activityChange->isLightTimeChanges()
				|| ($withResponsible && $activityChange->isResponsibleIdChanged())
			)
			{
				$result->add($activityChange);
			}
		}

		return $result;
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function getAffectedBindings(): array
	{
		$result = [];
		/** @var $activityChange ActivityChange */
		foreach ($this->values as $activityChange)
		{
			foreach ($activityChange->getOldBindings() as $binding)
			{
				$result[$binding->getHash()] = $binding;
			}
			foreach ($activityChange->getNewBindings() as $binding)
			{
				$result[$binding->getHash()] = $binding;
			}
		}

		return array_values($result);
	}
}
