<?php

namespace Bitrix\Crm\Cache;

class RatedCache
{
	protected const KEY = 0;
	protected const VALUE = 1;
	protected const RATE = 2;

	protected int $limit;
	protected array $data = [];
	protected array $keyIndex = [];
	protected array $rateMap = [];

	public function __construct(int $limit = 500)
	{
		if ($limit < 1)
		{
			$limit = 1;
		}

		$this->limit = $limit;
	}

	public function set(string $key, array $value): void
	{
		$index = count($this->data);
		if ($index >= $this->limit)
		{
			$i = $index;
			while ($i-- >= $this->limit)
			{
				$minRate = min(array_keys($this->rateMap));
				$index = $this->rateMap[$minRate][0];
				unset($this->keyIndex[$this->data[$index][static::KEY]]);
				unset($this->data[$index]);
				array_splice($this->rateMap[$minRate], 0, 1);
				if (empty($this->rateMap[$minRate]))
				{
					unset($this->rateMap[$minRate]);
				}
			}
		}
		$rate = 0;
		$this->data[$index] = [static::KEY => $key, static::VALUE => $value, static::RATE => $rate];
		$this->keyIndex[$key] = $index;
		if (!isset($this->rateMap[$rate]))
		{
			$this->rateMap[$rate] = [];
		}
		$this->rateMap[$rate][] = $index;
	}

	public function get(string $key): ?array
	{
		if (isset($this->keyIndex[$key]))
		{
			$index = $this->keyIndex[$key];
			$dataItem = &$this->data[$index];
			$rate = $dataItem[static::RATE];
			array_splice($this->rateMap[$rate], array_search($index, $this->rateMap[$rate], true), 1);
			if (empty($this->rateMap[$rate]))
			{
				unset($this->rateMap[$rate]);
			}
			$dataItem[static::RATE] = ++$rate;
			$this->rateMap[$rate][] = $index;

			return $dataItem[static::VALUE];
		}

		return null;
	}

	public function clear(): void
	{
		$this->data = [];
		$this->keyIndex = [];
		$this->rateMap = [];
	}
}
