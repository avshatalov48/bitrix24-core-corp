<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Action\Settings;

use Bitrix\Main\Localization\Loc;

class EditAction extends OpenAction
{
	public static function getId(): ?string
	{
		return 'edit';
	}

	protected function getText(): string
	{
		return Loc::getMessage('SETTINGS_GRID_ROW_ACTION_EDIT_TITLE');
	}
}
