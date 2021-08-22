<?php

namespace Bitrix\Crm\Search;


class Result extends \Bitrix\Main\Result
{
	public function getIds(): array
	{
		if (empty($this->data))
		{
			return [];
		}
		return array_merge(...$this->data); // merge all subarrays of $this->data into single one
	}

	public function addId($id): void
	{
		$this->addIds([$id]);
	}

	public function addIds(array $ids): void
	{
		if (empty($this->data))
		{
			$priority = 0;
		}
		else
		{
			$priority = max(array_keys($this->data));
		}
		$this->addIdsByPriority($priority, $ids);
	}

	public function addIdsByPriority(int $priority, array $ids): void
	{
		$this->data[$priority] = $this->data[$priority] ?? [];
		$this->data[$priority] = array_unique(array_merge($this->data[$priority], $ids));
	}

	public function getPrioritizedIds(): array
	{
		ksort($this->data, SORT_NUMERIC);
		return $this->data;
	}

	public function setData(array $data)
	{
		throw new \Bitrix\Main\NotSupportedException();
	}

	public function getData()
	{
		return $this->getIds();
	}
}
