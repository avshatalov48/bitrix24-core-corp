<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync;


use Bitrix\Crm\Activity\FastSearch\Sync\Command;
use Bitrix\Crm\Traits;
use Bitrix\Main\Type\DateTime;

final class Monitor
{
	use Traits\Singleton;

	private ActivityConditionChecker $checker;

	private bool $processChangesInBackground = true;

	private function __construct()
	{
		$this->checker = ActivityConditionChecker::getInstance();
	}

	public function onActivityAdd(ActivityChangeSet $changeSet): void
	{
		if ($this->checker->isSatisfiedByAdd($changeSet))
		{
			$this->processCommand(new Command\Add($changeSet->newAct()));
		}
	}

	public function onActivityUpdate(ActivityChangeSet $changeSet): void
	{
		if ($this->checker->isSatisfiedByRemoveDuringUpdate($changeSet))
		{
			$id = $changeSet->newAct()->id();
			$this->processCommand(new Command\Remove($id));
			return;
		}

		if ($this->checker->isSatisfiedByUpsert($changeSet))
		{
			$this->processCommand(new Command\Upsert($changeSet->newAct()));
		}
	}

	public function onActivityDelete(int $activityId, array $activityFields): void
	{
		$created = DateTime::createFromUserTime($activityFields['CREATED']);

		if ($this->checker->isSatisfiedByRemove($created))
		{
			$this->processCommand(new Command\Remove($activityId));
		}
	}

	private function processCommand(Command\SyncCommand $command): void
	{
		$command->execute();
	}

}