<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\Service\Converter;
use Bitrix\Main\ArgumentException;

final class AutomatedSolution extends Converter
{
	private const REST_KEYS = [
		'ID',
		'TITLE',
		'TYPE_IDS',
	];
	private const NOT_REST_KEYS = [
		'ID',
		'TITLE',
		'TYPE_IDS',
		'INTRANET_CUSTOM_SECTION_ID',
	];

	public function toJson($model): array
	{
		if (!is_array($model))
		{
			throw new ArgumentException('Model should be an array', 'model');
		}

		if ($this->isRest())
		{
			$data = $this->filterArrayByKeysWhitelist($model, self::REST_KEYS);
		}
		else
		{
			$data = $this->filterArrayByKeysWhitelist($model, self::NOT_REST_KEYS);
		}

		return $this->convertKeysToCamelCase($this->prepareData($data));
	}

	private function filterArrayByKeysWhitelist(array $array, array $keysWhitelist): array
	{
		return array_filter(
			$array,
			fn(string $key) => in_array($key, $keysWhitelist, true),
			ARRAY_FILTER_USE_KEY,
		);
	}
}
