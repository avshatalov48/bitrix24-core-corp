<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class UrlFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		if ($this->getSettings()->isExcelMode())
		{
			return $value;
		}

		return '<a href="' . htmlspecialcharsbx($value) . '" target="_blank">' . htmlspecialcharsbx($value) . '</a>';
	}
}