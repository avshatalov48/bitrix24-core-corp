<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class CountryFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		$country = \Bitrix\Main\UserUtils::getCountryValue([
			'VALUE' => $value
		]);

		return htmlspecialcharsbx($country);
	}
}