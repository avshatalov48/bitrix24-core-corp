<?php
namespace Bitrix\Im\Replica;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (Loader::includeModule('replica'))
{
	class MessageHandler extends \Bitrix\Replica\Client\BaseHandler
	{
		protected $tableName = "b_im_message";
		protected $moduleId = "im";
		protected $className = "\\Bitrix\\Im\\Model\\MessageTable";
		protected $primary = array(
			"ID" => "auto_increment",
		);
		protected $predicates = array(
			"AUTHOR_ID" => "b_user.ID",
			"CHAT_ID" => "b_im_chat.ID",
		);
		protected $translation = array(
			"ID" => "b_im_message.ID",
			"CHAT_ID" => "b_im_chat.ID",
			"AUTHOR_ID" => "b_user.ID",
		);
		protected $fields = array(
			"DATE_CREATE" => "datetime",
			"MESSAGE" => "text",
			"MESSAGE_OUT" => "text",
		);

		const LOADER_PLACEHOLDER = '[B][/B]';

		/**
		 * Called before log write. You may return false and not log write will take place.
		 *
		 * @param array $record Database record.
		 * @return boolean
		 */
		public function beforeLogInsert(array $record)
		{
			if ($record["NOTIFY_TYPE"] <= 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Method will be invoked before new database record inserted.
		 * When an array returned the insert will be cancelled and map for
		 * returned record will be added.
		 *
		 * @param array &$newRecord All fields of inserted record.
		 *
		 * @return null|array
		 */
		public function beforeInsertTrigger(array &$newRecord)
		{
			if ($newRecord["CHAT_ID"] <= 0)
			{
				return array("ID" => 0);
			}

			$newRecord["MESSAGE"] = $this->fixMessage($newRecord["MESSAGE"]);
			if ($newRecord["MESSAGE"] == "")
			{
				$newRecord["MESSAGE"] = self::LOADER_PLACEHOLDER;
			}
			return null;
		}

		/**
		 * Method will be invoked before an database record updated.
		 *
		 * @param array $oldRecord All fields before update.
		 * @param array &$newRecord All fields after update.
		 *
		 * @return void
		 */
		public function beforeUpdateTrigger(array $oldRecord, array &$newRecord)
		{
			if (array_key_exists("MESSAGE", $newRecord))
			{
				$newRecord["MESSAGE"] = $this->fixMessage($newRecord["MESSAGE"]);
			}
		}

		/**
		 * Replaces some BB codes on receiver to display them properly.
		 *
		 * @param string $message A message.
		 *
		 * @return string
		 */
		protected function fixMessage($message)
		{
			$fixed = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1", $message);
			if ($fixed == null)
			{
				return $message;
			}
			$fixed = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1", $fixed);

			if ($fixed == null)
			{
				return $message;
			}

			return $fixed;
		}

		/**
		 * Method will be invoked after new database record inserted.
		 *
		 * @param array $newRecord All fields of inserted record.
		 *
		 * @return void
		 */
		public function afterInsertTrigger(array $newRecord)
		{
			$arParams = array();

			$chatId = $newRecord['CHAT_ID'];
			$arRel = \CIMChat::GetRelationById($chatId);

			$arFields['MESSAGE_TYPE'] = '';
			foreach ($arRel as $rel)
			{
				$arFields['MESSAGE_TYPE'] = $rel["MESSAGE_TYPE"];
				break;
			}
			$arFields['PARAMS'] = Array();
			$arFields['FILES'] = Array();

			if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
			{
				foreach ($arRel as $rel)
				{
					if ($rel['USER_ID'] == $newRecord['AUTHOR_ID'])
						$arFields['FROM_USER_ID'] = $rel['USER_ID'];
					else
						$arFields['TO_USER_ID'] = $rel['USER_ID'];
				}

				foreach ($arRel as $rel)
				{
					\CIMContactList::SetRecent(Array(
						'ENTITY_ID' => $rel['USER_ID'] == $arFields['TO_USER_ID']? $arFields['FROM_USER_ID']: $arFields['TO_USER_ID'],
						'MESSAGE_ID' => $newRecord['ID'],
						'CHAT_TYPE' => IM_MESSAGE_PRIVATE,
						'USER_ID' => $rel['USER_ID'],
						'CHAT_ID' => $chatId,
						'RELATION_ID' => $rel['ID']
					));
				}

				if (\CModule::IncludeModule('pull'))
				{
					$pullMessage = Array(
						'module_id' => 'im',
						'command' => 'message',
						'params' => \CIMMessage::GetFormatMessage(Array(
							'ID' => $newRecord['ID'],
							'CHAT_ID' => $chatId,
							'TO_USER_ID' => $arFields['TO_USER_ID'],
							'FROM_USER_ID' => $arFields['FROM_USER_ID'],
							'SYSTEM' => $newRecord['NOTIFY_EVENT'] == 'private_system'? 'Y': 'N',
							'MESSAGE' => $newRecord['MESSAGE'],
							'DATE_CREATE' => time(),
							'PARAMS' => $arFields['PARAMS'],
							'FILES' => $arFields['FILES'],
							'NOTIFY' => true
						)),
						'extra' => \Bitrix\Im\Common::getPullExtra()
					);
					$relations = \Bitrix\Im\Chat::getRelation($chatId, Array(
						'REAL_COUNTERS' => 'Y',
						'USER_DATA' => 'Y',
					));
					$pullMessage['params']['dialogId'] = $arFields['FROM_USER_ID'];
					$pullMessage['params']['counter'] = $relations[$arFields['TO_USER_ID']]['COUNTER'];

					$pullMessageTo = $pullMessage;

					if (\CPullOptions::GetPushStatus())
					{
						if (\CIMSettings::GetNotifyAccess($arFields["TO_USER_ID"], 'im', 'message', \CIMSettings::CLIENT_PUSH))
						{
							$pushParams = $pullMessage;
							$pushParams['params']['message']['text_push'] = $newRecord['MESSAGE'];
							$pushParams = \CIMMessenger::PreparePushForPrivate($pushParams);
							$pullMessageTo = array_merge($pullMessage, $pushParams);
						}
					}

					\Bitrix\Pull\Event::add($arFields['TO_USER_ID'], $pullMessageTo);

					\CPushManager::DeleteFromQueueBySubTag($arFields['FROM_USER_ID'], 'IM_MESS');
				}
			}
			else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_CHAT || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN_LINE)
			{
				$chat = \Bitrix\Im\Model\ChatTable::getById($chatId);
				$chatData = $chat->fetch();

				foreach ($arRel as $relation)
				{
					if ($relation["EXTERNAL_AUTH_ID"] == \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
					{
						continue;
					}
					if ($chatData['ENTITY_TYPE'] == "LINES" && $relation["EXTERNAL_AUTH_ID"] == 'imconnector')
					{
						continue;
					}

					\CIMContactList::SetRecent(Array(
						'ENTITY_ID' => $relation['CHAT_ID'],
						'MESSAGE_ID' => $newRecord['ID'],
						'CHAT_TYPE' => $relation['MESSAGE_TYPE'],
						'USER_ID' => $relation['USER_ID'],
						'CHAT_ID' => $relation['CHAT_ID'],
						'RELATION_ID' => $relation['ID'],
					));
				}

				if (\CModule::IncludeModule('pull'))
				{
					$pullMessage = Array(
						'module_id' => 'im',
						'command' => 'messageChat',
						'params' => \CIMMessage::GetFormatMessage(Array(
							'ID' => $newRecord['ID'],
							'CHAT_ID' => $chatId,
							'TO_CHAT_ID' => $chatId,
							'FROM_USER_ID' => $newRecord['AUTHOR_ID'],
							'MESSAGE' => $newRecord['MESSAGE'],
							'SYSTEM' => $newRecord['AUTHOR_ID'] > 0? 'N': 'Y',
							'DATE_CREATE' => time(),
							'PARAMS' => $arFields['PARAMS'],
							'FILES' => $arFields['FILES'],
							'NOTIFY' => true
						)),
						'extra' => \Bitrix\Im\Common::getPullExtra()
					);

					if ($chatData && \CPullOptions::GetPushStatus())
					{
						$pushParams = $pullMessage;
						$pushParams['params']['message']['text_push'] = $newRecord['MESSAGE'];
						$pushParams = \CIMMessenger::PreparePushForChat($pushParams);
						$pullMessage = array_merge($pullMessage, $pushParams);
					}

					$pullUsers = Array();
					$pullUsersSkip = Array();
					foreach ($arRel as $rel)
					{
						if ($chatData['ENTITY_TYPE'] == "LINES" && $rel["EXTERNAL_AUTH_ID"] == 'imconnector')
						{
						}
						if ($rel['USER_ID'] == $newRecord['AUTHOR_ID'])
						{
							$pullUsers[] = $rel['USER_ID'];
							$pullUsersSkip[] = $rel['USER_ID'];
							\CPushManager::DeleteFromQueueBySubTag($newRecord['AUTHOR_ID'], 'IM_MESS');
						}
						else
						{
							$pullUsers[] = $rel['USER_ID'];
							if ($rel['NOTIFY_BLOCK'] == 'Y' || !\CIMSettings::GetNotifyAccess($rel['USER_ID'], 'im', ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN? 'openChat': 'chat'), \CIMSettings::CLIENT_PUSH))
							{
								$pullUsersSkip[] = $rel['USER_ID'];
							}
						}
					}
					$pullMessage['push']['skip_users'] = $pullUsersSkip;

					\Bitrix\Pull\Event::add($pullUsers, $pullMessage);

					if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN  || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN_LINE)
					{
						\CPullWatch::AddToStack('IM_PUBLIC_'.$chatId, $pullMessage);
					}

					/*
					\CIMMessenger::SendMention(Array(
						'CHAT_ID' => $chatId,
						'CHAT_TITLE' => $chatData['TITLE'],
						'CHAT_RELATION' => $arRel,
						'CHAT_TYPE' => $chatData['TYPE'],
						'MESSAGE' => $newRecord['MESSAGE'],
						'FILES' => $arFields['FILES'],
						'FROM_USER_ID' => $newRecord['AUTHOR_ID'],
					));
					*/

					foreach(\GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
						\ExecuteModuleEventEx($arEvent, array($newRecord['ID'], $newRecord));
				}
			}
		}

		/**
		 * Method will be invoked after an database record updated.
		 *
		 * @param array $oldRecord All fields before update.
		 * @param array $newRecord All fields after update.
		 *
		 * @return void
		 */
		public function afterUpdateTrigger(array $oldRecord, array $newRecord)
		{
			if (!\Bitrix\Main\Loader::includeModule('pull'))
				return;

			if ($oldRecord["MESSAGE"] == self::LOADER_PLACEHOLDER && $newRecord["MESSAGE"] == "")
				return;

			$arFields = \CIMMessenger::GetById($newRecord['ID'], Array('WITH_FILES' => 'Y'));
			if (!$arFields)
				return;

			$relations = \CIMChat::GetRelationById($arFields['CHAT_ID']);

			$arPullMessage = Array(
				'id' => $arFields['ID'],
				'type' => $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? 'private': 'chat',
				'text' => \Bitrix\Im\Text::parse($arFields['MESSAGE']),
				'date' => \Bitrix\Main\Type\DateTime::createFromTimestamp($arFields['DATE_CREATE']),
			);
			if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
			{
				$arFields['FROM_USER_ID'] = $arFields['AUTHOR_ID'];
				foreach ($relations as $rel)
				{
					if ($rel['USER_ID'] != $arFields['AUTHOR_ID'])
						$arFields['TO_USER_ID'] = $rel['USER_ID'];
				}

				$arPullMessage['fromUserId'] = $arFields['FROM_USER_ID'];
				$arPullMessage['toUserId'] = $arFields['TO_USER_ID'];
			}
			else
			{
				$arPullMessage['chatId'] = $arFields['CHAT_ID'];
				$arPullMessage['senderId'] = $arFields['AUTHOR_ID'];

				if ($arFields['CHAT_ENTITY_TYPE'] == 'LINES')
				{
					foreach ($relations as $rel)
					{
						if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
						{
							unset($relations[$rel["USER_ID"]]);
						}
					}
				}
			}

			\Bitrix\Pull\Event::add(array_keys($relations), $p=Array(
				'module_id' => 'im',
				'command' => $arFields['PARAMS']['IS_DELETED']==='Y'? 'messageDelete': 'messageUpdate',
				'params' => $arPullMessage,
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
			foreach ($relations as $rel)
			{
				$obCache = new \CPHPCache();
				$obCache->CleanDir('/bx/imc/recent'.\CIMMessenger::GetCachePath($rel['USER_ID']));
			}
			if ($newRecord['MESSAGE_TYPE'] == IM_MESSAGE_OPEN || $newRecord['MESSAGE_TYPE'] == IM_MESSAGE_OPEN_LINE)
			{
				\CPullWatch::AddToStack('IM_PUBLIC_'.$arFields['CHAT_ID'], Array(
					'module_id' => 'im',
					'command' => $arFields['PARAMS']['IS_DELETED']==='Y'? 'messageDelete': 'messageUpdate',
					'params' => $arPullMessage,
					'extra' => \Bitrix\Im\Common::getPullExtra()
				));
			}

			$updateFlags = Array(
				'ID' => $newRecord['ID'],
				'TEXT' => $newRecord["MESSAGE"],
				'URL_PREVIEW' => true,
				'EDIT_FLAG' => true,
				'USER_ID' => $arFields['AUTHOR_ID'],
				'BY_EVENT' => false,
			);

			foreach(\GetModuleEvents("im", "OnAfterMessagesUpdate", true) as $arEvent)
				\ExecuteModuleEventEx($arEvent, array(intval($newRecord['ID']), $arFields, $updateFlags));
		}

		/**
		 * Called before record transformed for log writing.
		 *
		 * @param array &$record Database record.
		 *
		 * @return void
		 */
		public function beforeLogFormat(array &$record)
		{
			global $USER;

			if (!$record['MESSAGE'])
			{
				$record['MESSAGE'] = Loc::getMessage('IM_REPLICA_FILE');
			}

			if (\Bitrix\Im\User::getInstance($record['AUTHOR_ID'])->isBot())
			{
				$record['MESSAGE'] = "[b]".\Bitrix\Im\User::getInstance($record['AUTHOR_ID'])->getFullName()."[/b] \n ".$record['MESSAGE'];
				$record['AUTHOR_ID'] = 0;
			}
		}
	}
}
