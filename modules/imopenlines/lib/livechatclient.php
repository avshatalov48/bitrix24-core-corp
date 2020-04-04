<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\ImOpenlines\Security\Helper;

Loc::loadMessages(__FILE__);

class LiveChatClient
{
	const FORM_OFFLINE = 'OFFLINE';
	const FORM_WELCOME = 'WELCOME';
	const FORM_HISTORY = 'HISTORY';
	
	private $chatId = 0;
	private $userId = 0;
	private $error = null;
	private $moduleLoad = false;

	public function __construct($chatId, $userId)
	{
		$imLoad = \Bitrix\Main\Loader::includeModule('im');
		$pullLoad = \Bitrix\Main\Loader::includeModule('pull');
		if ($imLoad && $pullLoad)
		{
			$this->error = new Error(null, '', '');
			$this->moduleLoad = true;
		}
		else
		{
			if (!$imLoad)
			{
				$this->error = new Error(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_LCC_ERROR_IM_LOAD'));
			}
			elseif (!$pullLoad)
			{
				$this->error = new Error(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_LCC_ERROR_PULL_LOAD'));
			}
		}

		$this->chatId = intval($chatId);
		$this->userId = intval($userId);
	}

	private function checkAccess()
	{
		if (!$this->moduleLoad)
		{
			return Array(
				'RESULT' => false
			);
		}

		if ($this->chatId <= 0)
		{
			$this->error = new Error(__METHOD__, 'CHAT_ID', Loc::getMessage('IMOL_LCC_ERROR_CHAT_ID'));

			return Array(
				'RESULT' => false
			);
		}
		if ($this->userId <= 0)
		{
			$this->error = new Error(__METHOD__, 'USER_ID', Loc::getMessage('IMOL_LCC_ERROR_USER_ID'));

			return Array(
				'RESULT' => false
			);
		}

		$orm = \Bitrix\Im\Model\RelationTable::getList(array(
			"select" => array("ID", "ENTITY_TYPE" => "CHAT.ENTITY_TYPE"),
			"filter" => array(
				"=CHAT_ID" => $this->chatId,
				"=USER_ID" => $this->userId,
			),
		));
		if ($relation = $orm->fetch())
		{
			if ($relation["ENTITY_TYPE"] != "LIVECHAT")
			{
				$this->error = new Error(__METHOD__, 'CHAT_TYPE', Loc::getMessage('IMOL_LCC_ERROR_CHAT_TYPE'));

				return Array(
					'RESULT' => false
				);
			}
		}
		else
		{
			$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_LCC_ERROR_ACCESS_DENIED'));

			return Array(
				'RESULT' => false
			);
		}

		return Array(
			'RESULT' => true
		);
	}

	public function saveForm($type, $fields)
	{
		if (!in_array($type, Array(self::FORM_OFFLINE, self::FORM_WELCOME, self::FORM_HISTORY)))
		{
			$this->error = new Error(__METHOD__, 'FORM_ID', Loc::getMessage('IMOL_LCC_ERROR_FORM_ID'));
			return false;
		}
		
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}
		
		Log::write(Array(
			'FORM' => Array($type, $fields)
		), 'CLIENT FORM CRM');
		
		$chat = new Chat($this->chatId);
		list($configId) = explode('|', $chat->getData('ENTITY_ID'));

		$result = $chat->load(Array(
			'USER_CODE' => 'livechat|'.$configId.'|'.$this->chatId.'|'.$this->userId,
		));
		if (!$result)
		{
			$this->error = new Error(__METHOD__, 'FORM_ID', Loc::getMessage('IMOL_LCC_ERROR_ACCESS_DENIED'));
			return false;
		}
		
		$configManager = new \Bitrix\ImOpenLines\Config();
		$config = $configManager->get($configId);
		
		if (isset($fields['EMAIL']) && !preg_match("/^(.*)@(.*)\.[a-zA-Z]{2,}$/", $fields['EMAIL']))
		{
			unset($fields['EMAIL']);
		}
		if (isset($fields['PHONE']) && empty($fields['PHONE']))
		{
			unset($fields['PHONE']);
		}
		if (isset($fields['NAME']) && empty($fields['NAME']))
		{
			$fields['NAME'] = Loc::getMessage('IMOL_LCC_GUEST_NAME');
		}
		
		$user = \Bitrix\Im\User::getInstance($this->userId);
		
		$userUpdate = Array();
		$chatUpdate = Array();
		$messageParams = Array();
		
		$formSend = false;
		
		if ($type == self::FORM_WELCOME || $type == self::FORM_OFFLINE)
		{
			if (isset($fields['NAME']))
			{
				list($userName, $userLastName) = explode(" ", $fields['NAME'], 2);
				if ($userName && $userLastName)
				{
					if ($userName != $user->getName() || $userLastName != $user->getLastName())
					{
						$userUpdate['NAME'] = $userName;
						$userUpdate['LAST_NAME'] = $userLastName;
					}
				}
				else if ($user->getLastName() != $userName)
				{
					$userUpdate['LAST_NAME'] = $userName;
				}
			}
			if (isset($fields['EMAIL']) && $user->getEmail() != $fields['EMAIL'])
			{
				$userUpdate['EMAIL'] = $fields['EMAIL'];
			}
			if (isset($fields['PHONE']) && $user->getPhone(\Bitrix\Im\User::PHONE_MOBILE) != $fields['PHONE'])
			{
				$userUpdate['PERSONAL_MOBILE'] = $fields['PHONE'];
			}
			
			$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			$attach->AddGrid(Array(
				Array(
					"NAME" => Loc::getMessage('IMOL_LCC_FORM_NAME'),
					"VALUE" => isset($fields['NAME'])? $fields['NAME']: Loc::getMessage('IMOL_LCC_FORM_NONE'),
					"DISPLAY" => "COLUMN"
				),
				Array(
					"NAME" => Loc::getMessage('IMOL_LCC_FORM_EMAIL'),
					"VALUE" => isset($fields['EMAIL'])? $fields['EMAIL']: Loc::getMessage('IMOL_LCC_FORM_NONE'),
					"DISPLAY" => "COLUMN"
				),
				Array(
					"NAME" => Loc::getMessage('IMOL_LCC_FORM_PHONE'),
					"VALUE" => isset($fields['PHONE'])? $fields['PHONE']: Loc::getMessage('IMOL_LCC_FORM_NONE'),
					"DISPLAY" => "COLUMN"
				),
			));
			
			if (!empty($userUpdate))
			{
				$messageParams = Array(
					"FROM_USER_ID" => $this->userId,
					"MESSAGE" => '[B]'.Loc::getMessage('IMOL_LCC_FORM_SUBMIT').'[/B]',
					"ATTACH" => $attach,
					"SKIP_CONNECTOR" => 'Y',
					"PARAMS" => Array(
						"CLASS" => "bx-messenger-content-item-system"
					),
				);
			}
			
			$formSend = true;
		}
		else if ($type == self::FORM_HISTORY)
		{
			$userUpdate = Array();
			if (isset($fields['EMAIL']) && !$user->getEmail())
			{
				$userUpdate['EMAIL'] = $fields['EMAIL'];
			}
			
			$liveChat = new Chat($this->chatId);
			$chatFieldSession = $liveChat->getFieldData(Chat::FIELD_LIVECHAT);
			
			if (isset($fields['EMAIL']) && $chatFieldSession['SESSION_ID'])
			{
				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
				$attach->AddGrid(Array(
					Array(
						"NAME" => Loc::getMessage('IMOL_LCC_FORM_EMAIL'),
						"VALUE" => $fields['EMAIL'],
					),
				));
				$messageParams = Array(
					"FROM_USER_ID" => $this->userId,
					"MESSAGE" => '[B]'.Loc::getMessage('IMOL_LCC_FORM_HISTORY_2', Array("#LINK#" => "[URL=/online/?IM_HISTORY=imol|".$chatFieldSession['SESSION_ID']."]".$chatFieldSession['SESSION_ID']."[/URL]")).'[/B]',
					"ATTACH" => $attach,
					"SKIP_CONNECTOR" => 'Y',
					"SYSTEM" => 'Y',
				);
				
				\Bitrix\ImOpenLines\Mail::sendSessionHistory($chatFieldSession['SESSION_ID'], $fields['EMAIL']);
			}
			$liveChat->updateFieldData(Chat::FIELD_LIVECHAT, Array( 
				'SESSION_ID' => 0,
				'SHOW_FORM' => 'N'
			));
		}
		
		// update user entity
		if (!empty($userUpdate))
		{
			$userClass = new \CUser();
			$userClass->Update($this->userId, $userUpdate);
			\Bitrix\Im\User::clearStaticCache();
		}
		
		if (isset($userUpdate['NAME']) || isset($userUpdate['LAST_NAME']))
		{
			$titleParams = $chat->getTitle($config['LINE_NAME'], trim($userUpdate['NAME'].' '.$userUpdate['LAST_NAME']));
			$chatUpdate['TITLE'] = $titleParams['TITLE'];
		}
		
		// update chat entity
		if (!empty($chatUpdate))
		{
			$chat->update($chatUpdate);
		}
		
		// publish info message to chat
		$session = false;
		$sessionStart = false;
		
		if (!empty($messageParams))
		{
			if (!$session)
			{
				$session = new Session();
				$sessionStart = $session->load(Array(
					'USER_CODE' => $chat->getData('ENTITY_ID'),
				));
			}
			$messageParams['TO_CHAT_ID'] = $chat->getData('ID');
			
			$userViewChat = \CIMContactList::InRecent($session->getData('OPERATOR_ID'), IM_MESSAGE_CHAT, $session->getData('CHAT_ID'));
			$messageParams['RECENT_ADD'] = $userViewChat? 'Y': 'N';
			
			\CIMChat::AddMessage($messageParams);
		}

		// update crm entity
		
		if (
			$type == self::FORM_HISTORY 
			&& empty($userUpdate) 
			&& isset($fields['EMAIL']) && $fields['EMAIL'] != $user->getEmail()
		)
		{
			if (!$session)
			{
				$session = new Session();
				$sessionStart = $session->load(Array(
					'USER_CODE' => $chat->getData('ENTITY_ID'),
				));
			}
			if ($sessionStart)
			{
				$tracker = new \Bitrix\ImOpenLines\Tracker();
				$tracker->message(Array(
					'SESSION' => $session,
					'MESSAGE' => Array(
						'TEXT' => $fields['EMAIL']
					),
				));
			}
		}
		else if (!empty($userUpdate))
		{
			if ($config['CRM'] == 'Y' && \IsModuleInstalled('crm'))
			{
				if (!$session)
				{
					$session = new Session();
					$sessionStart = $session->load(Array(
						'USER_CODE' => $chat->getData('ENTITY_ID'),
					));
				}
				if ($sessionStart)
				{
					$params = $session->getData();
					$crmData = $session->updateCrm(array(
						'CONFIG_ID' => $params['CONFIG_ID'],
						'SESSION_ID' => $params['ID'],
						'MODE' => $params['MODE'],
						'USER_CODE' => $params['USER_CODE'],
						'USER_ID' => $params['USER_ID'],
						'CRM_TITLE' => $chat->getData('TITLE'),
						'OPERATOR_ID' => $params['OPERATOR_ID'],
						'CHAT_ID' => $params['CHAT_ID']
					));
					if ($crmData['CRM'] == 'Y')
					{
						$session->update(Array(
							'CRM' => 'Y',
							'CRM_CREATE' => 'Y',
							'CRM_ENTITY_TYPE' => $crmData['CRM_ENTITY_TYPE'],
							'CRM_ENTITY_ID' => $crmData['CRM_ENTITY_ID'],
							'CRM_ACTIVITY_ID' => $crmData['CRM_ACTIVITY_ID'],
						));
				
						$chat->updateFieldData(Chat::FIELD_SESSION, Array(
							'CRM' => 'Y',
							'CRM_ENTITY_TYPE' => $crmData['CRM_ENTITY_TYPE'],
							'CRM_ENTITY_ID' => $crmData['CRM_ENTITY_ID'],
						));
					}
				}
			}
		}
		
		if ($formSend)
		{
			if (!$session)
			{
				$session = new Session();
				$sessionStart = $session->load(Array(
					'USER_CODE' => $chat->getData('ENTITY_ID'),
				));
			}
			if ($sessionStart)
			{
				$session->update(Array(
					'SEND_FORM' => strtolower($type)
				));
			}
		}
		
		return true;
	}
	
	public function getError()
	{
		return $this->error;
	}
}