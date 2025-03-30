<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params;

use Bitrix\Main\ORM\Query\Query;

abstract class Filter implements FilterInterface
{
	public function prepareQuery(Query $query): void
	{}
}
