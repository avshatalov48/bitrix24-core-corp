<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Main\Localization\Loc;

class FireAction extends JsGridAction
{
	public static function getId(): ?string
	{
		return 'fire';
	}

	public function processRequest(\Bitrix\Main\HttpRequest $request): ?\Bitrix\Main\Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('INTRANET_USER_GRID_ROW_ACTIONS_FIRE') ?? '';
	}

	public function isAvailable(array $rawFields): bool
	{
		return $this->isCurrentUserAdmin()
			&& (int)$rawFields['ID'] !== $this->getSettings()->getCurrentUserId()
			&& $rawFields['ACTIVE'] === 'Y'
			&& empty($rawFields['CONFIRM_CODE'])
			&& !(
				$this->getSettings()->isUserIntegrator($this->getSettings()->getCurrentUserId())
				&& $this->getSettings()->isUserAdmin($rawFields['ID'])
				&& !$this->getSettings()->isUserIntegrator($rawFields['ID'])
			);
	}

	public function getExtensionMethod(): string
	{
		return 'activityAction';
	}

	protected function getActionParams(array $rawFields): array
	{
		return [
			'action' => 'deactivate',
			'userId' => $rawFields['ID'],
		];
	}
}