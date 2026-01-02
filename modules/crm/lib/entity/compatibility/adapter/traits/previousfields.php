<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter\Traits;

trait PreviousFields
{
	/** @var Array<int, array> */
	private array $previousEntities = [];

	public function setPreviousFields(int $id, array $previousFields): self
	{
		$this->previousEntities[$id] = $previousFields;

		return $this;
	}

	private function getPreviousFields(int $id): ?array
	{
		return $this->previousEntities[$id] ?? null;
	}
}
