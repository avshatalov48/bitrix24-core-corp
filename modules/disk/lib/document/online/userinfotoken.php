<?php

namespace Bitrix\Disk\Document\Online;

use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\TimeSigner;

class UserInfoToken
{
	public static function generateTimeLimitedToken(int $userId, int $objectId): string
	{
		$timeSigner = new TimeSigner();

		return $timeSigner->sign((string)$userId, '+7 days', self::getSalt($objectId));
	}

	public static function checkTimeLimitedToken(string $token, int $desiredUserId, int $objectId): bool
	{
		try
		{
			$timeSigner = new TimeSigner();
			$unsignedUserId = (int)$timeSigner->unsign($token, self::getSalt($objectId));

			return $unsignedUserId === $desiredUserId;
		}
		catch (BadSignatureException $e)
		{
			return false;
		}
	}

	protected static function getSalt(int $objectId): string
	{
		return "DISK_OBJECT_{$objectId}";
	}
}