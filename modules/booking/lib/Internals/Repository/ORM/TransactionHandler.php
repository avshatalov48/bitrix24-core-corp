<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Repository\TransactionHandlerInterface;
use Bitrix\Main\Application;

class TransactionHandler implements TransactionHandlerInterface
{
	public function handle(callable $fn, string $errType = null): mixed
	{
		Application::getConnection()->startTransaction();

		try
		{
			$result = $fn();

			Application::getConnection()->commitTransaction();

			return $result;
		}
		catch (\Throwable $e)
		{
			Application::getConnection()->rollbackTransaction();

			if ($errType)
			{
				throw new $errType($e->getMessage());
			}

			throw $e;
		}
	}
}
