<?php

namespace Bitrix\Disk\Internals\Steppers;

use Bitrix\Disk\Internals\DeletedLogTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

final class DeletedLogMover extends Stepper
{
	const PORTION = 100000;

	const STATUS_DONE  = false;
	const STATUS_PAUSE = 'P';
	const STATUS_INDEX = 'Y';

	protected $portionSize = self::PORTION;
	protected static $moduleId = 'disk';

	public static function getStatus()
	{
		return Option::get('disk', 'deleted_log_migrate', self::STATUS_DONE);
	}

	public static function pauseExecution()
	{
		Option::set('disk', 'deleted_log_migrate', self::STATUS_PAUSE);
	}

	public static function stopExecution()
	{
		Option::delete('disk', ['name' => 'deleted_log_migrate']);
		Option::delete('disk', ['name' => 'deleted_log_migrate_data']);
	}

	public static function continueExecution()
	{
		$status = self::getStatus();
		if ($status === self::STATUS_INDEX || $status === self::STATUS_PAUSE)
		{
			Option::set('disk', 'deleted_log_migrate', self::STATUS_INDEX);
			self::bind();

			return true;
		}

		return false;
	}

	public static function continueExecutionWithoutAgent($portion = self::PORTION)
	{
		$status = self::getStatus();
		if ($status === self::STATUS_INDEX || $status === self::STATUS_PAUSE)
		{
			Option::set('disk', 'deleted_log_migrate', self::STATUS_INDEX);

			$resultData = [];
			$indexer = new static();
			$indexer
				->setPortionSize($portion)
				->execute($resultData)
			;

			return true;
		}

		return false;
	}

	public function setPortionSize($portionSize)
	{
		$this->portionSize = $portionSize;

		return $this;
	}

	public function execute(array &$result)
	{
		$statusAgent = self::getStatus();
		if ($statusAgent === self::STATUS_DONE || $statusAgent === self::STATUS_PAUSE)
		{
			return self::FINISH_EXECUTION;
		}

		$status = $this->loadCurrentStatus();
		$lastId = (int)$status['lasId'];

		$newStatus = [
			'lasId' => $status['lasId'],
		];

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$sql = $helper->getInsertIgnore(
			'b_disk_deleted_log_v2',
			' (ID, USER_ID, STORAGE_ID, OBJECT_ID, TYPE, CREATE_TIME) ',
			"SELECT ID, USER_ID, STORAGE_ID, OBJECT_ID, TYPE, CREATE_TIME 
				FROM b_disk_deleted_log
				WHERE ID < {$lastId} 
				ORDER BY ID DESC
				LIMIT {$this->portionSize}"
		);
		$connection->queryExecute($sql);

		$newStatus['lasId'] = $connection->queryScalar("
			SELECT MIN(t.ID)
			FROM (
				SELECT l.ID FROM b_disk_deleted_log l
				WHERE l.ID < {$lastId}
				ORDER BY l.ID DESC
				LIMIT {$this->portionSize}
			) t
		");

		if ($newStatus['lasId'] > 0)
		{
			Option::set('disk', 'deleted_log_migrate_data', serialize($newStatus));
			$result = [];

			return self::CONTINUE_EXECUTION;
		}

		self::stopExecution();
		$connection->queryExecute('TRUNCATE TABLE b_disk_deleted_log');

		return self::FINISH_EXECUTION;
	}

	public function loadCurrentStatus()
	{
		$status = Option::get('disk', 'deleted_log_migrate_data', 'default');
		$status = ($status !== 'default' ? @unserialize($status, ['allowed_classes' => false]) : []);
		$status = (is_array($status) ? $status : []);

		if (empty($status))
		{
			$connection = Application::getConnection();
			$status = [
				'lasId' => $connection->queryScalar('SELECT MAX(ID) + 1 as m FROM b_disk_deleted_log'),
			];
		}

		return $status;
	}
}