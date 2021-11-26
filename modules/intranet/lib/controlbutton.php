<?php
namespace Bitrix\Intranet;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class ControlButton
{
	public static function udpateChatUsers($chatId, $addedUsers, $deletedUsers): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$chat = new \CIMChat(0);

		if (!empty($deletedUsers))
		{
			foreach ($deletedUsers as $key => $userId)
			{
				$chat->DeleteUser($chatId, $userId, false);
			}
		}

		if (!empty($addedUsers))
		{
			$chat->AddUser($chatId, $addedUsers);
		}
	}

	public static function addUserToChat($chatId, $userId): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$chat = new \CIMChat(0);
		$chat->AddUser($chatId, $userId);
	}

	public static function createCalendarChat($calendarData, $userId): int
	{
		$chat = new \CIMChat(0);
		$chatFields = [
			'TITLE' => Loc::getMessage(
				'INTRANET_CONTROL_BUTTON_CALENDAR_CHAT_TITLE',
				['#EVENT_TITLE#' => $calendarData['TITLE']]
			),
			'TYPE' => IM_MESSAGE_CHAT,
			'ENTITY_TYPE' => \CCalendar::CALENDAR_CHAT_ENTITY_TYPE,
			'ENTITY_ID' => $calendarData['ID'],
			'SKIP_ADD_MESSAGE' => 'Y',
			'AUTHOR_ID' => $userId,
			'USERS' => $calendarData['USER_IDS']
		];

		$chatId = $chat->add($chatFields);

		if ($chatId)
		{
			$pathToCalendar = \CCalendar::GetPathForCalendarEx($userId);
			$pathToEvent = \CHTTP::urlAddParams($pathToCalendar, ['EVENT_ID' => $calendarData['ID']]);
			$entryLinkTitle = '[url=' . $pathToEvent . ']' . $calendarData['TITLE'] . '[/url]';
			$chatMessageFields = [
				'FROM_USER_ID' => $userId,
				'MESSAGE' => Loc::getMessage(
					'INTRANET_CONTROL_BUTTON_CALENDAR_CHAT_FIRST_MESSAGE',
					[
						'#EVENT_TITLE#' => $entryLinkTitle,
						'#DATETIME_FROM#' => \CCalendar::Date(
							\CCalendar::Timestamp($calendarData['DATE_FROM']),
							$calendarData['DT_SKIP_TIME'] === 'N',
							true, true
						)
					]
				),
				'SYSTEM' => 'Y',
				'INCREMENT_COUNTER' => 'N',
				'PUSH' => 'Y',
				'TO_CHAT_ID' => $chatId,
				'SKIP_USER_CHECK' => 'Y',
				'SKIP_COMMAND' => 'Y'
			];

			\CIMChat::addMessage($chatMessageFields);

			$calendarData['MEETING']['CHAT_ID'] = $chatId;
			$response['id'] = \CCalendar::SaveEvent([
				'arFields' => [
					'ID' => $calendarData['ID'],
					'MEETING' => $calendarData['MEETING']
				],
				'checkPermission' => false,
				'userId' => $calendarData['CREATED_BY']
			]);

			\CCalendar::ClearCache('event_list');
		}

		return $chatId;
	}

	public static function createTaskChat($taskData, $userId): int
	{
		$chat = new \CIMChat(0);
		$chatFields = [
			'TITLE' => Loc::getMessage(
				'INTRANET_CONTROL_BUTTON_TASK_CHAT_TITLE',
				['#TASK_TITLE#' => $taskData['TITLE']]
			),
			'TYPE' => IM_MESSAGE_CHAT,
			'ENTITY_TYPE' => 'TASKS',
			'ENTITY_ID' => $taskData['ID'],
			'SKIP_ADD_MESSAGE' => 'Y',
			'AUTHOR_ID' => $userId,
			'USERS' => $taskData['USER_IDS']
		];

		$chatId = $chat->add($chatFields);

		if ($chatId)
		{
			$pathToTask = SITE_DIR . 'company/personal/user/' . $taskData['CREATED_BY'] . '/tasks/task/view/' . $taskData['ID'] . '/';
			$entryLinkTitle = '[url=' . $pathToTask . ']' . $taskData['TITLE'] . '[/url]';
			$chatMessageFields = [
				'FROM_USER_ID' => $userId,
				'MESSAGE' => Loc::getMessage(
					'INTRANET_CONTROL_BUTTON_TASK_CHAT_FIRST_MESSAGE',
					[
						'#TASK_TITLE#' => $entryLinkTitle,
					]
				),
				'SYSTEM' => 'Y',
				'INCREMENT_COUNTER' => 'N',
				'PUSH' => 'Y',
				'TO_CHAT_ID' => $chatId,
				'SKIP_USER_CHECK' => 'Y',
				'SKIP_COMMAND' => 'Y'
			];

			\CIMChat::addMessage($chatMessageFields);
		}

		return $chatId;
	}
}
