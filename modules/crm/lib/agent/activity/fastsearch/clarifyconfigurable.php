<?php

namespace Bitrix\Crm\Agent\Activity\FastSearch;

use Bitrix\Crm\Activity\Entity\AppTypeTable;
use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Agent\Activity\FastSearchConfigurableSupportAgent;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class ClarifyConfigurable
{
	private const LAST_ID_OPTION_NAME = 'FAST_SEARCH_CONFIGURABLE_SUPPORT_AGENT_LAST_ID';

	private const BATCH_SIZE = 100;

	public function execute(): bool
	{
		if ($this->noNeedToRun())
		{
			return FastSearchConfigurableSupportAgent::AGENT_DONE_STOP_IT;
		}


		$activityIds = self::queryNextPartConfigurableActivitiesIds();

		if (empty($activityIds))
		{
			$this->clearLastId();
			return FastSearchConfigurableSupportAgent::AGENT_DONE_STOP_IT;
		}

		$this->setLastId(max($activityIds));

		$actRows = $this->queryClarifyConfigurableData($activityIds);

		if (empty($actRows))
		{
			return FastSearchConfigurableSupportAgent::AGENT_CONTINUE;
		}

		$this->updateFastSearchConfigurableData($actRows);

		return FastSearchConfigurableSupportAgent::AGENT_CONTINUE;
	}

	private function queryNextPartConfigurableActivitiesIds(): array
	{
		$rows = ActivityFastSearchTable::query()
			->setSelect(['ACTIVITY_ID'])
			->where('ACTIVITY_ID', '>', self::getLastId())
			->where('ACTIVITY_TYPE', 'CONFIGURABLE_REST_APP.*.*')
			->setLimit(self::BATCH_SIZE)
			->addOrder('ACTIVITY_ID')
			->fetchAll();

		return array_column($rows, 'ACTIVITY_ID');
	}

	private function queryClarifyConfigurableData(array $activityIds): array
	{
		return ActivityTable::query()
			->setSelect(['ID', 'PROVIDER_TYPE_ID'])
			->whereIn('ID', $activityIds)
			->whereNot('PROVIDER_TYPE_ID', 'CONFIGURABLE')
			->fetchAll();
	}

	private function updateFastSearchConfigurableData(array $activityRows): void
	{
		$connection = Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();

		$tableName = ActivityFastSearchTable::getTableName();

		$sql = '';
		foreach ($activityRows as $row)
		{
			$providerId = $row['PROVIDER_TYPE_ID'];
			[$where, $binds] = $sqlHelper->prepareUpdate(
				$tableName,
				['ACTIVITY_TYPE' => "CONFIGURABLE_REST_APP.$providerId.*"]
			);
			$sql .= sprintf("UPDATE %s SET %s WHERE ACTIVITY_ID = %s;\n", $tableName, $where, (int)$row['ID']);
		}

		$connection->startTransaction();
		try {
			$connection->executeSqlBatch($sql);
			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();
		}

	}

	private function getLastId(): int
	{
		return (int)Option::get('crm', self::LAST_ID_OPTION_NAME, 0);
	}

	private function setLastId(int $lastId): void
	{
		Option::set('crm', self::LAST_ID_OPTION_NAME, $lastId);
	}

	public function clearLastId(): void
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_NAME]);
	}

	private function noNeedToRun(): bool
	{
		// If agent already running do not run another checks
		if (Option::get('crm', self::LAST_ID_OPTION_NAME, null) !== null)
		{
			return false;
		}

		// Don't run when main fastsearch filler running
		if (ActivityFastsearchFiller::isRunning())
		{
			return true;
		}

		$typeQuery = AppTypeTable::query()
			->setSelect(['ID'])
			->where('IS_CONFIGURABLE_TYPE', 'Y')
			->setLimit(1);

		if ($typeQuery->fetch() === false)
		{
			return true;
		}

		return false;
	}
}
