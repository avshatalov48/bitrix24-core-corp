<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler;

use Bitrix\Main;
use Bitrix\Main\Grid\Row\RowAssembler;

class UnusedElementsRowAssembler extends RowAssembler
{
	protected function prepareFieldAssemblers(): array
	{
		return [
			new Main\Grid\Row\Assembler\Field\StringFieldAssembler([
				'TITLE',
				'EXTERNAL_ID',
			]),
			new Field\UnusedElements\ElementTypeFleldAssembler([
				'TYPE',
			]),
			new Field\Base\TrimmedTextFieldAssembler(
				['DESCRIPTION'],
				maxLength: 300
			),
			new Field\UnusedElements\OwnersFieldAssembler([
				'OWNERS',
			]),
			new Field\Base\DateFieldAssembler([
				'DATE_UPDATE',
				'DATE_CREATE',
			]),
		];
	}
}
