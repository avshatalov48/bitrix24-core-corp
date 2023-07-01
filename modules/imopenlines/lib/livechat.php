<?php

namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Disk\File;

Loc::loadMessages(__FILE__);

class LiveChat
{
	const MODULE_ID = 'imopenlines';
	const EXTERNAL_AUTH_ID = 'imconnector';

	private $config = null;
	private $error = null;
	private $sessionId = null;
	private $temporary = Array();
	private $userId = null;
	private $chat = null;

	public function __construct($config)
	{
		$this->config = $config;
		$this->error = new BasicError(null, '', '');
	}

	public function openSession()
	{
		\CUtil::decodeURIComponent($_GET);
		$context = Main\Application::getInstance()->getContext();
		$request = $context->getRequest();

		$sessionId = '';
		if ($request->get('userHash') && preg_match("/^[a-fA-F0-9]{32}$/i", $request->get('userHash')))
		{
			$sessionId = $request->get('userHash');
		}
		else
		{
			$sessionId = $request->getCookieRaw('LIVECHAT_HASH');
		}
		if (isset($_GET['userName']))
		{
			$this->temporary['USER_NAME'] = $_GET['userName'];
		}
		if (isset($_GET['userLastName']))
		{
			$this->temporary['USER_LAST_NAME'] = $_GET['userLastName'];
		}
		if (isset($_GET['userAvatar']))
		{
			$this->temporary['USER_AVATAR'] = $_GET['userAvatar'];
		}
		if (isset($_GET['userEmail']))
		{
			$this->temporary['USER_EMAIL'] = $_GET['userEmail'];
		}
		if (isset($_GET['currentUrl']) && !empty($_GET['currentUrl']))
		{
			$currentUrl = parse_url($_GET['currentUrl']);
			if ($currentUrl)
			{
				$this->temporary['USER_PERSONAL_WWW'] = $_GET['currentUrl'];
			}
		}

		if (isset($_GET['firstMessage']))
		{
			$this->temporary['FIRST_MESSAGE'] = $_GET['firstMessage'];
		}
		else if (isset($_GET['currentUrl']) && !empty($_GET['currentUrl']))
		{
			$currentUrl = parse_url($_GET['currentUrl']);
			if ($currentUrl)
			{
				$this->temporary['FIRST_MESSAGE'] = '[b]'.Loc::getMessage('IMOL_LC_GUEST_URL').'[/b]: [url='.$_GET['currentUrl'].']'.$currentUrl['scheme'].'://'.$currentUrl['host'].$currentUrl['path'].'[/url]';
			}
		}

		if (preg_match("/^[a-fA-F0-9]{32}$/i", $sessionId))
		{
			$this->sessionId = $sessionId;
		}
		else if ($_SESSION['LIVECHAT_HASH'])
		{
			$this->sessionId = $_SESSION['LIVECHAT_HASH'];
		}
		else
		{
			$licence = Main\Application::getInstance()->getLicense()->getPublicHashKey();

			$this->sessionId = md5(time().bitrix_sessid().$licence);
		}

		$_SESSION['LIVECHAT_HASH'] = $this->sessionId;
		setcookie('LIVECHAT_HASH', $this->sessionId, time() + 31536000, '/');

		$this->userId = $this->getGuestUser();

		global $USER;
		if (!$USER->IsAuthorized())
		{
			$USER->Authorize($this->userId, false, true, 'public');
		}
		$this->getChatForUser();

		return true;
	}

