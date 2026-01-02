<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\UnusedElements;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class ElementTypeFleldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): ?string
	{
		return match ($value)
		{
			'dataset' => Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_TYPE_DATASET'),
			'chart' => Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_TYPE_CHART'),
			default => null,
		};
	}
}
