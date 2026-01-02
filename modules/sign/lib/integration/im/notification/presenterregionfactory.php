<?php

namespace Bitrix\Sign\Integration\Im\Notification;

use Bitrix\Main\Application;

class PresenterRegionFactory
{
	public static function makeByContextRegion(): CommonPresenter
	{
		return match (Application::getInstance()->getLicense()->getRegion())
		{
			'ru' => new RuRegionPresenter(),
			default => new CommonPresenter(),
		};
	}
}