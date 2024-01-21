<?php

namespace Bitrix\Im\V2\Controller;

use Bitrix\Im\V2\Error;
use Bitrix\Im\V2\Sync\SyncError;
use Bitrix\Im\V2\Sync\SyncService;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use DateTimeInterface;

class Sync extends BaseController
{
	public function listAction(array $filter, int $limit = 50): ?array
	{
		$syncService = new SyncService();

		if (isset($filter['lastDate']))
		{
			try
			{
				$date = new DateTime($filter['lastDate'], DateTimeInterface::RFC3339);
			}
			catch (ObjectException $exception)
			{
				$this->addError(new Error($exception->getCode(), $exception->getMessage()));

				return null;
			}

			return $syncService->getChangesFromDate($date, $limit);
		}

		if (isset($filter['lastId']))
		{
			return $syncService->getChangesFromId((int)$filter['lastId'], $limit);
		}

		$this->addError(new SyncError(SyncError::LAST_ID_AND_DATE_EMPTY));

		return null;
	}
}