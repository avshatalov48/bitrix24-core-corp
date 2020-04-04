<?php
namespace Bitrix\Timeman\UseCase;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Service\DependencyManager;

Loc::loadMessages(__FILE__);

class BaseUseCaseHandler
{
	protected function getPermissionManager()
	{
		global $USER;
		return DependencyManager::getInstance()->getUserPermissionsManager($USER);
	}
}