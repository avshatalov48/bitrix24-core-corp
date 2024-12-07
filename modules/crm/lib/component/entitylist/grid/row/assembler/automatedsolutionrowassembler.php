<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Assembler;

use Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field\AutomatedSolution\LinkToTypeListFieldAssembler;
use Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field\AutomatedSolution\TypesFieldAssembler;
use Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field\FormattedDateTimeFieldAssembler;
use Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field\UserLinkFieldAssembler;
use Bitrix\Main\Grid\Row\RowAssembler;

final class AutomatedSolutionRowAssembler extends RowAssembler
{
	protected function prepareFieldAssemblers(): array
	{
		return [
			new LinkToTypeListFieldAssembler([
				'TITLE',
			]),
			new UserLinkFieldAssembler([
				'CREATED_BY',
				'UPDATED_BY',
			]),
			new FormattedDateTimeFieldAssembler([
				'CREATED_TIME',
				'UPDATED_TIME',
				'LAST_ACTIVITY_TIME',
			]),
			new TypesFieldAssembler([
				'TYPE_IDS',
			]),
			new FormattedDateTimeFieldAssembler([
				'LAST_ACTIVITY_TIME',
			]),
		];
	}
}
