<?php
namespace Bitrix\Intranet\Integration;

use Bitrix\Main\Loader;
use Bitrix\Intranet\ControlButton;

final class Calendar
{
	public static function onCalendarEventUpdate($eventId, $entryFields, $currentEventAttendeeList): void
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

		ControlButton::udpateChatUsers($chatId, $addedUsers, $deletedUsers);
	}
}