<?php
namespace Bitrix\Im\Replica;

use Bitrix\Main\Loader;

if (Loader::includeModule('replica'))
{
	class StartWritingHandler extends \Bitrix\Replica\Client\BaseHandler
	{
		protected $moduleId = "im";

		public function initDataManagerEvents()
		{
			\Bitrix\Main\EventManager::getInstance()->addEventHandler(
				"im",
				"OnStartWriting",
				array($this, "OnStartWriting")
			);
			\Bitrix\Main\EventManager::getInstance()->addEventHandler(
				"replica",
				"OnExecuteStartWriting",
				array($this, "OnExecuteStartWriting")
			);
		}

		function onStartWriting($params)
		{
			$userId = intval($params['USER_ID']);
			if ($userId <= 0)
			{
				return false;
			}

			if (\Bitrix\Im\User::getInstance($userId)->isBot())
			{
				return true;
			}

			$dialogId = $params['DIALOG_ID'];

			$operation = new \Bitrix\Replica\Db\Execute();
			if (mb_substr($dialogId, 0, 4) === "chat")
			{
				$chatId = intval(mb_substr($dialogId, 4));
				$operation->writeToLog(
					"StartWriting",
					array(
						array(
							"relation" => "b_user.ID",
							"value" => $userId,
						),
						array(
							"value" => "chat",
						),
						array(
							"relation" => "b_im_chat.ID",
							"value" => $chatId,
						),
					)
				);
			}
			else
			{
				$dialogId = intval($dialogId);
				$operation->writeToLog(
					"StartWriting",
					array(
						array(
							"relation" => "b_user.ID",
							"value" => $userId,
						),
						array(
							"value" => "",
						),
						array(
							"relation" => "b_user.ID",
							"value" => $dialogId,
						),
					)
				);
			}

			return true;
		}

		function onExecuteStartWriting(\Bitrix\Main\Event $event)
		{
			$parameters = $event->getParameters();
			$userId = intval($parameters[0]);
			$dialogId = $parameters[1].$parameters[2];

			if ($userId > 0)
			{
				if (!\Bitrix\Main\Loader::includeModule('pull'))
					return;

				$userName = \Bitrix\Im\User::getInstance($userId)->getFullName();


				if (mb_substr($dialogId, 0, 4) == 'chat')
				{
					$chatId = mb_substr($dialogId, 4);
					$arRelation = \CIMChat::GetRelationById($chatId);
					unset($arRelation[$userId]);

					$chat = \Bitrix\Im\Model\ChatTable::getById($chatId);
					$chatData = $chat->fetch();

					$pullMessage = Array(
						'module_id' => 'im',
						'command' => 'startWriting',
						'expiry' => 60,
						'params' => Array(
							'dialogId' => $dialogId,
							'userId' => $userId,
							'userName' => $userName
						),
						'extra' => \Bitrix\Im\Common::getPullExtra()
					);
					if ($chatData['ENTITY_TYPE'] == 'LINES')
					{
						foreach ($arRelation as $rel)
						{
							if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
							{
								unset($arRelation[$rel["USER_ID"]]);
							}
						}
					}
					\Bitrix\Pull\Event::add(array_keys($arRelation), $pullMessage);

					$orm = \Bitrix\Im\Model\ChatTable::getById($chatId);
					$chat = $orm->fetch();
					if ($chat['TYPE'] == IM_MESSAGE_OPEN || $chat['TYPE'] == IM_MESSAGE_OPEN_LINE)
					{
						\CPullWatch::AddToStack('IM_PUBLIC_'.$chatId, $pullMessage);
					}
				}
				else if (intval($dialogId) > 0)
				{
					\Bitrix\Pull\Event::add($dialogId, Array(
						'module_id' => 'im',
						'command' => 'startWriting',
						'expiry' => 60,
						'params' => Array(
							'dialogId' => $userId,
							'userId' => $userId,
							'userName' => $userName
						),
						'extra' => \Bitrix\Im\Common::getPullExtra()
					));
				}
			}
		}
	}
}
