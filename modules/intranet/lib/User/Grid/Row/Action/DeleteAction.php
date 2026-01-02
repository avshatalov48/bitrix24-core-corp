<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Localization\Loc;

class DeleteAction extends JsGridAction
{
	protected static function getActionType(): UserActionDictionary
	{
		return UserActionDictionary::DELETE;
	}

	public function processRequest(\Bitrix\Main\HttpRequest $request): ?\Bitrix\Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_DELETE') ?? '';
	}

	public function getExtensionMethod(): string
	{
		return 'activityAction';
	}

	protected function getActionParams(array $rawFields): array
	{
		return [
			'action' => 'deleteOrFire',
			'userId' => $rawFields['ID'],
		];
	}
}