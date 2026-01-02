<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Contract\Builder;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;

interface Filter
{
	public function prepareFilter(): ConditionTree;
}