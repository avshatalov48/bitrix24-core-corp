<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

final class StringCaster extends Caster
{
	protected function castSingleValue($value): string
	{
		return (string)$value;
	}
}
