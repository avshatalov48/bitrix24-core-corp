<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class StringFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		return htmlspecialcharsbx($value);
	}
}