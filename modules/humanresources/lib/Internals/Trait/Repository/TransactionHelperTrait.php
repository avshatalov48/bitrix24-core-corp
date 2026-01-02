<?php

namespace Bitrix\HumanResources\Internals\Trait\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\DB\TransactionException;

trait TransactionHelperTrait
{
	/**
	 * @param \Closure $callback
	 */
	public function doTransactionally(\Closure $callback, bool $enableNestedTransactions = true): void
	{
		$connection = Application::getConnection();
		$connection->startTransaction();

		try
		{
			$callback();
			$connection->commitTransaction();
		}
		catch (TransactionException $e)
		{
			if ($enableNestedTransactions)
			{
				return;
			}

			$connection->rollbackTransaction();
		}
		catch (\Throwable $e)
		{
			$connection->rollbackTransaction();
			throw $e;
		}
	}
}