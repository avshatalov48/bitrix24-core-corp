<?php

namespace Bitrix\Crm\Entity;

use Bitrix\Crm\Category\EditorHelper;
use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\CustomerType;
use Bitrix\Crm\Service\Container;
use CBitrixComponent;
use CCrmOwnerType;

class EntityEditorOptionBuilder
{
	private ?int $categoryId = null;
	private int $customerType = CustomerType::UNDEFINED;

	private readonly EntityEditorOptionMap $map;

	public function __construct(
		private readonly int $entityTypeId,
	)
	{
		$this->map = new EntityEditorOptionMap();
	}

	public function setCategoryId(?int $categoryId = null): self
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	public function setCustomerType(int $customerType): self
	{
		$this->customerType = $customerType;

		return $this;
	}

	public function build(): string
	{
		$option = $this->map->option($this->entityTypeId);

		if ($this->isLead())
		{
			$prefix = $this->getLeadOptionPrefix();
			$option = $prefix === null ? $option : "{$prefix}_{$option}";
		}

		if ($option === null && $this->isDynamicTypeBasedApproach())
		{
			$option = $this->buildDynamicOption();
			if ($option !== null)
			{
				return $option;
			}
		}

		if ($option === null && $this->isEntityDefined())
		{
			$entityName = mb_strtolower(CCrmOwnerType::ResolveName($this->entityTypeId));
			$option = "{$entityName}_details";
		}

		return (new EditorHelper($this->entityTypeId))
			->getEditorConfigId($this->categoryId, $option ?? '', $this->useUpperCase());
	}

	public function buildDynamicOption(): ?string
	{
		$entityName = CCrmOwnerType::ResolveName($this->entityTypeId);
		$guid = "{$entityName}_details";

		if ($this->categoryId > 0)
		{
			$guid .= "_C{$this->categoryId}";
		}

		return $guid;
	}

	private function getLeadOptionPrefix(): ?string
	{
		$excludedTypes = [
			CustomerType::UNDEFINED,
			CustomerType::GENERAL,
		];

		if (in_array($this->customerType, $excludedTypes, true))
		{
			return null;
		}

		return mb_strtolower(CustomerType::resolveName($this->customerType));
	}

	private function useUpperCase(): bool
	{
		$includedTypes = [
			CCrmOwnerType::Contact,
			CCrmOwnerType::Company,
		];

		return in_array($this->entityTypeId, $includedTypes, true);
	}

	private function isLead(): bool
	{
		return $this->entityTypeId === CCrmOwnerType::Lead;
	}

	private function isDynamicTypeBasedApproach(): bool
	{
		return CCrmOwnerType::isUseDynamicTypeBasedApproach($this->entityTypeId);
	}

	private function isEntityDefined(): bool
	{
		return CCrmOwnerType::IsDefined($this->entityTypeId);
	}
}
