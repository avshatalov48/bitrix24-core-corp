<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;

class ExternalIdFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$value = (int)$value;

		return "<a href='/bi/dashboard/detail/{$value}/'>{$value}</a><br>";
	}
}
