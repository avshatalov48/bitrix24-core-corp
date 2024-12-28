<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\RowAssembler;

use Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler\ShareRoleIsActiveFieldAssembler;
use Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler\ShareRoleIsDeletedFieldAssembler;
use Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler\ShareRoleNameFieldAssembler;
use Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler\ShareRoleShareFieldAssembler;
use Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler\ShareRoleUserFieldAssembler;
use Bitrix\Main\Grid\Row\RowAssembler;

class ShareRoleRowAssembler extends RowAssembler
{
	/**
	 * @inheritDoc
	 */
	protected function prepareFieldAssemblers(): array
	{
		return [
			new ShareRoleShareFieldAssembler(['SHARE']),
			new ShareRoleNameFieldAssembler(['NAME']),
			new ShareRoleUserFieldAssembler(['AUTHOR', 'EDITOR']),
			new ShareRoleIsDeletedFieldAssembler(['IS_DELETED']),
			new ShareRoleIsActiveFieldAssembler(['IS_ACTIVE']),
		];
	}
}