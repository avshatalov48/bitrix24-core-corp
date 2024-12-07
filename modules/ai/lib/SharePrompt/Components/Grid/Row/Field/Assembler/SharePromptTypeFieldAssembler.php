<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class SharePromptTypeFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): ?string
	{
		if ($value === 'SIMPLE_TEMPLATE')
		{
			return Loc::getMessage('PROMPT_LIBRARY_GRID_PROMPT_TYPE_SIMPLE_TEMPLATE');
		}

		return Loc::getMessage('PROMPT_LIBRARY_GRID_PROMPT_TYPE_DEFAULT');
	}
}
