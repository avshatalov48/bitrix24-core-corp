<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

interface FilterableByCategory
{
	public function filterByCategory(?int $id = null): self;
}
