<?php

namespace Bitrix\Crm\Service\Timeline\Item\Compatible;

use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Model;

abstract class Compatible extends Item
{
	protected array $data = [];

	public function __construct(Context $context, \Bitrix\Crm\Service\Timeline\Item\Compatible\Model $model)
	{
		parent::__construct($context, $model);
		$this->data = $this->initializeData($model->getData());
	}

	public function jsonSerialize(): array
	{
		return $this->applyTypeCompatibility($this->data); // to be compatible with CUtil::PhpToJSObject() format
	}

	protected function initializeData(array $data): array
	{
		return $data;
	}

	protected function applyTypeCompatibility(array $data): array
	{
		return $this->convertType($data);
	}

	private function convertType($value)
	{
		if (is_array($value))
		{
			foreach ($value as $key => $subValue)
			{
				$value[$key] = $this->convertType($subValue);
			}
		}
		elseif (!is_bool($value))
		{
			$value = (string)$value;
		}

		return $value;
	}
}
