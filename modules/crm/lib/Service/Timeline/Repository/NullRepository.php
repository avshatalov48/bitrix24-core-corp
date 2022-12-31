<?php

namespace Bitrix\Crm\Service\Timeline\Repository;

use Bitrix\Crm\Service\Timeline\Repository;

final class NullRepository extends Repository
{
	public function __construct()
	{
	}

	public function getScheduledItems(?Query $queryParams = null): Result
	{
		return (new Result());
	}

	public function getHistoryItemsPage(Query $queryParams): Result
	{
		return (new Result());
	}
}
