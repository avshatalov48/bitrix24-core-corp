<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use CVoxImplantHistory;

class VoxImplantManager
{
	private const ORIGIN_ID_PREFIX = 'VI_';

	private static array $callInfoCache = [];

	public static function getCallInfo(?string $callId): ?array
	{
		if (!Loader::includeModule('voximplant'))
		{
			return null;
		}

		if (empty($callId))
		{
			return null;
		}

		if (!isset(self::$callInfoCache[$callId]))
		{
			$info = CVoxImplantHistory::getBriefDetails($callId);
			if (is_array($info))
			{
				self::$callInfoCache[$callId] = $info;
			}
		}

		return self::$callInfoCache[$callId] ?? null;
	}

	public static function getCallDuration(string $callId): ?int
	{
		$info = self::getCallInfo($callId) ?? [];

		return isset($info['DURATION']) ? (int)$info['DURATION'] : null;
	}

	public static function saveComment(string $callId, $comment): void
	{
		if (!Loader::includeModule('voximplant'))
		{
			return;
		}

		$comment = is_string($comment) ? $comment : '';

		CVoxImplantHistory::saveComment($callId, $comment);

		if (isset(self::$callInfoCache[$callId]))
		{
			unset(self::$callInfoCache[$callId]);
		}
	}

	final public static function isActivityBelongsToVoximplant(array $activityFields): bool
	{
		return (
			isset($activityFields['PROVIDER_ID'])
			&& $activityFields['PROVIDER_ID'] === Call::ACTIVITY_PROVIDER_ID
			&& isset($activityFields['ORIGIN_ID'])
			&& is_string($activityFields['ORIGIN_ID'])
			&& self::isVoxImplantOriginId($activityFields['ORIGIN_ID'])
		);
	}

	final public static function isVoxImplantOriginId(string $originId): bool
	{
		return str_starts_with($originId, self::ORIGIN_ID_PREFIX);
	}

	final public static function extractCallIdFromOriginId(string $originId): string
	{
		if (!self::isVoxImplantOriginId($originId))
		{
			throw new ArgumentException('originId should belong to voximplant');
		}

		return str_replace(self::ORIGIN_ID_PREFIX, '', $originId);
	}

	final public static function insertPrefix(string $callId): string
	{
		if (self::isVoxImplantOriginId($callId))
		{
			return $callId;
		}

		return self::ORIGIN_ID_PREFIX . $callId;
	}
}
