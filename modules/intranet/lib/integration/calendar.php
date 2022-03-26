<?php
namespace Bitrix\Intranet\Integration;

use Bitrix\Intranet\Secretary;
use Bitrix\Mail\Helper\Message;
use Bitrix\Mail\Internals\MessageAccessTable;
use Bitrix\Main\Loader;

final class Calendar
{
	/**
	 * Calendar event update handler.
	 *
	 * @param $eventId
	 * @param $entryFields
	 * @param $currentEventAttendeeList
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function onCalendarEventUpdate($eventId, $entryFields, $currentEventAttendeeList): void
	{
		self::updateCalendarChatParticipants($eventId, $entryFields, $currentEventAttendeeList);
	}

	/**
	 * Calendar event delete handler.
	 *
	 * @param $eventId
	 * @param $entry
	 */
	public static function OnCalendarEventDelete($eventId, $entry): void
	{
		self::rejectAccessToMailMessages($eventId);
	}

	/**
	 * Revoke access to message for calendar event.
	 *
	 * @see Secretary
	 * @see \Bitrix\Mail\MessageAccess
	 *
	 * @param int $eventId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function rejectAccessToMailMessages(int $eventId): void
	{
		if (Loader::includeModule('mail'))
		{
			$list = MessageAccessTable::getList([
				'select' => ['TOKEN'],
				'filter' => [
					'=ENTITY_TYPE' => Message::ENTITY_TYPE_CALENDAR_EVENT,
					'=ENTITY_ID' => $eventId,
				],
			])->fetchAll();
			foreach ($list as $row)
			{
				MessageAccessTable::delete($row['TOKEN']);
			}
		}
	}

	/**
	 * Update chat created for calendar event.
	 *
	 * @param $eventId
	 * @param $entryFields
	 * @param $currentEventAttendeeList
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function updateCalendarChatParticipants($eventId, $entryFields, $currentEventAttendeeList): void
	{
		if (
			$entryFields['ID'] !== $entryFields['PARENT_ID']
			|| !isset($entryFields['ATTENDEES'])
		)
		{
			return;
		}

		if (!Loader::includeModule('im'))
		{
			return;
		}

		$meetingData = unserialize($entryFields['MEETING'], ['allowed_classes' => false]);

		if (isset($meetingData['CHAT_ID']))
		{
			$chatId = $meetingData['CHAT_ID'];
		}
		else
		{
			return;
		}

		$currentUsers = [];

		if (is_array($currentEventAttendeeList) && !empty($currentEventAttendeeList))
		{
			foreach ($currentEventAttendeeList as $key => $data)
			{
				$currentUsers[] = $data['id'];
			}
		}

		$addedUsers = array_diff($entryFields['ATTENDEES'], $currentUsers);
		$deletedUsers = array_diff($currentUsers, $entryFields['ATTENDEES']);

		if (empty($addedUsers) && empty($deletedUsers))
		{
			return;
		}

		Secretary::updateChatUsers($chatId, $addedUsers, $deletedUsers);
	}
}