<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector\Traits;

trait FilterByIds
{
	protected array $idsForFilter = [];

	protected function isFilterByIds(): bool
	{
		return (bool)count($this->idsForFilter);
	}

	protected function setIdsForFilter(array $ids = []): void
	{
		$this->idsForFilter = $ids;
	}

	protected function getIdsForFilter(): array
	{
		return $this->idsForFilter;
	}

	protected function getFilterIds(): array
	{
		if ($this->isFilterByIds())
		{
			return [
				'@ID' => $this->getIdsForFilter()
			];
		}

		return [];
	}
}
