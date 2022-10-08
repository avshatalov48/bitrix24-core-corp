<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Crm\Model\EO_ItemCategory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CCrmOwnerType;

class ItemCategory extends Category
{
	protected $entityObject;

	public function __construct(EO_ItemCategory $entityObject)
	{
		$this->entityObject = $entityObject;

		Container::getInstance()->getLocalization()->loadMessages(); // TODO: separate file with system categories
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
		return $this->entityObject->getId();
	}

	public function getEntityTypeId(): int
	{
		return $this->entityObject->getEntityTypeId();
	}

	public function setEntityTypeId(int $entityTypeId): Category
	{
		$this->entityObject->setEntityTypeId($entityTypeId);

		return $this;
	}

	public function getName(): string
	{
		$code = $this->getCode();
		if (!empty($code))
		{
			$name = Loc::getMessage($code);
			if (!empty($name))
			{
				return $name;
			}
		}

		return $this->entityObject->getName();
	}

	public function setName(string $name): Category
	{
		$this->entityObject->setName($name);

		return $this;
	}

	public function getSort(): int
	{
		return $this->entityObject->getSort();
	}

	public function setSort(int $sort): Category
	{
		$this->entityObject->setSort($sort);

		return $this;
	}

	public function setIsDefault(bool $isDefault): Category
	{
		$this->entityObject->setIsDefault($isDefault);

		return $this;
	}

	public function getIsDefault(): bool
	{
		return $this->entityObject->getIsDefault();
	}

	public function getIsSystem(): bool
	{
		return $this->entityObject->getIsSystem();
	}

	public function getCode(): string
	{
		return $this->entityObject->getCode();
	}

	public function save(): Result
	{
		return $this->entityObject->save();
	}

	public function delete(): Result
	{
		return $this->entityObject->delete();
	}
}
