<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action;

use Bitrix\Intranet\User\Access\UserAccessController;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\ConfirmChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\DeclineChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\DeleteChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\FireChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\ReInviteChildAction;
use Bitrix\Intranet\User\Grid\Panel\Action\Group\UserAccessChildAction;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Panel\Action\GroupAction;

class UserGroupAction extends GroupAction
{
	private const ACCESS_ACTIONS = [
		ConfirmChildAction::class,
		DeclineChildAction::class,
		FireChildAction::class,
		DeleteChildAction::class,
	];

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

		$access = UserAccessController::createByDefault();
		/** @var UserAccessChildAction $actionClass */
		foreach (self::ACCESS_ACTIONS as $actionClass)
		{
			if ($access->check($actionClass::getActionType()))
			{
				$actions[] = new $actionClass($this->getSettings());
			}
		}

		return $actions;
	}
}