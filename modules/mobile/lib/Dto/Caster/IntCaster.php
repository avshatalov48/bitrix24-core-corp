<?php

namespace Bitrix\Mobile\Dto\Caster;

final class IntCaster extends Caster
{
	protected function castSingleValue($value)
	{
		return (int)$value;
	}
}
