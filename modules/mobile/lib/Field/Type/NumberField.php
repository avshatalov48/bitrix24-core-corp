<?php

namespace Bitrix\Mobile\Field\Type;

class NumberField extends BaseField
{
	public const TYPE = 'number';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		return $this->value;
	}

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		$data = parent::getData();

		$onlyInteger = true;
		if (is_array($this->value))
		{
			foreach ($this->value as $value)
			{
				if ($this->isFloat((string)$value))
				{
					$onlyInteger = false;
					break;
				}
			}
		}
		elseif ($this->isFloat((string)$this->value))
		{
			$onlyInteger = false;
		}

		if (!$onlyInteger)
		{
			$data['precision'] = 2;
		}

		return $data;
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	private function isFloat(string $value): bool
	{
		if ($this->getUserFieldInfo()['USER_TYPE_ID'] === 'double')
		{
			return true;
		}

		return (is_numeric($value) && strpos($value, '.') !== false);
	}
}
