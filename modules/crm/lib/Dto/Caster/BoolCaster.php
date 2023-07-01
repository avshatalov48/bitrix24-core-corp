<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

final class BoolCaster extends Caster
{
	protected function castSingleValue($value)
	{
		if (is_string($value))
		{
			return in_array(mb_strtoupper($value), ['TRUE', 'Y'], true);
		}
		return (bool)$value;
	}
}
