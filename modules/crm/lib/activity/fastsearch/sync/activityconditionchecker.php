<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync;


use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchTable;
use Bitrix\Crm\Traits;
use Bitrix\Main\Type\DateTime;

class ActivityConditionChecker
{
	use Traits\Singleton;

	private DateTime $thresholdDate;

	public function __construct()
	{
		$this->thresholdDate = $this->calculateThresholdDate();
	}

	public function isSatisfiedByAdd(ActivityChangeSet $changeSet): bool
	{
		if ($changeSet->newAct()->type() === ActivitySearchData::TYPE_UNSUPPORTED)
		{
			return false;
		}

		if ($changeSet->newAct()->kind() === ActivitySearchData::KIND_UNSUPPORTED)
		{
			return false;
		}

		return true;
	}


	public function isSatisfiedByUpsert(ActivityChangeSet $changeSet): bool
	{
		if ($this->isSkipByCreated($changeSet->newAct()->created()))
		{
			return false;
		}

		if ($changeSet->newAct()->type() === ActivitySearchData::TYPE_UNSUPPORTED)
		{
			return false;
		}

		if ($changeSet->newAct()->kind() === ActivitySearchData::KIND_UNSUPPORTED)
		{
			return false;
		}

		return $changeSet->hasAnyChange();
	}

	public function isSatisfiedByRemoveDuringUpdate(ActivityChangeSet $changeSet): bool
	{
		if ($this->isSkipByCreated($changeSet->newAct()->created()))
		{
			return false;
		}

		if ($changeSet->isKindChanged() && $changeSet->newAct()?->kind() === ActivitySearchData::KIND_UNSUPPORTED)
		{
			return true;
		}

		if (
			$changeSet->isTypeChanged()
			&& $changeSet->newAct()?->type() === ActivitySearchData::TYPE_UNSUPPORTED
		)
		{
			return true;
		}

		return false;
	}

	public function isSatisfiedByRemove(DateTime $created): bool
	{
		return !$this->isSkipByCreated($created);
	}

	private function isSkipByCreated(DateTime $created): bool
	{
		$created = clone $created;
		$created = $created->setTime(0, 0);

		return $created->getTimestamp() < $this->thresholdDate->getTimestamp();
	}

	/**
	 * @return DateTime
	 */
	private function calculateThresholdDate(): DateTime
	{
		$days = ActivityFastSearchTable::CREATED_THRESHOLD_DAYS;
		return (new DateTime())->add("-P{$days}D");
	}
}