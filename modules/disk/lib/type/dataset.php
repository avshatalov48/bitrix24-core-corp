<?php

namespace Bitrix\Disk\Type;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\Dictionary;

class DataSet extends Dictionary
{
	/**
	 * DataSet constructor.
	 *
	 * @param array|\Traversable $items
	 */
	public function __construct($items)
	{
		$data = array();
		if (!is_array($items) && $items instanceof \Traversable)
		{
			foreach ($items as $item)
			{
				$data[] = $item;
			}
		}
		else
		{
			$data = $items;
		}

		parent::__construct($data);
	}

	public static function createByIterator(\Traversable $iterator)
	{
		return new static($iterator);
	}

	public static function createByArray(array $items)
	{
		return new static($items);
	}

	public function filterByCallback($callback)
	{
		if (!is_callable($callback))
		{
			throw new ArgumentException('Callback has to be callable');
		}

		return new static(array_filter($this->values, $callback));
	}

	protected function filterByField($field, $value)
	{
		$result = array();
		foreach ($this->values as $item)
		{
			if ($value === null && !array_key_exists($field, $item))
			{
				continue;
			}
			elseif (!isset($item[$field]) && $value !== null)
			{
				continue;
			}

			if ((string)$item[$field] === (string)$value)
			{
				$result[] = $item;
			}
		}

		return new static($result);
	}

	public function filterByFields(array $necessaryFields)
	{
		$selection = $this;
		foreach ($necessaryFields as $field => $value)
		{
			$selection = new static($selection->filterByField($field, $value));
		}

		return $selection;
	}

	public function isExists(array $necessaryFields)
	{
		return !$this->filterByFields($necessaryFields)->isEmpty();
	}

	public function getById($id)
	{
		$values = $this->filterByField('ID', $id);

		return $values->getFirst();
	}

	public function getFirst()
	{
		if ($this->isEmpty())
		{
			return null;
		}

		$first = reset($this->values);

		return $first;
	}

	public function sortByColumn($columns, $callbacks = '', $defaultValueIfNotSetValue = null, $preserveKeys = false)
	{
		Collection::sortByColumn($this->values, $columns, $callbacks, $defaultValueIfNotSetValue, $preserveKeys);

		return $this;
	}
}