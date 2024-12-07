<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\SystemException;
use Exception;

class SequenceService
{
	use Singleton;

	public function nextDynamicTypeId(): int
	{
		$connection = Application::getConnection();

		$connection->startTransaction();

		try {
			$connection->queryExecute(
				"select SEQUENCE_VALUE from b_crm_sequences where SEQUENCE_NAME = 'dynamic_type_id' FOR UPDATE;"
			);
			$connection->queryExecute(
				"update b_crm_sequences set SEQUENCE_VALUE = SEQUENCE_VALUE + 2 where SEQUENCE_NAME = 'dynamic_type_id';"
			);
			$res = $connection->query(
				"select SEQUENCE_VALUE from b_crm_sequences where SEQUENCE_NAME = 'dynamic_type_id';"
			);

			$row = $res->fetch();
			$nextId = (int)$row['SEQUENCE_VALUE'];
			$connection->commitTransaction();
		}
		catch (Exception $e)
		{
			$connection->rollbackTransaction();
			throw new SystemException('crm sequence generation error');
		}

		return $nextId;
	}
}