<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document\OnlyOffice\Enum;
use Bitrix\Disk\Document\OnlyOffice\Models\RestrictionLog;
use Bitrix\Disk\Document\OnlyOffice\Models\RestrictionLogTable;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;

final class RestrictionManager
{
	public const DEFAULT_LIMIT = 10;
	public const UNLIMITED_VALUE = -1;
	public const LOCK_NAME = 'oo_edit_restriction';
	public const TTL = 4 * 3600;
	public const TTL_PENDING = 2*60;

	protected const LOCK_LIMIT = 15;

	public function __construct()
	{
	}

	public function shouldUseRestriction(): bool
	{
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return false;
		}

		return $this->getLimit() !== self::UNLIMITED_VALUE;
	}

	public function getLimit(): int
	{
		$value = Bitrix24Manager::getFeatureVariable('disk_oo_edit_restriction');

		return $value ?? self::UNLIMITED_VALUE;
	}

	public function lock(): bool
	{
		$connection = Application::getConnection();

		return $connection->lock(self::LOCK_NAME, self::LOCK_LIMIT);
	}

	public function unlock(): void
	{
		$connection = Application::getConnection();

		$connection->unlock(self::LOCK_NAME);
	}

	public function isAllowedEdit(string $documentKey, int $userId): bool
	{
		$limit = $this->getLimit();
		if ($limit === self::UNLIMITED_VALUE)
		{
			return true;
		}

		$countSessions = $this->countSessions();
		if ($this->isEnoughToDeletePendingUsages($limit, $countSessions))
		{
			$this->addJobToCleanPendingUsages();
		}

		if ($limit > $countSessions)
		{
			return true;
		}

		if ($this->existsSession($documentKey, $userId))
		{
			return true;
		}

		return false;
	}

	protected function isEnoughToDeletePendingUsages(int $limit, int $countSessions): bool
	{
		return $countSessions > ($limit / 2);
	}

	protected function addJobToCleanPendingUsages(): void
	{
		Application::getInstance()->addBackgroundJob(fn () => $this->deletePendingUsages());
	}

	public function registerUsage(string $documentKey, int $userId): void
	{
		$restrictionLog = new RestrictionLog();
		$restrictionLog
			->setUserId($userId)
			->setExternalHash($documentKey);

		$restrictionLog->save();
	}

	public function processHookData(int $status, array $hookData): void
	{
		$documentKey = $hookData['key'] ?? null;
		if (!$documentKey)
		{
			return;
		}

		$usersWhoFinished = [];
		$actions = $hookData['actions'] ?? [];
		foreach ($actions as $action)
		{
			$type = $action['type'] ?? null;
			$userId = (int)($action['userid'] ?? null);

			if ($type === Enum\UserAction::DISCONNECT)
			{
				$usersWhoFinished[] = $userId;
			}
		}

		if ($this->isDocumentClosed($status))
		{
			$this->deleteEntriesByExternalHash($documentKey);

			return;
		}

		$this->updateEntriesActivityByDocumentKey($documentKey);
		if ($status === Enum\Status::IS_BEING_EDITED)
		{
			$this->deleteUserEntriesByDocumentKey($usersWhoFinished, $documentKey);
		}
	}

	protected function isDocumentClosed(int $status): bool
	{
		return in_array($status, [
			Enum\Status::IS_READY_FOR_SAVE,
			Enum\Status::ERROR_WHILE_SAVING,
			Enum\Status::CLOSE_WITHOUT_CHANGES,
		], true);
	}

	protected function updateEntriesActivityByDocumentKey(string $documentKey): void
	{
		$filter = [
			'=EXTERNAL_HASH' => $documentKey,
		];

		RestrictionLogTable::updateBatch([
			'UPDATE_TIME' => new DateTime(),
			'STATUS' => RestrictionLogTable::STATUS_USED,
		], $filter);
	}

	protected function deleteUserEntriesByDocumentKey(array $userIds, string $documentKey): void
	{
		if (!$userIds)
		{
			return;
		}

		$this->deleteEntriesByExternalHash($documentKey, $userIds);
	}

	protected function deleteEntriesByExternalHash(string $documentKey, array $userIds = null): void
	{
		$connection = Application::getConnection();
		$tableName = RestrictionLogTable::getTableName();
		$sqlHelper = $connection->getSqlHelper();
		$documentKey = $sqlHelper->forSql($documentKey);

		$sql = "
			DELETE FROM {$tableName} WHERE EXTERNAL_HASH = '{$documentKey}' 
		";

		if ($userIds !== null)
		{
			$userIdsString = implode(',', $userIds);
			$sql .= " AND USER_ID IN ({$userIdsString})";
		}

		$connection->queryExecute($sql);
	}

	protected function countSessions(): int
	{
		return RestrictionLogTable::query()
			->queryCountTotal();
	}

	protected function existsSession(string $documentKey, int $userId): bool
	{
		$countSession = RestrictionLogTable::query()
			->where('EXTERNAL_HASH', $documentKey)
			->where('USER_ID', $userId)
			->queryCountTotal();

		return $countSession > 0;
	}

	public function deletePendingUsages(): void
	{
		$connection = Application::getConnection();
		$tableName = RestrictionLogTable::getTableName();
		$ttlTimeForPending = $connection->getSqlHelper()->convertToDbDateTime(
			DateTime::createFromTimestamp(time() - self::TTL_PENDING)
		);
		$statusPending = RestrictionLogTable::STATUS_PENDING;

		$connection->query( "
			DELETE FROM {$tableName}
			WHERE 
				UPDATE_TIME < {$ttlTimeForPending} AND STATUS = {$statusPending}
		");

	}

	public static function deleteOldOrPendingAgent(): string
	{
		self::deleteOldOrPending();

		return self::class . '::deleteOldOrPendingAgent();';
	}

	public static function deleteOldOrPending(): void
	{
		$connection = Application::getConnection();
		$tableName = RestrictionLogTable::getTableName();
		$ttlTime = $connection->getSqlHelper()->convertToDbDateTime(
			DateTime::createFromTimestamp(time() - self::TTL)
		);
		$ttlTimeForPending = $connection->getSqlHelper()->convertToDbDateTime(
			DateTime::createFromTimestamp(time() - self::TTL_PENDING)
		);
		$statusPending = RestrictionLogTable::STATUS_PENDING;

		$connection->query("
			DELETE FROM {$tableName}
			WHERE 
				UPDATE_TIME < {$ttlTime} OR (UPDATE_TIME < {$ttlTimeForPending} AND STATUS = {$statusPending})
		");
	}
}