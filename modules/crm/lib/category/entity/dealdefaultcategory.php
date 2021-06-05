<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;

class DealDefaultCategory extends Category
{
	protected $name;
	protected $sort;

	public function __construct(string $name, int $sort)
	{
		$this->name = $name;
		$this->sort = $sort;
	}

	public function getId(): ?int
	{
		return 0;
	}

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	public function setEntityTypeId(int $entityTypeId): Category
	{
		throw new InvalidOperationException('Deal categories does not support changing entityTypeId');
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): Category
	{
		$this->name = $name;

		return $this;
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function setSort(int $sort): Category
	{
		$this->sort = $sort;

		return $this;
	}

	public function setIsDefault(bool $isDefault): Category
	{
		throw new InvalidOperationException('Deal categories does not support updating default state');
	}

	public function getIsDefault(): bool
	{
		return true;
	}

	public function save(): Result
	{
		DealCategory::setDefaultCategoryName($this->name);
		DealCategory::setDefaultCategorySort($this->sort);

		return new Result();
	}

	public function delete(): Result
	{
		throw new InvalidOperationException('Default deal category can not be deleted');
	}
}
