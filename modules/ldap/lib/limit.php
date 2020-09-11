<?php

namespace Bitrix\Ldap;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UserTable;

Loc::loadMessages(__FILE__);

final class Limit
{
	private static $userLimit = 1000;
	private static $userLimitExceededTag = 'LDAP_USER_LIMIT_EXCEEDED';
	private static $isUserLimitExceeded = false;

	public static function isUserLimitExceeded(): bool
	{
		if(self::$isUserLimitExceeded !== true)
		{
			$isFeatureEnabled = \CBXFeatures::IsFeatureEnabled('LdapUnlimitedUsers');
			$featuresList = \CBXFeatures::GetFeaturesList();

			if($isFeatureEnabled && !empty($featuresList))
			{
				self::$isUserLimitExceeded = false;
			}
			else
			{
				self::$isUserLimitExceeded = self::countLdapUsers() >= self::$userLimit;

				if(self::$isUserLimitExceeded)
				{
					self::notifyUserLimitExceeded();
				}
			}
		}

		return self::$isUserLimitExceeded;
	}

	public static function getUserLimitNotifyMessage(): string
	{
		return loc::getMessage(
			'LDAP_LIMIT_USER_LIMIT_EXCEEDED',
			['#LIMIT#' => self::$userLimit]
		);
	}

	private static function notifyUserLimitExceeded(): void
	{
		\CEventLog::Add([
			"SEVERITY" => \CEventLog::SEVERITY_WARNING,
			"AUDIT_TYPE_ID" => self::$userLimitExceededTag,
			"MODULE_ID" => "ldap",
			"DESCRIPTION" => self::getUserLimitNotifyMessage()
		]);

		\CAdminNotify::Add([
			'MODULE_ID' => 'ldap',
			'TAG' => self::$userLimitExceededTag,
			'ENABLE_CLOSE' => 'Y',
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_NORMAL,
			'MESSAGE' => self::getUserLimitNotifyMessage()
		]);
	}

	private static function countLdapUsers(): int
	{
		$result = 0;

		$res = UserTable::query()
			->addSelect(new ExpressionField('CNT', 'COUNT(1)'))
			->whereLike('EXTERNAL_AUTH_ID', 'LDAP#%')
			->exec();

		if($row = $res->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}
}