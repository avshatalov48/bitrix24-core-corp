<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class EmailFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		if ($this->getSettings()->isExcelMode())
		{
			return $value;
		}

		return '<a href="mailto:'.htmlspecialcharsbx($value).'">'.htmlspecialcharsbx($value).'</a>';
	}
}