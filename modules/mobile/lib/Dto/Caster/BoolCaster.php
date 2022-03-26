<?php

namespace Bitrix\Mobile\Dto\Caster;

final class BoolCaster extends Caster
{
	protected function castSingleValue($value)
	{
		return (bool)$value;
	}
}