	private function getChatForUser()
	{
		$orm = \Bitrix\Im\Model\ChatTable::getList(array(
			'filter' => array(
				'=ENTITY_TYPE' => 'LIVECHAT',
				'=ENTITY_ID' => $this->config['ID'].'|'.$this->userId
			),
			'limit' => 1
		));
		if($chat = $orm->fetch())
		{
			if (isset($this->temporary['FIRST_MESSAGE']) && $chat['DESCRIPTION'] != $this->temporary['FIRST_MESSAGE'])
			{
				$chatManager = new \CIMChat(0);
				$chatManager->SetDescription($chat['ID'], $this->temporary['FIRST_MESSAGE']);
				$chat['DESCRIPTION'] = $this->temporary['FIRST_MESSAGE'];
			}
			$this->chat = $chat;

			$ar = \CIMChat::GetRelationById($this->chat['ID'], false, true, false);
			if (!isset($ar[$this->userId]))
			{
				$chatManager = new \CIMChat(0);
				$chatManager->AddUser($this->chat['ID'], $this->userId, false, true); // TODO security context
			}
			return $this->chat;
		}

		$avatarId = 0;
		$userName = '';
		$chatColorCode = '';
		$addChat['USERS'] = false;
		if ($this->userId)
		{
			$orm = \Bitrix\Main\UserTable::getById($this->userId);
			if ($user = $orm->fetch())
			{
				if ($user['PERSONAL_PHOTO'] > 0)
				{
					$avatarId = \CFile::CopyFile($user['PERSONAL_PHOTO']);
				}
				$addChat['USERS'] = Array($this->userId);

				$userName = \Bitrix\Im\User::getInstance($this->userId)->getFullName(false);
				$chatColorCode = \Bitrix\Im\Color::getCodeByNumber($this->userId);
				if (\Bitrix\Im\User::getInstance($this->userId)->getGender() == 'M')
				{
					$replaceColor = \Bitrix\Im\Color::getReplaceColors();
					if (isset($replaceColor[$chatColorCode]))
					{
						$chatColorCode = $replaceColor[$chatColorCode];
					}
				}
			}
		}

		if (!$userName)
		{
			$result = Chat::getGuestName();
			$userName = $result['USER_NAME'];
			$chatColorCode = $result['USER_COLOR'];
		}

		$addChat['TITLE'] = Loc::getMessage('IMOL_LC_CHAT_NAME', Array("#USER_NAME#" => $userName, "#LINE_NAME#" => $this->config['LINE_NAME']));

		$addChat['TYPE'] = IM_MESSAGE_CHAT;
		$addChat['COLOR'] = $chatColorCode;
		$addChat['AVATAR_ID'] = $avatarId;
		$addChat['ENTITY_TYPE'] = 'LIVECHAT';
		$addChat['ENTITY_ID'] = $this->config['ID'].'|'.$this->userId;
		$addChat['SKIP_ADD_MESSAGE'] = 'Y';
		$addChat['AUTHOR_ID'] = $this->userId;

		if (isset($this->temporary['FIRST_MESSAGE']))
		{
			$addChat['DESCRIPTION'] = $this->temporary['FIRST_MESSAGE'];
		}

		$chat = new \CIMChat(0);
		$id = $chat->Add($addChat);
		if (!$id)
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\ChatTable::getById($id);
		$this->chat = $orm->fetch();
		return $this->chat;
	}

	/**
	 * @param $messageId
	 * @param $messageFields
	 * @return bool
	 * @throws Main\NotImplementedException
	 */
	public static function onMessageSend($messageId, $messageFields)
	{
		$chatEntityType = $messageFields['CHAT_ENTITY_TYPE'] ?? null;
		if ($chatEntityType !== 'LIVECHAT')
		{
			return false;
		}

		$messageFields['MESSAGE_ID'] = $messageId;
		Log::write($messageFields, 'LIVECHAT MESSAGE SEND');

		if ($messageFields['SKIP_CONNECTOR'] == 'Y')
		{
			return false;
		}

		[$lineId, $userId] = explode("|", $messageFields['CHAT_ENTITY_ID']);

		$extraFields = Array();
		if ($messageFields['AUTHOR_ID'] > 0)
		{
			$user = \Bitrix\Im\User::getInstance($messageFields['AUTHOR_ID']);
			if ($userId == $messageFields['AUTHOR_ID'])
			{
				$extraFields['EXTRA_URL'] = $user->getWebsite();
			}
			else if (!$user->isConnector() && !$user->isBot())
			{
				return false;
			}
		}

		$chatId = $messageFields['TO_CHAT_ID'];

		if (
			trim($messageFields['MESSAGE']) == '' &&
			empty($messageFields["ATTACH"]) &&
			empty($messageFields["FILES"])
		)
		{
			return false;
		}

		$files = [];
		if(!empty($messageFields["FILES"]))
		{
			foreach ($messageFields["FILES"] as $field)
			{
				$files[] = File::getById($field['id'])->getFileId();
			}
		}

		$message = [
			'id' => $messageId,
			'date' => "",
			'text' => $messageFields['MESSAGE'],
			'files' => $files,
			'attach' => $messageFields['ATTACH'],
			'system' => $messageFields['SYSTEM'],
		];

		$event = new \Bitrix\Main\Event('imconnector', 'OnReceivedMessage', [
			'user' => $messageFields['CHAT_AUTHOR_ID'],
			'connector' => 'livechat',
			'line' => $lineId,
			'chat' => ['id' => $chatId],
			'message' => $message,
			'extra' => $extraFields
		]);
		$event->send();

		return true;
	}

