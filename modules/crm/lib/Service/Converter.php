<?php

namespace Bitrix\Crm\Service;

abstract class Converter
{
	protected $converter;

	protected const UNDERSCORE_MARK = '~~~';

	abstract public function toJson($model): array;

	public function convertKeysToCamelCase(array $data): array
	{
		$data = $this->markUnderscoreSeparatedDigits($data);
		$data = $this->getConverter()->process($data);
		$data = $this->returnMarkedUnderscores($data);

		return $data;
	}

	protected function getConverter(): \Bitrix\Main\Engine\Response\Converter
	{
		if(!$this->converter)
		{
			$this->converter = \Bitrix\Main\Engine\Response\Converter::toJson();
		}

		return $this->converter;
	}

	protected function markUnderscoreSeparatedDigits(array $data): array
	{
		$newData = [];
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				$value = $this->markUnderscoreSeparatedDigits($value);
			}
			$newKey = preg_replace('/(\d)(_)(\d)/', '${1}' . static::UNDERSCORE_MARK . '${3}', $key);
			$newData[$newKey] = $value;
		}

		return $newData;
	}

	protected function returnMarkedUnderscores(array $data): array
	{
		$newData = [];
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				$value = $this->returnMarkedUnderscores($value);
			}
			$newKey = preg_replace('/(\d)(' . static::UNDERSCORE_MARK . ')(\d)/', '${1}_${3}', $key);
			$newData[$newKey] = $value;
		}

		return $newData;
	}
}