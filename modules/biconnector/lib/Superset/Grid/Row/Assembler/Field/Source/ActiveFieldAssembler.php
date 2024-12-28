<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Source;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class ActiveFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		return $value === 'Y'
			? Loc::getMessage('BICONNECTOR_SUPERSET_SOURCE_GRID_ACTIVE_YES') ?? ''
			: Loc::getMessage('BICONNECTOR_SUPERSET_SOURCE_GRID_ACTIVE_NO') ?? '';
	}
}
