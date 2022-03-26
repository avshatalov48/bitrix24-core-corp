<?php

namespace Bitrix\Crm\Conversion;

class SchemeItem
{
	protected $entityTypeIds = [];
	protected $phrase;
	protected $id;
	protected $name;

	/**
	 * @param int[] $entityTypeIds
	 * @param string $phrase
	 */
	public function __construct(array $entityTypeIds, string $phrase)
	{
		$this->entityTypeIds = $entityTypeIds;
		sort($this->entityTypeIds);
		$this->phrase = $phrase;
	}

	public function getEntityTypeIds(): array
	{
		return $this->entityTypeIds;
	}

	public function getPhrase(): string
	{
		return $this->phrase;
	}

	public function setId($schemeId): self
	{
		$this->id = $schemeId;

		return $this;
	}

	public function getId(): string
	{
		if (!empty($this->id))
		{
			return (string)$this->id;
		}

		return implode('|', $this->entityTypeIds);
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getName(): string
	{
		if (!empty($this->name))
		{
			return $this->name;
		}

		$entityNames = [];
		foreach ($this->entityTypeIds as $typeId)
		{
			$entityNames[] = \CCrmOwnerType::ResolveName($typeId);
		}

		return implode('|', $entityNames);
	}

	public function toJson(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'entityTypeIds' => $this->getEntityTypeIds(),
			'phrase' => $this->getPhrase(),
		];
	}
}
