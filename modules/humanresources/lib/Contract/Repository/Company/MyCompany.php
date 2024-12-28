<?php

namespace Bitrix\HumanResources\Contract\Repository\Company;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Contract\ItemCollection;

interface MyCompany
{
	public function getById(int $id): ?Item;

	public function listByIds(array $ids): ItemCollection;
}