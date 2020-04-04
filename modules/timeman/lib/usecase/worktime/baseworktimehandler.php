<?php
namespace Bitrix\Timeman\UseCase\Worktime;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\UseCase\BaseUseCaseHandler;

Loc::loadMessages(__FILE__);

class BaseWorktimeHandler extends BaseUseCaseHandler
{
	protected function getWorktimeService()
	{
		return DependencyManager::getInstance()->getWorktimeService();
	}
}