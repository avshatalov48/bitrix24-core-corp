<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

interface FilterableByTypes
{
	public function filterByEntityTypeIds(array|int|null $ids): self;
	public function excludeEntityTypeIds(array|int|null $ids): self;
}
