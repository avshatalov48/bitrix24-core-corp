<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Main\Localization\Loc;

class ConfirmAction extends JsGridAction
{
	public static function getId(): ?string
	{
		return 'confirm';
	}

	public function processRequest(\Bitrix\Main\HttpRequest $request): ?\Bitrix\Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_CONFIRM') ?? '';
	}

	public function isAvailable(array $rawFields): bool
	{
		return $this->isCurrentUserAdmin()
			&& (int)$rawFields['ID'] !== $this->getSettings()->getCurrentUserId()
			&& $rawFields['ACTIVE'] === 'N'
			&& !empty($rawFields['CONFIRM_CODE']);
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