<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;
use CCrmOwnerType;

class ClientDefaultCategory extends Category
{
	protected $entityTypeId;
	protected $name;
	protected $sort;

	public function __construct(int $entityTypeId, string $name, int $sort)
	{
		$this->entityTypeId = $entityTypeId;
		$this->name = $name;
		$this->sort = $sort;
	}

	public function getData(): array
	{
		if (in_array($this->getEntityTypeId(), [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
		{
			return array_merge(parent::getData(), [
				'IS_SYSTEM' => $this->getIsSystem(),
				'CODE' => $this->getCode(),
			]);
		}

		return parent::getData();
	}

	public function getId(): ?int
	{
		return 0;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function setEntityTypeId(int $entityTypeId): Category
	{
		throw new InvalidOperationException('Default client category does not support changing entityTypeId');
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): Category
	{
		throw new InvalidOperationException('Default client category does not support changing name');
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function setSort(int $sort): Category
	{
		throw new InvalidOperationException('Default client category does not support changing sort');
	}

	public function setIsDefault(bool $isDefault): Category
	{
		throw new InvalidOperationException('Default client category does not support updating default state');
	}

	public function getIsDefault(): bool
	{
		return true;
	}

	public function getIsSystem(): bool
	{
		return true;
	}

	public function save(): Result
	{
		throw new InvalidOperationException('Default client category can not be changed');
	}

	public function delete(): Result
	{
		throw new InvalidOperationException('Default client category can not be deleted');
	}
}
