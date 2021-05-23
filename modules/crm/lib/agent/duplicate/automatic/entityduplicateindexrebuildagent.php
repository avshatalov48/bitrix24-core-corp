<?php

namespace Bitrix\Crm\Agent\Duplicate\Automatic;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Integrity;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

abstract class EntityDuplicateIndexRebuildAgent extends AgentBase
{
	public static function doRun()
	{
		$instance = new static();
		$instance->runUserDuplicateIndexAgents();
		return true;
	}

	abstract public function getEntityTypeId(): int;

	public function runUserDuplicateIndexAgents(): void
	{
		if (!Integrity\AutoSearchUserSettings::isEnabled())
		{
			return;
		}

		$items = Integrity\Entity\AutosearchUserSettingsTable::query()
			->where('ENTITY_TYPE_ID', $this->getEntityTypeId())
			->where('NEXT_EXEC_TIME', '<', (new DateTime())->add('15 minutes'))
			->whereIn('STATUS_ID', [
				\Bitrix\Crm\Integrity\AutoSearchUserSettings::STATUS_NEW,
				\Bitrix\Crm\Integrity\AutoSearchUserSettings::STATUS_READY_TO_MERGE,
				\Bitrix\Crm\Integrity\AutoSearchUserSettings::STATUS_CONFLICTS_RESOLVING
			])
			->setOrder(['NEXT_EXEC_TIME' => 'asc'])
			->setOffset(0)
			->setLimit($this->getLimit())
			->exec();

		while ($userSettings = $items->fetchObject())
		{
			if ($userSettings->getStatusId() !== Integrity\AutoSearchUserSettings::STATUS_NEW)
			{
				$userSettings
					->setStatusId(Integrity\AutoSearchUserSettings::STATUS_NEW)
					->save();
			}
			if (!Integrity\AutoSearchUserSettings::hasAccess($userSettings->getEntityTypeId(), $userSettings->getUserId()))
			{
				$userSettings
					->setNextExecTime((new DateTime())->add('1 day'))
					->save();

				continue;
			}
			RebuildUserDuplicateIndexAgent::add((int)$userSettings->getEntityTypeId(), (int)$userSettings->getUserId());
		}
	}

	protected static function getLimit()
	{
		$limit = (int)Option::get("crm", "~duplicate_autosearch_user_check_limit", 10);
		return $limit > 0 ? $limit : 10;
	}
}