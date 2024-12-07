<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Main\Localization\Loc;

class RestoreAction extends JsGridAction
{
	public static function getId(): ?string
	{
		return 'restore';
	}

	public function processRequest(\Bitrix\Main\HttpRequest $request): ?\Bitrix\Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_RESTORE') ?? '';
	}

	public function isAvailable(array $rawFields): bool
	{
		return $this->isCurrentUserAdmin()
			&& (int)$rawFields['ID'] !== $this->getSettings()->getCurrentUserId()
			&& $rawFields['ACTIVE'] === 'N'
			&& !$rawFields['CONFIRM_CODE'];
	}

	public function getExtensionMethod(): string
	{
		return 'activityAction';
	}

	protected function getActionParams(array $rawFields): array
	{
		return [
			'action' => 'restore',
			'userId' => $rawFields['ID'],
		];
	}
}