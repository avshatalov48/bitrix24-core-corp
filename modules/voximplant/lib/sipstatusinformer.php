<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

class SipStatusInformer
{
	const LEVEL_INFO_ADMINS = 'admins';

	const SHORT_STATUS_FAILED = "SIP_SHORT_STATUS_INFORMER_FAILED_WITH_LINK";
	const SHORT_STATUS_RECOVERED = "SIP_SHORT_STATUS_INFORMER_RECOVERED";
	const LONG_STATUS_FAILED = "SIP_LONG_STATUS_INFORMER_FAILED_WITH_LINK";
	const LONG_STATUS_RECOVERED = "SIP_LONG_STATUS_INFORMER_RECOVERED";

	public static function notifyStatusUpdate($status, array $substitutions = [], $levelInformer = self::LEVEL_INFO_ADMINS)
	{
		$message = static::getStatusText($status, $substitutions);

		if($levelInformer == self::LEVEL_INFO_ADMINS)
		{
			\Bitrix\Voximplant\Integration\Im::notifyChangeSipRegistrationStatus($message);
		}

	}

	private static function getStatusText($status, $substitutions)
	{
		if((int)$substitutions['#STATUS_CODE#'] == 200)
		{
			return static::getStatusRecoveredText($substitutions);
		}
		else
		{
			return static::getStatusFailedText($substitutions);
		}
	}

	private static function getStatusFailedText($substitutions)
	{
		if($substitutions['#PHONE_NAME#'] === '')
		{
			return Loc::getMessage(self::LONG_STATUS_FAILED, $substitutions);

		}
		else
		{
			return Loc::getMessage(self::SHORT_STATUS_FAILED, $substitutions);
		}
	}

	private static function getStatusRecoveredText($substitutions)
	{
		if($substitutions['#PHONE_NAME#'] === '')
		{
			return Loc::getMessage(self::LONG_STATUS_RECOVERED, $substitutions);
		}
		else
		{
			return Loc::getMessage(self::SHORT_STATUS_RECOVERED, $substitutions);
		}
	}
}
