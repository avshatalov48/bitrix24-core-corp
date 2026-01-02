<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserTable;
use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Kanban\StagesTable;

final class TimelineStage extends Stepper
{
	protected static $moduleId = "tasks";

	protected const LOCK_TIMEOUT = 1;

	public function execute(array &$option): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return self::FINISH_EXECUTION;
		}

		$processedUsers = $option['processed'] ?? 0;
		$limit = 100;

		$query = UserTable::query()
			->setSelect(['ID'])
			->setOrder(['ID' => 'ASC'])
			->setLimit($limit)
			->setOffset($processedUsers);

		$rows = $query->exec();
		$users = $rows->fetchAll();

		$count = count($users);

		$connection = Application::getConnection();

		foreach ($users as $user)
		{
			$userId = $user['ID'];
			$lockName = 'timeline_stage_update_lock_' . $userId;

			$connection->lock($lockName, self::LOCK_TIMEOUT);

			$data = StagesTable::getCompletedStage($userId);

			$existingStages = StagesTable::query()
				->setSelect(['SYSTEM_TYPE'])
				->where('ENTITY_ID', $userId)
				->where('ENTITY_TYPE', $data['ENTITY_TYPE'])
				->exec()
				->fetchAll();

			// if user doesn't activate timeline yet
			if (empty($existingStages))
			{
				$connection->unlock($lockName);
				continue;
			}

			$query = StagesTable::query()
				->setSelect(['ID'])
				->where('ENTITY_ID', $userId)
				->where('ENTITY_TYPE', $data['ENTITY_TYPE'])
				->where('SYSTEM_TYPE', $data['SYSTEM_TYPE'])
				->exec()
				->fetch();

			// if record already exists - skip
			if ($query)
			{
				$connection->unlock($lockName);
				continue;
			}

			try
			{
				StagesTable::add($data);
			}
			catch (\Exception $exception)
			{
			}
			finally
			{
				$connection->unlock($lockName);
			}
		}

		$option['processed'] = $processedUsers + $count;

		if ($count < $limit)
		{
			Option::set('tasks', 'timeline', 'v2');

			return self::FINISH_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}
}