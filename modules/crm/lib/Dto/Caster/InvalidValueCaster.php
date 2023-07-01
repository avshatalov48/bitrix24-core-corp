<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Caster;

final class InvalidValueCaster extends Caster
{
	protected function castSingleValue($value)
	{
		return null;
	}
}