	private function getGuestUser($userId = null)
	{
		$xmlId = $this->sessionId;

		if (isset($this->temporary['USER_NAME']) && $this->temporary['USER_NAME'])
		{
			$userName = $this->temporary['USER_NAME'];
			$userLastName = isset($this->temporary['USER_LAST_NAME'])? $this->temporary['USER_LAST_NAME']: '';
		}
		else
		{
			$userName = self::getDefaultGuestName();
			$userLastName = '';
		}
		$userEmail = isset($this->temporary['USER_EMAIL'])? $this->temporary['USER_EMAIL']: '';
		$userWebsite = isset($this->temporary['USER_PERSONAL_WWW'])? $this->temporary['USER_PERSONAL_WWW']: '';
		$userGender = '';
		$userAvatar = isset($this->temporary['USER_AVATAR'])? self::uploadAvatar($this->temporary['USER_AVATAR']): '';
		$userWorkPosition = '';

		if ($userId && \Bitrix\Im\User::getInstance($userId)->isExists())
		{
			if (\Bitrix\Im\User::getInstance($userId)->isConnector())
			{
				return $userId;
			}
			$userData = \Bitrix\Im\User::getInstance($userId);
			$xmlId = $userData->getId();
			$userName = $userData->getName(false);
			$userLastName = $userData->getLastName(false);
			$userGender = $userData->getGender();
			$userWebsite = $userData->getWebsite();
			$userWorkPosition = $userData->getWorkPosition();
			$userAvatar = $userData->getAvatarId();
			$userEmail = $userData->getEmail();
			if ($userAvatar)
			{
				$userAvatar = \CFile::MakeFileArray($userAvatar);
			}
		}

		global $USER;
		if ($USER->IsAuthorized())
		{
			$orm = \Bitrix\Main\UserTable::getList(array(
				'filter' => array('=ID' => $USER->GetId())
			));
		}
		else
		{
			$orm = \Bitrix\Main\UserTable::getList(array(
				'filter' => array(
					'=EXTERNAL_AUTH_ID' => self::EXTERNAL_AUTH_ID,
					'=XML_ID' => 'livechat|'.$xmlId
				),
				'limit' => 1
			));
		}

		if($userFields = $orm->fetch())
		{
			$userId = $userFields['ID'];
			if ($userFields['EXTERNAL_AUTH_ID'] == self::EXTERNAL_AUTH_ID)
			{
			$updateFields = Array();
			if ($userWebsite && $userWebsite != $userFields['PERSONAL_WWW'])
			{
				$updateFields['PERSONAL_WWW'] = $userWebsite;
			}

			if (!empty($updateFields))
			{
				$cUser = new \CUser;
				$cUser->Update($userId, $updateFields);}
			}
		}
		else
		{
			$cUser = new \CUser;
			$fields['LOGIN'] = self::MODULE_ID . '_' . rand(1000,9999) . randString(5);
			$fields['NAME'] = $userName;
			$fields['LAST_NAME'] = $userLastName;
			if ($userAvatar)
			{
				$fields['PERSONAL_PHOTO'] = $userAvatar;
			}
			if ($userEmail)
			{
				$fields['EMAIL'] = $userEmail;
			}
			if ($userWebsite)
			{
				$fields['PERSONAL_WWW'] = $userWebsite;
			}
			$fields['PERSONAL_GENDER'] = $userGender;
			$fields['WORK_POSITION'] = $userWorkPosition;
			$fields['PASSWORD'] = md5($fields['LOGIN'].'|'.rand(1000,9999).'|'.time());
			$fields['CONFIRM_PASSWORD'] = $fields['PASSWORD'];
			$fields['EXTERNAL_AUTH_ID'] = self::EXTERNAL_AUTH_ID;
			$fields['XML_ID'] =  'livechat|'.$xmlId;
			$fields['ACTIVE'] = 'Y';

			$userId = $cUser->Add($fields);
		}

		return $userId;
	}

	public static function getDefaultGuestName()
	{
		return Loc::getMessage('IMOL_LC_GUEST_NAME');
	}

	public static function getLocalize($lang = null, $withTagScript = true)
	{
		$messages = \Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/imopenlines/js_livechat.php', $lang);
		$text = 'BX.LiveChatMessage.add('.\CUtil::PhpToJSObject($messages).');';

		if ($withTagScript)
		{
			$text = '<script type="text/javascript">'.$text.'</script>';
		}
		return $text;
	}

	public static function uploadAvatar($avatarUrl = '')
	{
		if (!$avatarUrl)
			return '';

		if (!in_array(mb_strtolower(\GetFileExtension($avatarUrl)), Array('png', 'jpg', 'jpeg')))
			return '';

		$recordFile = \CFile::MakeFileArray($avatarUrl);
		if (!\CFile::IsImage($recordFile['name'], $recordFile['type']))
			return '';

		if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0 && $recordFile['size'] < 1000000)
		{
			$recordFile = array_merge($recordFile, array('MODULE_ID' => 'imopenlines'));
		}
		else
		{
			$recordFile = '';
		}

		return $recordFile;
	}

	public function getChat()
	{
		return $this->chat;
	}

	public function getError()
	{
		return $this->error;
	}
}