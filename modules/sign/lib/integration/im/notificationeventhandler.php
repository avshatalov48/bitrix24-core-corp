<?php

namespace Bitrix\Sign\Integration\im;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Integration\Im\Notification\PresenterRegionFactory;

class NotificationEventHandler
{
	public const REMIND_EVENT = 'remind';

	public static function onGetNotifySchema(): array
	{
		return [
			'sign' => [
				'NAME' => PresenterRegionFactory::makeByContextRegion()->getModuleName(),
				'NOTIFY' => [
					self::REMIND_EVENT => [
						'NAME' => Loc::getMessage('SIGN_INTEGRATION_IM_NOTIFY_SCHEMA_REMIND'),
						'MAIL' => 'N',
					],
				],
			],
		];
	}
}