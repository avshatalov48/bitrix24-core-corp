<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Result;

abstract class Category implements \JsonSerializable
{
	abstract public function getId(): ?int;

	abstract public function getEntityTypeId(): int;

	abstract public function setEntityTypeId(int $entityTypeId): Category;

	abstract public function getName(): string;

	public function getSingleName(): ?string
	{
		return null;
	}

	public function getSingleNameIfPossible(): string
	{
		$result = $this->getSingleName();

		return is_null($result) ? $this->getName() : $result;
	}

	abstract public function setName(string $name): Category;

	abstract public function getSort(): int;

	abstract public function setSort(int $sort): Category;

	abstract public function setIsDefault(bool $isDefault): Category;

	abstract public function getIsDefault(): bool;

	abstract public function save(): Result;

	abstract public function delete(): Result;

	public function getIsSystem(): bool
	{
		return false;
	}

	public function getCode(): string
	{
		return '';
	}

	public function getDisabledFieldNames(): array
	{
		return [];
	}

	public function isTrackingEnabled(): bool
	{
		return true;
	}

	public function getUISettings(): array
	{
		return [];
	}

	public function getData(): array
	{
		return [
			'ID' => $this->getId(),
			'NAME' => $this->getName(),
			'SORT' => $this->getSort(),
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'IS_DEFAULT' => $this->getIsDefault(),
		];
	}

	public function jsonSerialize(): array
	{
		return Container::getInstance()->getCategoryConverter()->toJson($this);
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
