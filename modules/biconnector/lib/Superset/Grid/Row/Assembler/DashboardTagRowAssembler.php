<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler;

use Bitrix\Main\Grid\Row\RowAssembler;

class DashboardTagRowAssembler extends RowAssembler
{
	protected function prepareFieldAssemblers(): array
	{
		return [
			new Field\Dashboard\TagTitleFieldAssembler([
				'TITLE',
			]),
		];
	}
}