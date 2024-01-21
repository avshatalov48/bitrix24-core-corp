<?php

namespace Bitrix\TasksMobile\Controller;

class Settings extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'isBetaAvailable',
			'isBetaActive',
			'isNewChecklistActive',
		];
	}

	public function isNewDashboardActiveAction(): bool
	{
		return \Bitrix\TasksMobile\Settings::getInstance()->isNewDashboardActive();
	}

	public function isBetaAvailableAction(): bool
	{
		return \Bitrix\TasksMobile\Settings::getInstance()->isBetaAvailable();
	}

	public function isBetaActiveAction(): bool
	{
		return \Bitrix\TasksMobile\Settings::getInstance()->isBetaActive();
	}

	public function activateBetaAction(): void
	{
		\Bitrix\TasksMobile\Settings::getInstance()->activateBeta();
	}

	public function deactivateBetaAction(): void
	{
		\Bitrix\TasksMobile\Settings::getInstance()->deactivateBeta();
	}

	public function isNewChecklistActiveAction(): bool
	{
		return \Bitrix\TasksMobile\Settings::getInstance()->isNewChecklistActive();
	}

	public function activateNewChecklistAction(): void
	{
		\Bitrix\TasksMobile\Settings::getInstance()->activateNewChecklist();
	}

	public function deactivateNewChecklistAction(): void
	{
		\Bitrix\TasksMobile\Settings::getInstance()->deactivateNewChecklist();
	}
}
