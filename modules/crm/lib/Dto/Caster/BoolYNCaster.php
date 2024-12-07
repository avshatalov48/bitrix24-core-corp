<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

final class BoolYNCaster extends Caster
{
	protected function castSingleValue($value)
	{
		if (is_string($value))
		{
			return mb_strtoupper($value) === 'Y' ? 'Y' : 'N';
		}

		return 'N';
	}
}
