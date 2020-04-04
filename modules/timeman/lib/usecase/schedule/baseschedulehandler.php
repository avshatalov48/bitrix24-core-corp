<?php
namespace Bitrix\Timeman\UseCase\Schedule;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\UseCase\BaseUseCaseHandler;

Loc::loadMessages(__FILE__);

class BaseScheduleHandler extends BaseUseCaseHandler
{
	protected function getScheduleService()
	{
		return DependencyManager::getInstance()->getScheduleService();
	}
}