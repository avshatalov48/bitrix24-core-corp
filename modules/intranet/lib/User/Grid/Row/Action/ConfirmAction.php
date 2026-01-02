<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Localization\Loc;

class ConfirmAction extends JsGridAction
{
	protected static function getActionType(): UserActionDictionary
	{
		return UserActionDictionary::CONFIRM;
	}

	public function processRequest(\Bitrix\Main\HttpRequest $request): ?\Bitrix\Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_CONFIRM') ?? '';
	}

	public function getExtensionMethod(): string
	{
		return 'confirmAction';
	}

	protected function getActionParams(array $rawFields): array
	{
		return [
			'isAccept' => true,
			'userId' => $rawFields['ID'],
		];
	}
}