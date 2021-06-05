<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Main\Result;

abstract class Category implements \JsonSerializable
{
	abstract public function getId(): ?int;

	abstract public function getEntityTypeId(): int;

	abstract public function setEntityTypeId(int $entityTypeId): Category;

	abstract public function getName(): string;

	abstract public function setName(string $name): Category;

	abstract public function getSort(): int;

	abstract public function setSort(int $sort): Category;

	abstract public function setIsDefault(bool $isDefault): Category;

	abstract public function getIsDefault(): bool;

	abstract public function save(): Result;

	abstract public function delete(): Result;

	public function getData(): array
	{
		return [
			'ID' => $this->getId(),
			'NAME' => $this->getName(),
			'SORT' => $this->getSort(),
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'IS_DEFAULT' => $this->getIsDefault() ? 'Y' : 'N',
		];
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'sort' => $this->getSort(),
			'entityTypeId' => $this->getEntityTypeId(),
			'isDefault' => $this->getIsDefault(),
		];
	}

	public function getItemsFilter(array $filter = []): array
	{
		if($this->getIsDefault())
		{
			$filter[] = [
				'LOGIC' => 'OR',
				[
					'=CATEGORY_ID' => 0,
				],
				[
					'=CATEGORY_ID' => null,
				],
				[
					'=CATEGORY_ID' => $this->getId(),
				],
			];
		}
		else
		{
			$filter['=CATEGORY_ID'] = $this->getId();
		}

		return $filter;
	}
}