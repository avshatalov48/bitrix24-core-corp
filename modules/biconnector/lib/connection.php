<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag;

class Connection extends \Bitrix\Main\DB\MysqliConnection
{
	/**
	 * @inheritDoc
	 */
	protected function queryInternal($sql, array $binds = null, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		if ($trackerQuery != null)
		{
			$trackerQuery->startQuery($sql, $binds);
		}

		if (preg_match('/^\s*select\s*/i', $sql))
		{
			$this->resource->real_query($sql);
			$result = $this->resource->store_result(MYSQLI_STORE_RESULT_COPY_DATA);
		}
		else
		{
			$result = $this->resource->query($sql, MYSQLI_STORE_RESULT);
		}

		if ($trackerQuery != null)
		{
			$trackerQuery->finishQuery();
		}

		$this->lastQueryResult = $result;

		if (!$result)
		{
			throw new SqlQueryException('Mysql query error', $this->getErrorMessage(), $sql);
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	protected function createResult($result, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new Result($result, $this, $trackerQuery);
	}
}
