<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\PhoneNumber\Format;

class PhoneNumberField extends FieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		if (empty($value))
		{
			return '';
		}

		$phoneNumber = Parser::getInstance()->parse($value);

		return $phoneNumber->format(Format::INTERNATIONAL);
	}
}