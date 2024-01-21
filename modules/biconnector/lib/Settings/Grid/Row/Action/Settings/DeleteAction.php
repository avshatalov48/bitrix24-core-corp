<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Action\Settings;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DeleteAction extends BaseAction
{
	public static function getId(): ?string
	{
		return 'delete';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('SETTINGS_GRID_ROW_ACTION_DELETE_TITLE');
	}
}
