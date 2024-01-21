<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field;
use Bitrix\Main\Grid\Row\RowAssembler;

class DashboardRowAssembler extends RowAssembler
{
	protected function prepareFieldAssemblers(): array
	{
		return [
			new Field\NameFieldAssembler([
				'TITLE',
			]),
			new Field\StatusFieldAssembler([
				'STATUS',
			]),
			new Field\CreatedByFieldAssembler([
				'CREATED_BY_ID',
			]),
			new Field\ActionFieldAssembler([
				'EDIT_URL',
			]),
			new Field\BasedOnFieldAssembler([
				'SOURCE_ID',
			]),
			new Field\FilterPeriodFieldAssembler([
				'FILTER_PERIOD',
			]),
			new Field\ExternalIdFieldAssembler([
				'ID',
			]),
			new Field\DateCreateFieldAssembler([
				'DATE_CREATE',
			]),
		];
	}
}
