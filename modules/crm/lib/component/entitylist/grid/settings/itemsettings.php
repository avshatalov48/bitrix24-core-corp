<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Settings;

use Bitrix\Main\Grid\Settings;

final class ItemSettings extends Settings
{
	private ?int $categoryId = null;
	private ?bool $isAllItemsCategory = null;
	private ?bool $isRecurring = null;
	private ?bool $isMyCompany = null;
	private ?array $editableFieldsWhitelist = null;
	private array $columnNameToEditableFieldNameMap = [];

	public function __construct(array $params)
	{
		parent::__construct($params);

		if (isset($params['CATEGORY_ID']) && is_int($params['CATEGORY_ID']))
		{
			$this->setCategoryId($params['CATEGORY_ID']);
		}

		if (isset($params['IS_ALL_ITEMS_CATEGORY']) && is_bool($params['IS_ALL_ITEMS_CATEGORY']))
		{
			$this->setIsAllItemsCategory($params['IS_ALL_ITEMS_CATEGORY']);
		}

		if (isset($params['IS_RECURRING']) && is_bool($params['IS_RECURRING']))
		{
			$this->setIsRecurring($params['IS_RECURRING']);
		}

		if (isset($params['IS_MY_COMPANY']) && is_bool($params['IS_MY_COMPANY']))
		{
			$this->setIsMyCompany($params['IS_MY_COMPANY']);
		}

		if (isset($params['EDITABLE_FIELDS_WHITELIST']) && is_array($params['EDITABLE_FIELDS_WHITELIST']))
		{
			$this->setEditableFieldsWhitelist($params['EDITABLE_FIELDS_WHITELIST']);
		}

		if (
			isset($params['COLUMN_NAME_TO_EDITABLE_FIELD_NAME_MAP'])
			&& is_array($params['COLUMN_NAME_TO_EDITABLE_FIELD_NAME_MAP'])
		)
		{
			$this->setColumnNameToEditableFieldNameMap($params['COLUMN_NAME_TO_EDITABLE_FIELD_NAME_MAP']);
		}
	}

	public function setCategoryId(?int $categoryId): self
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	public function getCategoryId(): ?int
	{
		return $this->categoryId;
	}

	public function setIsAllItemsCategory(?bool $isAllItemsCategory): self
	{
		$this->isAllItemsCategory = $isAllItemsCategory;

		return $this;
	}

	public function getIsAllItemsCategory(): ?bool
	{
		return $this->isAllItemsCategory;
	}

	public function isAllItemsCategory(): bool
	{
		return is_bool($this->isAllItemsCategory) && $this->isAllItemsCategory;
	}

	public function setIsRecurring(?bool $isRecurring): self
	{
		$this->isRecurring = $isRecurring;

		return $this;
	}

	public function getIsRecurring(): ?bool
	{
		return $this->isRecurring;
	}

	public function isRecurring(): bool
	{
		return is_bool($this->isRecurring) && $this->isRecurring;
	}

	public function setIsMyCompany(?bool $isMyCompany): self
	{
		$this->isMyCompany = $isMyCompany;

		return $this;
	}

	public function getIsMyCompany(): ?bool
	{
		return $this->isMyCompany;
	}

	public function isMyCompany(): bool
	{
		return is_bool($this->isMyCompany) && $this->isMyCompany;
	}

	public function setEditableFieldsWhitelist(?array $fieldNames): self
	{
		if ($fieldNames === null)
		{
			$this->editableFieldsWhitelist = null;
		}
		else
		{
			$this->editableFieldsWhitelist = array_filter($fieldNames, 'is_string');
		}

		return $this;
	}

	public function getEditableFieldsWhitelist(): ?array
	{
		return $this->editableFieldsWhitelist;
	}

	public function setColumnNameToEditableFieldNameMap(array $map): self
	{
		$this->columnNameToEditableFieldNameMap = array_filter(
			$map,
			fn(mixed $value, mixed $key) => is_string($value) && is_string($key),
			ARRAY_FILTER_USE_BOTH,
		);

		return $this;
	}

	public function getColumnNameToEditableFieldNameMap(): array
	{
		return $this->columnNameToEditableFieldNameMap;
	}
}
