<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class NameFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		return htmlspecialcharsbx((string)$value);
	}
}
