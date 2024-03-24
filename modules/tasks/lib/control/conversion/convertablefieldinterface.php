<?php

namespace Bitrix\Tasks\Control\Conversion;

interface ConvertableFieldInterface
{
	public function getConvertKey(): string;
}