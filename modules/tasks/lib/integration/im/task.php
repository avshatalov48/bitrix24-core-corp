<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\IM;


use Bitrix\Main\Localization\Loc;
use Bitrix\Im\Model\ChatTable;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Util;

Loc::loadMessages(__FILE__);

final class Task extends IM
{
	/**
	 * Posts new message to a specified chat
	 *
	 * @param $chatId
	 * @param $message
	 * @param null $task
	 */
	public static function postMessage($chatId, $message, $task = null)
	{
		$attach = null;
		if(is_array($task))
		{
			$attach = new \CIMMessageParamAttach(1, '#E30000'); // red color
			$attach->AddUser(Array(
				'NAME' => $task['TITLE'],
				'AVATAR' => BX_ROOT.'/js/tasks/images/im/chat.png',
				'LINK' => \CTaskNotifications::getNotificationPath(array('ID' => $task['RESPONSIBLE_ID']), $task['ID']),
			));

			$gridRows = array();

			if($task['STATUS'])
			{
				$status = Loc::getMessage('TASKS_TASK_STATUS_'.$task['STATUS']);

				if($status)
				{
					$gridRows[] = array(
						"NAME" => Loc::getMessage('TASKS_TASK_ENTITY_STATUS_PSEUDO_FIELD').':',
						"VALUE" => Loc::getMessage('TASKS_TASK_STATUS_'.$task['STATUS']),
						"DISPLAY" => "COLUMN",
						"WIDTH" => 120,
					);
				}
			}
			if($task['CREATED_BY'])
			{
				$gridRows[] = array(
					"NAME" => Loc::getMessage('TASKS_TASK_ENTITY_CREATED_BY_FIELD').':',
					// getFullName() returns escaped data, we want unescaped
					"VALUE" => htmlspecialcharsback(\Bitrix\Im\User::getInstance($task['CREATED_BY'])->getFullName()),
					"USER_ID" => $task['CREATED_BY'],
					"DISPLAY" => "COLUMN",
					"WIDTH" => 120,
				);
			}
			if($task['RESPONSIBLE_ID'])
			{
				$gridRows[] = array(
					"NAME" => Loc::getMessage('TASKS_TASK_ENTITY_RESPONSIBLE_ID_FIELD').':',
					// getFullName() returns escaped data, we want unescaped
					"VALUE" => htmlspecialcharsback(\Bitrix\Im\User::getInstance($task['RESPONSIBLE_ID'])->getFullName()),
					"USER_ID" => $task['RESPONSIBLE_ID'],
					"DISPLAY" => "COLUMN",
					"WIDTH" => 120,
				);
			}
			if($task['DEADLINE'])
			{
				$userTZOffset = Util\User::getTimeZoneOffsetCurrentUser() + Util::getServerTimeZoneOffset();

				$gridRows[] = array(
					"NAME" => Loc::getMessage('TASKS_TASK_ENTITY_DEADLINE_FIELD').':',
					"VALUE" => ((string) $task['DEADLINE']).' ('.\Bitrix\Tasks\UI::formatTimezoneOffsetUTC($userTZOffset).')',
					"DISPLAY" => "COLUMN",
					"WIDTH" => 120,
				);
			}
			if($task['DESCRIPTION'])
			{
				$description = htmlspecialcharsbx($task['DESCRIPTION']);
				if(mb_strlen($description) > 100)
				{
					$description = mb_substr($description, 0, 100).'...';
				}

				$gridRows[] = array(
					"NAME" => Loc::getMessage('TASKS_TASK_ENTITY_DESCRIPTION_FIELD').':',
					"VALUE" => $description,
					"DISPLAY" => "COLUMN",
					"WIDTH" => 120,
				);
			}

			if(count($gridRows))
			{
				$attach->AddDelimiter();
				$attach->AddGrid($gridRows);
			}
		}

		\CIMChat::AddMessage(Array(
			"FROM_USER_ID" => $task['RESPONSIBLE_ID'],
			"TO_CHAT_ID" => $chatId,
			"MESSAGE" => $message,
			"ATTACH" => $attach,
		));
	}

	/**
	 * Opens new task chat, or returns existing one
	 *
	 * @param $task
	 * @return bool|int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function openChat($task)
	{
		if(!static::includeModule())
		{
			return 0;
		}

		if(is_array($task))
		{
			$res = ChatTable::getList(array(
				'select' => Array('ID'),
				'filter' => array(
					'=ENTITY_TYPE' => 'TASKS',
					'=ENTITY_ID' => $task['ID'],
				),
				'limit' => 1
			));
			$chat = $res->fetch();

			if($chat)
			{
				$chatId = $chat['ID'];
			}
			else
			{
				$chat = new \CIMChat(0);

				$data = array(
					'TITLE' => str_replace('#TASK_TITLE#', $task['TITLE'], Loc::getMessage('TASKS_IM_CHAT_TITLE')),
					'TYPE' => IM_MESSAGE_CHAT,
					'ENTITY_TYPE' => 'TASKS',
					'ENTITY_ID' => $task['ID'],
					'SKIP_ADD_MESSAGE' => 'Y',
					'AUTHOR_ID' => $task['CREATED_BY'],
					'USERS' => array_map(function($member){
						return $member['USER_ID'];
					}, $task['SE_MEMBER']),
				);

				$chatId = $chat->add($data);
			}

			return $chatId;
		}

		return 0;
	}
}