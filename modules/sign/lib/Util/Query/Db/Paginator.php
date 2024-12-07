<?php

namespace Bitrix\Sign\Util\Query\Db;

class Paginator
{
	public static function getLimitAndOffset(int $numberOfRecordsPerPage, int $pageNumber): array
	{
		return [$numberOfRecordsPerPage, ($pageNumber - 1) * $numberOfRecordsPerPage];
	}
}