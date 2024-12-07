<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\RowAssembler;

use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptCategoriesFieldAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptIsActiveFieldAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptIsDeletedFieldAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptNameFieldAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptTypeFieldAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptUserFieldAssembler;
use Bitrix\AI\SharePrompt\Components\Grid\Row\Field\Assembler\SharePromptShareFieldAssembler;
use Bitrix\Main\Grid\Row\RowAssembler;

class SharePromptRowAssembler extends RowAssembler
{
	/**
	 * @inheritDoc
	 */
	protected function prepareFieldAssemblers(): array
	{
		return [
			new SharePromptShareFieldAssembler(['SHARE']),
			new SharePromptNameFieldAssembler(['NAME']),
			new SharePromptUserFieldAssembler(['AUTHOR', 'EDITOR']),
			new SharePromptIsDeletedFieldAssembler(['IS_DELETED']),
			new SharePromptIsActiveFieldAssembler(['IS_ACTIVE']),
			new SharePromptTypeFieldAssembler(['TYPE']),
			new SharePromptCategoriesFieldAssembler(['ACCESS']),
		];
	}
}
