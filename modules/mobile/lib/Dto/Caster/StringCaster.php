<?php

namespace Bitrix\Mobile\Dto\Caster;

final class StringCaster extends Caster
{
	protected function castSingleValue($value)
	{
		return (string)$value;
	}
}
