<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action;

use Bitrix\Intranet\User\Grid\Panel\Action\Group\ConfirmChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\DeclineChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\DeleteChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\FireChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\ReInviteChildAction;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Panel\Action\GroupAction;
use Bitrix\Main\ModuleManager;

class UserGroupAction extends GroupAction
{
	public function __construct(
		private readonly UserSettings $settings
	)
	{
	}

	protected function getSettings(): UserSettings
	{
		return $this->settings;
	}

	protected function prepareChildItems(): array
	{
		$actions = [];

		if ($this->getSettings()->isInvitationAvailable())
		{
			$actions[] = new ReInviteChildAction($this->getSettings());
		}

		if ($this->getSettings()->isCurrentUserAdmin())
		{
			if (ModuleManager::isModuleInstalled('bitrix24'))
			{
				$actions = array_merge($actions, [
					new ConfirmChildAction($this->getSettings()),
					new DeclineChildAction($this->getSettings()),
				]);
			}

			$actions = array_merge($actions, [
				new FireChildAction($this->getSettings()),
				new DeleteChildAction($this->getSettings()),
			]);
		}

		return $actions;
	}
}