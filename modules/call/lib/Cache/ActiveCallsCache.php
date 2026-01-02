<?php

namespace Bitrix\Call\Cache;

use Bitrix\Call\JwtCall;
use Bitrix\Im\Call\Registry;
use Bitrix\Im\Call\Util;
use Bitrix\Im\V2\Call\CallFactory;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;

class ActiveCallsCache
{
	private const CACHE_TTL = 86400;
	private const CACHE_DIR = 'call/active_calls';

	/**
	 * Generates cache ID for user's active calls
	 *
	 * @param int $userId User ID
	 * @return string Cache ID string
	 */
	private static function getUserCacheId(int $userId): string
	{
		return 'call/active_calls_' . $userId;
	}

	/**
	 * Generates cache tag for user's active calls
	 *
	 * @param int $userId User ID
	 * @return string Cache tag string
	 */
	private static function getUserCacheTag(int $userId): string
	{
		return 'call/active_calls_user_' . $userId;
	}

	/**
	 * Generates cache tag for a specific call
	 *
	 * @param int $callId Call ID
	 * @return string Cache tag string
	 */
	private static function getCallCacheTag(int $callId): string
	{
		return 'call/active_call_call_' . $callId;
	}

	/**
	 * Retrieves active calls for a specific user from cache or rebuilds if not cached
	 *
	 * @param int $userId User ID
	 * @return array Array of active calls data for the user
	 */
	public static function getActiveCallsForUser(int $userId): array
	{
		$cache = Cache::createInstance();
		if ($cache->initCache(self::CACHE_TTL, self::getUserCacheId($userId), self::CACHE_DIR))
		{
			return $cache->getVars();
		}

		return self::updateUserActiveCallsCache($userId);
	}

	/**
	 * Updates and rebuilds the active calls cache for a specific user
	 *
	 * @param int $userId User ID
	 * @return array Updated active calls data for the user
	 */
	public static function updateUserActiveCallsCache(int $userId): array
	{
		$cache = Cache::createInstance();
		$data = self::buildActiveCallsArray($userId);

		$cache->forceRewriting(true);
		if ($cache->startDataCache(self::CACHE_TTL, self::getUserCacheId($userId), self::CACHE_DIR))
		{
			$tagged = Application::getInstance()->getTaggedCache();
			$tagged->startTagCache(self::CACHE_DIR);

			$tagged->registerTag(self::getUserCacheTag($userId));

			foreach ($data as $callId => $callData)
			{
				$tagged->registerTag(self::getCallCacheTag($callId));
			}

			$tagged->endTagCache();
			$cache->endDataCache($data);
		}
		return $data;
	}

	/**
	 * Updates cache for all users participating in a specific call
	 *
	 * @param int $callId Call ID
	 * @return void
	 */
	public static function updateCallCache(int $callId): void
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return;
		}

		$userRows = \Bitrix\Im\Model\CallUserTable::query()
			->addSelect('USER_ID')
			->where('CALL_ID', $callId)
			->fetchAll();

		if (!$userRows)
		{
			return;
		}

		$userIds = array_map('intval', array_column($userRows, 'USER_ID'));
		foreach ($userIds as $userId)
		{
			self::updateUserActiveCallsCache($userId);
		}
	}

	/**
	 * Builds array of active calls data for a specific user
	 *
	 * @param int $userId User ID
	 * @return array Array of active calls with full data including tokens and user info
	 */
	private static function buildActiveCallsArray(int $userId): array
	{
		$activeCalls = CallFactory::getUserActiveCalls($userId);
		return array_reduce(
			$activeCalls,
			function ($result, $call) use ($userId) {
				$callInstance = CallFactory::getCallInstance($call['PROVIDER'], $call);
				$callUsers = $callInstance->getUsers();
				$result[$call['ID']] = array_merge(
					$callInstance->toArray($userId),
					[
						'CALL_TOKEN' => JwtCall::getCallToken($call['CHAT_ID']),
						'CONNECTION_DATA' => $callInstance->getConnectionData($userId),
						'USERS' => $callUsers,
						'LOG_TOKEN' => $callInstance->getLogToken($userId),
						'USER_DATA' => Util::getUsers($callUsers),
					]
				);
				return $result;
			},
			[]
		);
	}

	/**
	 * Clears active calls cache for a specific user
	 *
	 * @param int $userId User ID
	 * @return void
	 */
	public static function clearUserActiveCallsCache(int $userId): void
	{
		$tagged = Application::getInstance()->getTaggedCache();
		$tagged->clearByTag(self::getUserCacheTag($userId));
	}

	/**
	 * Clears cache for a specific call across all users
	 *
	 * @param int $callId Call ID
	 * @return void
	 */
	public static function clearCallCache(int $callId): void
	{
		$tagged = Application::getInstance()->getTaggedCache();
		$tagged->clearByTag(self::getCallCacheTag($callId));
	}
}
