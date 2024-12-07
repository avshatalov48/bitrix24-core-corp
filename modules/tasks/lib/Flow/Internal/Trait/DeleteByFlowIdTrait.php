<?php

namespace Bitrix\Tasks\Flow\Internal\Trait;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\DB\SqlQueryException;

trait DeleteByFlowIdTrait
{
	/**
	 * @throws SqlQueryException
	 */
	public static function deleteByFlowId(int $flowId, string $field = 'FLOW_ID'): Result
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$table = static::getTableName();

		return $connection->query(
			"delete from {$table} where {$helper->quote($field)} = {$flowId}"
		);
	}
}