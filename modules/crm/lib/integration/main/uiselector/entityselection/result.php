<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection;

class Result
{
	private array $entities;
	private array $entitiesIDs;

	public function setEntities(array $entities): self
	{
		$this->entities = $entities;

		return $this;
	}

	public function setEntitiesIDs(array $entitiesIDs): self
	{
		$this->entitiesIDs = $entitiesIDs;

		return $this;
	}

	public function getEntities(): array
	{
		return $this->entities;
	}

	public function getEntitiesIDs(): array
	{
		return $this->entitiesIDs;
	}

	public function toArray(): array
	{
		return [
			$this->entities,
			$this->entitiesIDs,
		];
	}
}
