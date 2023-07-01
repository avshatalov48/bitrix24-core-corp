<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

final class IntCaster extends Caster
{
	protected function castSingleValue($value): int
	{
		return (int)$value;
	}
}
