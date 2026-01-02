<?php

namespace Bitrix\Call;

use Bitrix\Main\Loader;
use Bitrix\Im\Model\CallTable;
use Bitrix\Im\V2\Call\CallFactory;
use Bitrix\Main\Type\DateTime;
use Bitrix\Call\Cache\ActiveCallsCache;

class Call
{
	public const ACTIVE_CALLS_DEPTH_HOURS = 12;

	private static ?ActiveCallsCache $callsCache = null;

	private static function callsCache(): ActiveCallsCache
	{
		if (!self::$callsCache)
		{
			self::$callsCache = new ActiveCallsCache();
		}

		return self::$callsCache;
	}

	protected static function getCurrentUserId(): int
	{
		global $USER;

		return $USER->getId();
	}

	/**
	 * Gets list active calls of a user on portal
	 *
	 * @return array
	 */
	public static function getActiveCalls(): array
	{
		if (!Settings::isNewCallsEnabled())
		{
			return [];
		}

		$userId = self::getCurrentUserId();

		return self::callsCache()->getActiveCallsForUser($userId);
	}

	public static function finishActiveCalls(int $depthHours = 12): void
	{
		Loader::includeModule('im');

		$callList = CallTable::getList([
			'select' => ['*'],
			'filter' => [
				'!=STATE' => \Bitrix\Im\Call\Call::STATE_FINISHED,
				'<START_DATE' => (new DateTime())->add("-{$depthHours} hour"),
			]
		]);

		while ($row = $callList->fetch())
		{
			$call = CallFactory::createWithArray($row['PROVIDER'], $row);
			$call->finish();
			self::callsCache()->updateCallCache($call->getId());
		}
	}

	/**
	 * Updates and rebuilds the active calls cache for a specific user
	 *
	 * @param int $userId User ID
	 * @return array Updated active calls data for the user
	 */
	public static function updateUserActiveCallsCache(int $userId): array
	{
		return self::callsCache()->updateUserActiveCallsCache($userId);
	}

	/**
	 * Updates cache for all users participating in a specific call
	 *
	 * @param int $callId Call ID
	 * @return void
	 */
	public static function updateCallCache(int $callId): void
	{
		self::callsCache()->updateCallCache($callId);
	}

	/**
	 * Terminates all active calls in a chat except the specified call
	 *
	 * @param int $chatId Chat ID
	 * @param int|null $excludeCallId Call ID to exclude from termination
	 * @return void
	 */
	public static function terminateAllCallsInChat(int $chatId, ?int $excludeCallId = null): void
	{
		if (!$chatId)
		{
			return;
		}

		if (!Loader::includeModule('im'))
		{
			return;
		}

		$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
		if ($chat instanceof \Bitrix\Im\V2\Chat\NullChat)
		{
			return;
		}

		$provider = $entityId = '';
		if ($chat instanceof \Bitrix\Im\V2\Chat\PrivateChat)
		{
			$provider = \Bitrix\Im\Call\Call::PROVIDER_PLAIN;
			$entityId = $chatId;
		}
		else
		{
			$provider = \Bitrix\Im\Call\Call::PROVIDER_BITRIX;
			$entityId = 'chat' . $chatId;
		}

		$filter = [
			'=PROVIDER' => $provider,
			'=ENTITY_TYPE' => \Bitrix\Im\Call\Integration\EntityType::CHAT,
			'=ENTITY_ID' => $entityId,
			'!=STATE' => \Bitrix\Im\Call\Call::STATE_FINISHED,
			'>START_DATE' => (new DateTime())->add('-24 hours'),
		];

		if ($excludeCallId)
		{
			$filter['!=ID'] = $excludeCallId;
		}

		$callList = CallTable::getList([
			'select' => ['*'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC']
		]);

		while ($callData = $callList->fetch())
		{
			// Reset END_DATE to null before creating the call instance to ensure proper finish() behavior
			$callData['END_DATE'] = null;

			$activeCall = CallFactory::createWithArray($callData['PROVIDER'], $callData);
			if ($activeCall->getState() === \Bitrix\Im\Call\Call::STATE_FINISHED)
			{
				continue;
			}

			$activeCall->finish();
			self::updateCallCache($activeCall->getId());
		}
	}

	public static function finishOldCallsAgent(): string
	{
		if (!Loader::includeModule('im'))
		{
			return __METHOD__ . '();';
		}

		$callList = CallTable::getList([
			'select' => ['*'],
			'filter' => [
				'!=STATE' => \Bitrix\Im\Call\Call::STATE_FINISHED,
				'<START_DATE' => (new DateTime())->add('-' . self::ACTIVE_CALLS_DEPTH_HOURS . ' hour'),
			]
		]);

		while ($row = $callList->fetch())
		{
			$call = CallFactory::createWithArray($row['PROVIDER'], $row);
			$call->finish();

			self::callsCache()->updateCallCache($call->getId());

			(new \Bitrix\Call\Analytics\CallAnalytics($call))->finishOldCalls();
		}

		return __METHOD__ . '();';
	}
}
