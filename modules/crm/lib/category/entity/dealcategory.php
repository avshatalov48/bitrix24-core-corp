<?php

namespace Bitrix\Crm\Category\Entity;

use Bitrix\Crm\Entry\EntryException;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;

class DealCategory extends Category
{
	protected $entityObject;

	public function __construct(EO_DealCategory $entityObject)
	{
		$this->entityObject = $entityObject;
	}

	public function getId(): ?int
	{
		return $this->entityObject->getId();
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
		throw new InvalidOperationException('Deal categories does not support updating default state');
	}

	public function getIsDefault(): bool
	{
		return false;
	}

	public function save(): Result
	{
		$result = new Result();

		$fields = $this->entityObject->collectValues();
		try
		{
			if ($this->getId() > 0)
			{
				\Bitrix\Crm\Category\DealCategory::update($this->getId(), $fields);
			}
			else
			{
				$id = \Bitrix\Crm\Category\DealCategory::add($fields);
				$this->entityObject = DealCategoryTable::getById($id)->fetchObject();
			}
		}
		catch (EntryException $exception)
		{
			$result->addError(new Error($exception->getLocalizedMessage()));
		}

		return $result;
	}

	public function delete(): Result
	{
		$result = new Result();
		try
		{
			\Bitrix\Crm\Category\DealCategory::delete($this->getId());
			Container::getInstance()->getFactory($this->getEntityTypeId())->clearCategoriesCache();
		}
		catch (EntryException $exception)
		{
			$result->addError(new Error($exception->getLocalizedMessage()));
		}

		return $result;
	}

	public function setOriginId(string $originId): self
	{
		$this->entityObject->setOriginId($originId);

		return $this;
	}

	public function getOriginId(): ?string
	{
		return $this->entityObject->getOriginId();
	}

	public function setOriginatorId(string $originatorId): self
	{
		$this->entityObject->setOriginatorId($originatorId);

		return $this;
	}

	public function getOriginatorId(): ?string
	{
		return $this->entityObject->getOriginatorId();
	}

	public function getData(): array
	{
		return array_merge(
			parent::getData(),
			[
				'ORIGIN_ID' => $this->getOriginId(),
				'ORIGINATOR_ID' => $this->getOriginatorId(),
			],
		);
	}
}
