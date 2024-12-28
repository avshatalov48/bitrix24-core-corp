<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity\Trait;

/** @implements \Bitrix\Crm\Security\Role\Manage\Entity\FilterableByTypes */
trait FilterableByTypes
{
	protected array|null $filterByEntityTypeIds = null;
	protected array|null $excludeEntityTypeIds = null;

	public function filterByEntityTypeIds(array|int|null $ids = null): self
	{
		if (!is_array($ids))
		{
			$ids = [ $ids ];
		}

		$this->filterByEntityTypeIds = $ids;

		return $this;
	}

	public function excludeEntityTypeIds(array|int|null $ids = null): self
	{
		if (!is_array($ids))
		{
			$ids = [ $ids ];
		}

		$this->excludeEntityTypeIds = $ids;

		return $this;
	}
}
