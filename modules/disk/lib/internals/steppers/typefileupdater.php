<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals\Steppers;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Update\Stepper;

class TypeFileUpdater extends Stepper
{
	private const ROWS_PER_STEP = 100;

	protected static $moduleId = 'disk';

	private function calculateAffectedRowsCount(): ?int
	{
		$connection = Application::getConnection();
		$sql = 'SELECT COUNT(ID) FROM b_disk_tracked_object WHERE TYPE_FILE IS NULL';
		$count = $connection->queryScalar($sql);

		if (is_null($count))
		{
			return null;
		}

		return (int)$count;
	}

	public function execute(array &$result): bool
	{
		if (empty($result))
		{
			$result['steps'] = 0;
			$result['count'] = $this->calculateAffectedRowsCount();
			if (!$result['count'])
			{
				return self::FINISH_EXECUTION;
			}
		}

		$sql = 'UPDATE b_disk_tracked_object track
					SET track.TYPE_FILE = (
						SELECT obj.TYPE_FILE
						FROM b_disk_object obj
						WHERE obj.ID = track.OBJECT_ID
					)
				WHERE track.TYPE_FILE IS NULL
				ORDER BY track.ID DESC
				LIMIT ' . self::ROWS_PER_STEP;

		$connection = Application::getConnection();
		try
		{
			$connection->query($sql);
		}
		catch (SqlQueryException $exception)
		{
			$this->writeToLog($exception);
			return self::FINISH_EXECUTION;
		}

		$count = $connection->getAffectedRowsCount();
		$result['steps'] += $count;

		if ($count < self::ROWS_PER_STEP)
		{
			return self::FINISH_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}

}