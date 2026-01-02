<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

final class RawArrayCaster extends Caster
{
	protected function castSingleValue($value): array
	{
		return is_array($value) ? $value : [$value];
	}
}
