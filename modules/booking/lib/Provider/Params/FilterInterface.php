<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

interface FilterInterface
{
	public function prepareFilter(): ConditionTree;

	public function prepareQuery(Query $query): void;
}
