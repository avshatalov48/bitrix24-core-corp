<?php

namespace Bitrix\BIConnector\Aggregate;

use Bitrix\Main\Web\Json;

class ArrayStringState extends BaseState
{
	protected $state = [];
	public function __construct(private readonly string $delimiter = '')
	{
	}

	public function updateState($id, $value)
	{
		if ($value !== null)
		{
			$this->state[$id] = $value;
		}
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function output()
	{
		$values = $this->state ? array_values($this->state) : [];

		if (!empty($this->delimiter))
		{
			$exploded = [];
			foreach ($values as $value)
			{
				$exploded = [...$exploded, ...explode($this->delimiter, $value)];
			}

			$values = $exploded;
		}

		return Json::encode($values);
	}
}
