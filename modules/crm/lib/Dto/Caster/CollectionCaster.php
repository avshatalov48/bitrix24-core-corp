<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

class CollectionCaster extends Caster
{
	private Caster $singleValueCaster;

	public function __construct(Caster $singleValueCaster)
	{
		$this->singleValueCaster = $singleValueCaster;
	}

	protected function castSingleValue($value)
	{
		$result = [];

		if (is_array($value))
		{
			foreach ($value as $key => $singleVal)
			{
				if ($this->isNullable && $singleVal === null)
				{
					$result[$key] = $singleVal;
				}
				else
				{
					$result[$key] = $this->singleValueCaster->castSingleValue($singleVal);
				}
			}
		}
		return $result;
	}
}
