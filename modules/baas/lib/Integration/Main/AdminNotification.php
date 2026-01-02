<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Main;

use Bitrix\Main\Localization\Loc;

class AdminNotification
{
	protected const NOTIFICATION_TAG = 'baas_registration_failed';

	public function show(): void
	{
		\CAdminNotify::add([
			'MESSAGE' => Loc::getMessage(
				'BAAS_REGISTRATION_ADMIN_NOTIFY_WARN',
				['#LANGUAGE_ID#' => LANGUAGE_ID],
			),
			'TAG' => self::NOTIFICATION_TAG,
			'MODULE_ID' => 'baas',
			'ENABLE_CLOSE' => 'N',
			'NOTIFY_TYPE' => 'E',
		]);
	}

	public function hide(): void
	{
		\CAdminNotify::DeleteByTag(self::NOTIFICATION_TAG);
	}

	public static function onDomainRegistered(): void
	{
		(new static())->hide();
	}
}
