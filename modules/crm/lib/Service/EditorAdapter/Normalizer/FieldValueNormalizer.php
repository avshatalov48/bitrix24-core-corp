<?php

namespace Bitrix\Crm\Service\EditorAdapter\Normalizer;

use Bitrix\Crm\Field;

final class FieldValueNormalizer extends Base
{
	public function __construct(private readonly string $fieldType)
	{
	}

	public function normalize(mixed $value): mixed
	{
		if (is_array($value))
		{
			$result = [];
			foreach ($value as $singleValue)
			{
				$result[] = $this->prepareSingleValue($singleValue);
			}
		}
		else
		{
			$result = $this->prepareSingleValue($value);
		}

		return $result;
	}

	private function prepareSingleValue($value): mixed
	{
		if (is_float($value))
		{
			$value = sprintf('%f', $value);
			$value = rtrim($value, '0');
			$value = rtrim($value, '.');
		}
		elseif (is_numeric($value))
		{
			$value = (string)$value;
		}
		elseif (is_object($value) && method_exists($value, '__toString'))
		{
			$value = $value->__toString();
		}
		elseif ($this->fieldType === Field::TYPE_BOOLEAN)
		{
			if ($value !== 'Y' && $value !== 'N')
			{
				$value = $value ? 'Y' : 'N';
			}
		}
		elseif ($value === false)
		{
			$value = '';
		}

		return $value;
	}
}
