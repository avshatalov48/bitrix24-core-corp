<?php

namespace Bitrix\Crm\Entity\Compatibility;

use Bitrix\Main\Result;

abstract class Adapter
{
	/** @var self[] */
	private array $children = [];
	private ?string $tableAlias = null;

	final public function addChild(self $child): self
	{
		$this->children[] = $child;

		return $this;
	}

	/**
	 * @return Array<string, Array<string, array>>
	 */
	final public function getFieldsInfo(): array
	{
		$fieldsInfo = $this->doGetFieldsInfo();

		foreach ($this->children as $child)
		{
			//child can't override info for fields that were already described by parent, only add new fields
			$fieldsInfo += $child->getFieldsInfo();
		}

		return $fieldsInfo;
	}

	/**
	 * @return Array<string, Array<string, array>>
	 */
	protected function doGetFieldsInfo(): array
	{
		return [];
	}

	final public function getFields(): array
	{
		$fields = $this->doGetFields();

		foreach ($this->children as $child)
		{
			//child can't override parent fields, only add new ones
			$fields += $child->getFields();
		}

		return $fields;
	}

	protected function doGetFields(): array
	{
		return [];
	}

	final public function setTableAlias(string $alias): self
	{
		$this->tableAlias = $alias;

		return $this;
	}

	final protected function getTableAlias(): ?string
	{
		return $this->tableAlias;
	}

	/**
	 * @param Array<string, mixed> $fields
	 * @param Array<string, mixed> $compatibleOptions
	 *
	 * @return mixed
	 *
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
	final public function performAdd(array &$fields, array $compatibleOptions)
	{
		$mainResult = $this->doPerformAdd($fields, $compatibleOptions);
		if ($mainResult->isSuccess())
		{
			foreach ($this->children as $child)
			{
				$child->performAdd($fields, $compatibleOptions);
			}
		}

		$data = $mainResult->getData();
		if (array_key_exists('return', $data))
		{
			return $data['return'];
		}
	}

	/**
	 * @param int $id
	 * @param Array<string, mixed> $fields
	 * @param Array<string, mixed> $compatibleOptions
	 *
	 * @return mixed
	 *
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
	final public function performUpdate(int $id, array &$fields, array $compatibleOptions)
	{
		$mainResult = $this->doPerformUpdate($id, $fields, $compatibleOptions);
		if ($mainResult->isSuccess())
		{
			foreach ($this->children as $child)
			{
				$child->performUpdate($id, $fields, $compatibleOptions);
			}
		}

		$data = $mainResult->getData();
		if (array_key_exists('return', $data))
		{
			return $data['return'];
		}
	}

	/**
	 * @param int $id
	 * @param Array<string, mixed> $compatibleOptions
	 *
	 * @return mixed
	 *
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
	final public function performDelete(int $id, array $compatibleOptions)
	{
		$mainResult = $this->doPerformDelete($id, $compatibleOptions);
		if ($mainResult->isSuccess())
		{
			foreach ($this->children as $child)
			{
				$child->performDelete($id, $compatibleOptions);
			}
		}

		$data = $mainResult->getData();
		if (array_key_exists('return', $data))
		{
			return $data['return'];
		}
	}

	/**
	 * @param array $fields
	 * @param array $compatibleOptions
	 * @return Result - if you want to return something from the main method, place it in data in key 'return'
	 */
	abstract protected function doPerformAdd(array &$fields, array $compatibleOptions): Result;

	/**
	 * @param int $id
	 * @param array $fields
	 * @param array $compatibleOptions
	 * @return Result - if you want to return something from the main method, place it in data in key 'return'
	 */
	abstract protected function doPerformUpdate(int $id, array &$fields, array $compatibleOptions): Result;

	/**
	 * @param int $id
	 * @param array $compatibleOptions
	 * @return Result - if you want to return something from the main method, place it in data in key 'return'
	 */
	abstract protected function doPerformDelete(int $id, array $compatibleOptions): Result;
}
