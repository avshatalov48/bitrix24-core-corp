<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Panel\Action\DataProvider;
use Bitrix\Main\ModuleManager;

/**
 * @method UserSettings getSettings()
 */
class UserDataProvider extends DataProvider
{
	public function prepareActions(): array
	{
		$actions = [];

		if ($this->getSettings()->isCurrentUserAdmin())
		{
			$actions[] = new ChangeDepartmentAction($this->getSettings());
		}

		if (ModuleManager::isModuleInstalled('im'))
		{
			$actions[] = new CreateChatAction($this->getSettings());
		}

		return [
			...$actions,
			new UserGroupAction($this->getSettings()),
			new \Bitrix\Main\Grid\Panel\Action\ForAllCheckboxAction(),
		];
	}
}
