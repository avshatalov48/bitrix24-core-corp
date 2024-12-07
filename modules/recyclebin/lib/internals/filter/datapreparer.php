<?php

namespace Bitrix\Recyclebin\Internals\Filter;

class DataPreparer
{
	public function __construct(private array $data)
	{

	}

	public function prepareFilterFields(array &$filter, array $fields): void
	{
		foreach ($fields as $item)
		{
			$type = $item['type'];
			$id = $item['id'];

			if ($type === 'date')
			{
				$this->prepareDateTypeField($filter, $id);
			}
			elseif ($type === 'number')
			{
				$this->prepareNumberTypeField($filter, $id);
			}
			elseif ($type === 'list')
			{
				$this->prepareListTypeField($filter, $id);
			}
			elseif ($type === 'custom_entity')
			{
				$this->prepareCustomEntityTypeField($filter, $id);
			}
			else
			{
				$this->prepareDefaultTypeField($filter, $id);
			}
		}
	}

	private function prepareDefaultTypeField(array &$filter, string $id): void
	{
		$field = $this->getFieldData($id);
		if ($field)
		{
			$filter['%' . $id] = $field;
		}
	}

	private function prepareDateTypeField(array &$filter, string $id): void
	{
		if ($this->getFieldData($id . '_from'))
		{
			$filter['>=' . $id] = $this->getFieldData($id . '_from');
		}

		if ($this->getFieldData($id . '_to'))
		{
			$filter['<=' . $id] = $this->getFieldData($id . '_to');
		}
	}

	private function prepareNumberTypeField(array &$filter, string $id): void
	{
		if ($this->getFieldData($id . '_from'))
		{
			$filter['>=' . $id] = $this->getFieldData($id . '_from');
		}

		if ($this->getFieldData($id . '_to'))
		{
			$filter['<=' . $id] = $this->getFieldData($id . '_to');
		}

		if (
			array_key_exists('>=' . $id, $filter)
			&& array_key_exists('<=' . $id, $filter)
			&& $filter['>=' . $id] === $filter['<=' . $id])
		{
			$filter[$id] = $filter['>=' . $id];
			unset($filter['>=' . $id], $filter['<=' . $id]);
		}
	}

	private function prepareListTypeField(array &$filter, string $id): void
	{
		if ($this->getFieldData($id))
		{
			$filter['@' . $id] = $this->getFieldData($id);
		}
	}

	private function prepareCustomEntityTypeField(array &$filter, string $id): void
	{
		if ($this->getFieldData($id))
		{
			$filter[$id] = $this->getFieldData($id);
		}
	}

	public function getFieldData($field, $default = null): mixed
	{
		return $this->data[$field] ?? $default;
	}
}
