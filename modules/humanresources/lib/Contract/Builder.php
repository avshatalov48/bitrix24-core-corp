<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Contract;

use Bitrix\Main\ORM\Query\Query;

interface Builder
{
	public function prepareQuery(): Query;
}