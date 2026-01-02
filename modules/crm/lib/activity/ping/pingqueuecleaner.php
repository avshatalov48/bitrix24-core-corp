<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Activity\Ping;

use Bitrix\Crm\Model\ActivityPingQueueTable;
use Bitrix\Main\DB\Connection;

final class PingQueueCleaner
{
	public function __construct(private readonly Connection $connection)
	{
	}

	public function hasUnattainableItems(): bool
	{
		$sql = 'SELECT 1 FROM ' . $this->getTableName() . ' WHERE PING_DATETIME > \'9999-01-01\' LIMIT 1';

		return (bool)$this->connection->query($sql)->fetch();
	}

	public function deleteUnattainableItems(int $limit = 100): void
	{
		$sql = 'DELETE FROM ' . $this->getTableName() . ' WHERE PING_DATETIME > \'9999-01-01\' LIMIT ' . $limit;
		$this->connection->queryExecute($sql);
	}

	private function getTableName(): string
	{
		$sqlHelper = $this->connection->getSqlHelper();

		return $sqlHelper->quote(ActivityPingQueueTable::getTableName());
	}
}
