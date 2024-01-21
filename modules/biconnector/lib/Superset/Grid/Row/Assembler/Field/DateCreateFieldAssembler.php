<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\Main\Context;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Type;

class DateCreateFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		if ($value instanceof Type\Date)
		{
			$userCulture = Context::getCurrent()?->getCulture();
			$value = $value->toString($userCulture);
		}

		return $value;
	}
}
