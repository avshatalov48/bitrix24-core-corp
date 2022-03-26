<?php

namespace Bitrix\Mobile\Dto\Caster;

final class FloatCaster extends Caster
{
	protected function castSingleValue($value)
	{
		return (float)$value;
	}
}
