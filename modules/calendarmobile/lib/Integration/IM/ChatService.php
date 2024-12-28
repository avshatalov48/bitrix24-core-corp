<?php

namespace Bitrix\CalendarMobile\Integration\IM;

use Bitrix\Im\Dialog;
use Bitrix\Intranet\Secretary;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\UserTable;

class ChatService
{
	public function __construct(private readonly int $userId)
	{
	}

	/**
	 * @param int $eventId
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEventChatId(int $eventId): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Need module is not installed'));
		}

		$event = $this->getEvent($eventId);
		if (!$event)
		{
			return $result->addError(new Error('Event not found'));
		}

		$event['NOT_DECLINED_IDS'] = $this->prepareChatUsers($event['NOT_DECLINED_IDS']);
		if (empty($event['NOT_DECLINED_IDS']))
		{
			return $result->addError(new Error('No chat users found'));
		}

		if ($event['MEETING']['CHAT_ID'] > 0)
		{
			$chatId = (int)$event['MEETING']['CHAT_ID'];
			Secretary::addUserToChat($chatId, $this->userId);

			return $result->setData(['chatId' => $chatId]);
		}

		$parentEvent = [];
		if ($event['RECURRENCE_ID'])
		{
			$parentEvent = $this->getEvent($event['RECURRENCE_ID']);
		}

		$chatId = Secretary::createCalendarChat($event, $this->userId, $parentEvent);

		return $result->setData(['chatId' => $chatId]);
	}

	/**
	 * @param string $dialogId
	 * @param string $message
	 *
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendMessage(string $dialogId, string $message): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Need module is not installed'));
		}

		$chatId = Dialog::getChatId($dialogId);

		if (empty($chatId))
		{
			return $result->addError(new Error('Chat id not found'));
		}

		\CIMChat::AddMessage([
			'FROM_USER_ID' => $this->userId,
			'DIALOG_ID' => $dialogId,
			'MESSAGE' => $message,
		]);

		return $result;
	}

	/**
	 * @param int $eventId
	 *
	 * @return array|null
	 */
	private function getEvent(int $eventId): ?array
	{
		$event = \CCalendarEvent::getEventForViewInterface($eventId);
		if (!$event)
		{
			return null;
		}

		$notDeclinedIds = null;
		if (is_array($event['ATTENDEE_LIST']))
		{
			// exclude decliners
			$notDeclinedIds =  array_column(
				array_filter(
					$event['ATTENDEE_LIST'],
					static fn (array $attendee) => $attendee['status'] !== 'N'
				),
				'id'
			);
		}

		return [
			'ID' => $event['ID'],
			'TITLE' => $event['NAME'],
			'CREATED_BY' => $event['CREATED_BY'],
			'DATE_FROM' => $event['DATE_FROM'],
			'DT_SKIP_TIME' => $event['DT_SKIP_TIME'],
			'MEETING' => $event['MEETING'],
			'RECURRENCE_ID' => $event['RECURRENCE_ID'],
			'USER_IDS' => is_array($event['ATTENDEE_LIST']) ? array_column($event['ATTENDEE_LIST'], 'id') : [$event['CREATED_BY']],
			'NOT_DECLINED_IDS' => $notDeclinedIds,
		];
	}

	/**
	 * @param array $users
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function prepareChatUsers(array $users): array
	{
		$result = [];
		$externalUserTypes = UserTable::getExternalUserTypes();

		$queryResult = UserTable::query()
			->setSelect(['ID', 'EXTERNAL_AUTH_ID'])
			->whereIn('ID', $users)
			->exec()->fetchAll()
		;

		foreach ($queryResult as $user)
		{
			if (!in_array($user['EXTERNAL_AUTH_ID'], $externalUserTypes, true))
			{
				$result[] = (int)$user['ID'];
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function isAvailable(): bool
	{
		return Loader::includeModule('im') && Loader::includeModule('intranet');
	}
}
