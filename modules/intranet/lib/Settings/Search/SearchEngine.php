<?php

namespace Bitrix\Intranet\Settings\Search;

class SearchEngine
{
	//PHP8.0 does not support the callable type for properties
	/**
	 * @var callable
	 */
	private $format;

	public function __construct(
		private array $dataSet,
		callable $format
	)
	{
		$this->format = $format;
	}

	public function find(string $query): array
	{
		$result = [];
		if (empty($query))
		{
			return $result;
		}
		$query = htmlspecialcharsbx($query);
		foreach ($this->dataSet as $code => $label)
		{
			if (str_contains(mb_strtolower($label), mb_strtolower($query)))
			{
				$result[] = ($this->format)($code, $label);
			}
		}

		return $result;
	}

	public static function initWithDefaultFormatter(array $dataSet): self
	{
		$formatter = function ($code, $label) {
			return [
				'code' => $code,
				'title' => $label
			];
		};

		return new static($dataSet, $formatter);
	}
}