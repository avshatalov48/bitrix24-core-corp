<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/classes/interface.php");

class CXMPPFactory
{
	private $classesDir = "/bitrix/modules/xmpp/classes/factory_classes";
	private $arReceiveClasses = array();
	private $arProcessServerClasses = array();
	private $arClearCacheClasses = array();
	private $isInitialized = false;

	public function GetFactory()
	{
		if (!array_key_exists("xmppFactoryObject", $GLOBALS) || !is_object($GLOBALS["xmppFactoryObject"]))
		{
			$GLOBALS["xmppFactoryObject"] = new CXMPPFactory();
			$GLOBALS["xmppFactoryObject"]->Initialize();
		}
		return $GLOBALS["xmppFactoryObject"];
	}

	private function Initialize()
	{
		if ($this->isInitialized)
			return;

		CModule::IncludeModule('socialnetwork');
		CModule::IncludeModule('iblock');

		$this->arReceiveClasses = array();
		$this->arProcessServerClasses = array();
		$this->arClearCacheClasses = array();

		if ($handle = @opendir($_SERVER["DOCUMENT_ROOT"].$this->classesDir))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..")
					continue;

				if (!is_file($_SERVER["DOCUMENT_ROOT"].$this->classesDir."/".$file))
					continue;

				$className = "";
				$classVersion = 1;
				include($_SERVER["DOCUMENT_ROOT"].$this->classesDir."/".$file);

				if (strlen($className) <= 0)
					continue;

				$c = new $className();
				$c->Initialize();

				if (is_subclass_of($c, "CXMPPFactoryHandler"))
				{
					if ($c instanceof IXMPPFactoryHandler)
						$this->arReceiveClasses[] = $c;
					if ($c instanceof IXMPPFactoryServerHandler)
						$this->arProcessServerClasses[] = $c;
					if ($c instanceof IXMPPFactoryCleanableHandler)
						$this->arClearCacheClasses[] = $c;
				}
				else
				{
					if (method_exists($c, "ReceiveMessage"))
						$this->arReceiveClasses[] = $c;
					if (method_exists($c, "ProcessServerMessage"))
						$this->arProcessServerClasses[] = $c;
					if (method_exists($c, "ClearCaches"))
						$this->arClearCacheClasses[] = $c;
				}
			}

			closedir($handle);
		}

		$cnt = count($this->arReceiveClasses);
		for ($i = 0; $i < $cnt - 1; $i++)
		{
			for ($j = $i + 1; $j < $cnt; $j++)
			{
				$ix1 = (is_subclass_of($this->arReceiveClasses[$i], "CXMPPFactoryHandler") ? $this->arReceiveClasses[$i]->GetIndex() : $this->arReceiveClasses[$i]->receiveMessageIndex);
				$ix2 = (is_subclass_of($this->arReceiveClasses[$j], "CXMPPFactoryHandler") ? $this->arReceiveClasses[$j]->GetIndex() : $this->arReceiveClasses[$j]->receiveMessageIndex);
				if ($ix1 > $ix2)
				{
					$t = $this->arReceiveClasses[$i];
					$this->arReceiveClasses[$i] = $this->arReceiveClasses[$j];
					$this->arReceiveClasses[$j] = $t;
				}
			}
		}

		$cnt = count($this->arProcessServerClasses);
		for ($i = 0; $i < $cnt - 1; $i++)
		{
			for ($j = $i + 1; $j < $cnt; $j++)
			{
				$ix1 = (is_subclass_of($this->arProcessServerClasses[$i], "CXMPPFactoryHandler") ? $this->arProcessServerClasses[$i]->GetServerIndex() : $this->arProcessServerClasses[$i]->processServerMessageIndex);
				$ix2 = (is_subclass_of($this->arProcessServerClasses[$j], "CXMPPFactoryHandler") ? $this->arProcessServerClasses[$j]->GetServerIndex() : $this->arProcessServerClasses[$j]->processServerMessageIndex);
				if ($ix1 > $ix2)
				{
					$t = $this->arProcessServerClasses[$i];
					$this->arProcessServerClasses[$i] = $this->arProcessServerClasses[$j];
					$this->arProcessServerClasses[$j] = $t;
				}
			}
		}

		$this->isInitialized = true;
	}

	public function ReceiveMessage($senderJId, $arMessage, &$arClientAuth)
	{
		if (!$this->isInitialized)
			$this->Initialize();

		$receiveResult = false;
		foreach ($this->arReceiveClasses as $receiveClass)
		{
			if ($receiveResult = $receiveClass->ReceiveMessage($senderJId, $arMessage, $arClientAuth))
				break;
		}

		return $receiveResult;
	}

	public function ProcessServerMessage($arMessage, $clientDomain = "")
	{
		if (!$this->isInitialized)
			$this->Initialize();

		$processResult = false;
		foreach ($this->arProcessServerClasses as $processClass)
		{
			if ($processResult = $processClass->ProcessServerMessage($arMessage, $clientDomain))
				break;
		}

		return $processResult;
	}

	public function ClearCaches()
	{
		if (!$this->isInitialized)
			$this->Initialize();

		foreach ($this->arClearCacheClasses as $clearClass)
			$clearClass->ClearCaches();
	}

	private static function GetSystemJId($domain = "")
	{
		static $arSoNetUIdCache = array();
		static $arSoNetSenderTypeCache = array();
		static $arSoNetJIdCache = array();

		CXMPPUtility::ClearOptionsCache("xmpp");

		if (!array_key_exists($domain, $arSoNetSenderTypeCache))
			$arSoNetSenderTypeCache[$domain] = COption::GetOptionString("xmpp", "sonet_sender_type", "jid");

		if ($arSoNetSenderTypeCache[$domain] == "uid")
		{
			if (!array_key_exists($domain, $arSoNetUIdCache))
				$arSoNetUIdCache[$domain] = COption::GetOptionString("xmpp", "sonet_uid", false);

			$senderId = intval($arSoNetUIdCache[$domain]);
			if ($senderId <= 0)
			{
				CXMPPUtility::Show("Wrong system jid arSoNetUIdCache[".$domain."]=".$arSoNetUIdCache[$domain], 1);
				return 0;
			}

			$senderJId = CXMPPUtility::GetJIdByUserId($senderId, $domain);
			if (!$senderJId)
			{
				CXMPPUtility::Show("Wrong system jid arSoNetUIdCache[".$domain."]=".$arSoNetUIdCache[$domain], 1);
				return 0;
			}
		}
		else
		{
			if (!array_key_exists($domain, $arSoNetJIdCache))
				$arSoNetJIdCache[$domain] = COption::GetOptionString("xmpp", "sonet_jid", "admin@".$_SERVER["SERVER_NAME"]);

			$senderJId = $arSoNetJIdCache[$domain];
		}

		return $senderJId;
	}

	private function __SendMessage($senderId, $receiverId, $messageID, $type, $message, $domain = "")
	{
		if (!$this->isInitialized)
			$this->Initialize();

		$receiverId = intval($receiverId);
		if ($receiverId <= 0)
			return false;
		$messageID = intval($messageID);
		if ($messageID <= 0)
			return false;
		if (!in_array($type, array("P", "S")))
			return false;
		if (strlen($message) <= 0)
			return false;

		if (!CXMPPUtility::CheckXmppStatusOnline())
			return false;

		$senderJId = false;
		$receiverJId = false;

		$arUserID = array();
		$arUserID[] = $receiverId;

		$senderId = intval($senderId);
		if ($senderId <= 0)
		{
			if ($senderId != -5)
				return false;

			$senderJId = self::GetSystemJId($domain);
		}
		else
		{
			$arUserID[] = $senderId;
		}

		if(!$arJID = CXMPPUtility::GetJIdByUserId($arUserID, $domain))
			return false;

		if($senderId > 0)
			$senderJId = $arJID[$senderId];

		$receiverJId = $arJID[$receiverId];

		if (!$senderJId)
		{
			CXMPPUtility::Show("Error senderId=".$senderId.", receiverId=".$receiverId.", messageID=".$messageID." - Sender not found", 0);
			return false;
		}

		if (!$receiverJId)
		{
			CXMPPUtility::Show("Error senderId=".$senderId.", receiverId=".$receiverId.", messageID=".$messageID." - Reseiver not found", 0);
			return false;
		}

		$messageType = "chat";
		if ($type == "S")
			$messageType = "normal";

		$arMessage = CXMPPUtility::GetMessageArray($senderJId, $receiverJId, $messageType, $message, $domain);

		$result = false;

		if (CXMPPServer::IsServerStarted())
		{
			$server = CXMPPServer::GetServer();
			$result = $server->Send($receiverJId, $arMessage, $domain);
		}
		else
		{
			$result = CXMPPUtility::SendToServer($arMessage, $domain);
		}

		if ($result === true)
		{
			if (IsModuleInstalled("im") && CModule::IncludeModule("im"))
			{
				if ($type == "S")
				{
					$CIMNotify = new CIMNotify($receiverId);
					$CIMNotify->MarkNotifyRead($messageID);
				}
				else
				{
					$CIMMessage = new CIMMessage($receiverId);
					$CIMMessage->SetReadMessage($senderId);
				}
			}
			else
			{
				CSocNetMessages::MarkMessageRead($receiverId, $messageID);
			}
		}
		else
			CXMPPUtility::Show("Error senderId=".$senderId.", receiverId=".$receiverId.", messageID=".$messageID." - Message was not send", 0);

		return $result;
	}

	public function OnSocNetMessagesAdd($ID, $arFields)
	{
		/*if (array_key_exists("IS_LOG", $arFields) && $arFields["IS_LOG"] == "Y")
			$bSystem = true;
		else
			$bSystem = false;*/

		$domain = CXMPPServer::GetDomain();
		if (CXMPPUtility::IsBitrix24Mode())
			$domain = $_SERVER["HTTP_HOST"];

		$factory = CXMPPFactory::GetFactory();

		if (IsModuleInstalled("im") && CModule::IncludeModule("im"))
		{
			if (isset($arFields["NOTIFY_MODULE"]) && isset($arFields["NOTIFY_EVENT"]) && $arFields["MESSAGE_TYPE"] == IM_MESSAGE_SYSTEM
			&& !CIMSettings::GetNotifyAccess($arFields["TO_USER_ID"], $arFields["NOTIFY_MODULE"], $arFields["NOTIFY_EVENT"], CIMSettings::CLIENT_XMPP))
				return false;

			if ($arFields["MESSAGE_OUT"] == IM_MAIL_SKIP)
				$arFields["MESSAGE_OUT"] = '';

			return $factory->__SendMessage(
				((array_key_exists("IS_LOG", $arFields) && $arFields["IS_LOG"] == "Y") ? -5 : $arFields["FROM_USER_ID"]),
				$arFields["TO_USER_ID"],
				$ID,
				$arFields["MESSAGE_TYPE"],
				htmlspecialcharsbx(CTextParser::convert4mail(str_replace("#BR#", "\n", (strlen($arFields["MESSAGE_OUT"])>0? $arFields["MESSAGE_OUT"]: $arFields["MESSAGE"])))),
				$domain
			);
		}
		else
		{
			$parser = new CSocNetTextParser();
			return $factory->__SendMessage(
				((array_key_exists("IS_LOG", $arFields) && $arFields["IS_LOG"] == "Y") ? -5 : $arFields["FROM_USER_ID"]),
				$arFields["TO_USER_ID"],
				$ID,
				$arFields["MESSAGE_TYPE"],
				htmlspecialcharsbx($parser->convert4mail(str_replace("#BR#", "\n", $arFields["MESSAGE"]))),
				$domain
			);
		}
	}

	public function OnImMessagesUpdate($ID, $arFields)
	{
		if (!CModule::IncludeModule("im"))
			return false;

		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$domain = CXMPPServer::GetDomain();
			if (CXMPPUtility::IsBitrix24Mode())
				$domain = $_SERVER["HTTP_HOST"];

			$factory = CXMPPFactory::GetFactory();

			return $factory->__SendMessage(
				$arFields["FROM_USER_ID"],
				$arFields["TO_USER_ID"],
				$ID,
				$arFields["MESSAGE_TYPE"],
				htmlspecialcharsbx(CTextParser::convert4mail(str_replace("#BR#", "\n", ($arFields["MESSAGE"])))),
				$domain
			);
		}

		return true;
	}

	public function OnImFileUpload($arFields)
	{
		if (!CModule::IncludeModule("im"))
			return false;

		$chatId = $arFields['CHAT_ID'];

		$arChat = CIMChat::GetChatData(Array('ID' => $chatId));
		if ($arChat['chat'][$chatId]['messageType'] == IM_MESSAGE_PRIVATE)
		{
			$arFields["FROM_USER_ID"] = $arFields['FILE']['authorId'];
			foreach ($arChat['userInChat'][$chatId] as $userId)
			{
				if ($userId != $arFields["FROM_USER_ID"])
				{
					$arFields["TO_USER_ID"] = $userId;
				}
			}

			$domain = CXMPPServer::GetDomain();
			if (CXMPPUtility::IsBitrix24Mode())
				$domain = $_SERVER["HTTP_HOST"];

			$factory = CXMPPFactory::GetFactory();

			return $factory->__SendMessage(
				$arFields["FROM_USER_ID"],
				$arFields["TO_USER_ID"],
				$arFields["MESSAGE_ID"] > 1? $arFields["MESSAGE_ID"]: 1,
				IM_MESSAGE_PRIVATE,
				htmlspecialcharsbx(CTextParser::convert4mail(str_replace("#BR#", "\n", ($arFields["MESSAGE_OUT"])))),
				$domain
			);
		}

		return true;
	}

	public function SendUnreadMessages($receiverJId, $domain = "")
	{
		$receiverJId = trim($receiverJId);
		if (strlen($receiverJId) <= 0)
			return false;

		$receiver = CXMPPUtility::GetUserByJId($receiverJId, $domain);
		if (!$receiver)
			return false;

		$factory = CXMPPFactory::GetFactory();
		if (IsModuleInstalled("im") && CModule::IncludeModule("im"))
		{
			$CIMMessage = new CIMMessage($receiverJId, Array(
				'hide_link' => true
			));
			$arMessage = $CIMMessage->GetUnreadMessage(Array(
				'SPEED_CHECK' => 'N',
				'ORDER' => 'ASC',
				'USE_SMILES' => 'N',
				'USER_LOAD' => 'N',
				'LOAD_DEPARTMENT' => 'N'
			));
			if ($arMessage['result'])
			{
				foreach ($arMessage['message'] as $id => $arMessage)
				{
					$factory->__SendMessage(
						$arMessage["senderId"],
						$arMessage["recipientId"],
						$arMessage["id"],
						IM_MESSAGE_PRIVATE,
						htmlspecialcharsbx(CTextParser::convert4mail(str_replace(array("#BR#", "<br />", "<br>", "<br/>"), "\n", $arMessage["text"]))),
						$domain
					);
				}
			}

			// Notify
			$CIMNotify = new CIMNotify($receiverJId);
			$arNotify = $CIMNotify->GetUnreadNotify(Array(
				'SPEED_CHECK' => 'N',
				'ORDER' => 'ASC'
			));
			if ($arNotify['result'])
			{
				foreach ($arNotify['original_notify'] as $id => $arNotify)
				{
					if (isset($arNotify["NOTIFY_MODULE"]) && isset($arNotify["NOTIFY_EVENT"])
					&& !CIMSettings::GetNotifyAccess($arNotify["TO_USER_ID"], $arNotify["NOTIFY_MODULE"], $arNotify["NOTIFY_EVENT"], CIMSettings::CLIENT_XMPP))
						continue;

					if ($arNotify["MESSAGE_OUT"] == IM_MAIL_SKIP)
						$arNotify["MESSAGE_OUT"] = '';

					$factory->__SendMessage(
						$arNotify["FROM_USER_ID"],
						$arNotify["TO_USER_ID"],
						$arNotify["ID"],
						IM_MESSAGE_SYSTEM,
						htmlspecialcharsbx(CTextParser::convert4mail(str_replace(array("#BR#", "<br />", "<br>", "<br/>"), "\n", ((strlen($arNotify["MESSAGE_OUT"])>0? $arNotify["MESSAGE_OUT"]: $arNotify["MESSAGE"]))))),
						$domain
					);
				}
			}
		}
		else
		{
			$parser = new CSocNetTextParser();
			$dbMessages = CSocNetMessages::GetList(
				array("DATE_CREATE" => "ASC"),
				array("TO_USER_ID" => $receiver["ID"], "DATE_VIEW" => "", "TO_DELETED" => "N", "IS_LOG_ALL" => "Y"),
				false,
				false,
				array("ID", "FROM_USER_ID", "TO_USER_ID", "MESSAGE", "DATE_VIEW", "MESSAGE_TYPE", "FROM_DELETED", "TO_DELETED", "IS_LOG")
			);
			while ($arMessage = $dbMessages->Fetch())
			{
				$factory->__SendMessage(
					(($arMessage["IS_LOG"] == "Y") ? -5 : $arMessage["FROM_USER_ID"]),
					$arMessage["TO_USER_ID"],
					$arMessage["ID"],
					$arMessage["MESSAGE_TYPE"],
					htmlspecialcharsbx($parser->convert4mail(str_replace(array("#BR#", "<br />", "<br>", "<br/>"), "\n", $arMessage["MESSAGE"]))),
					$domain
				);
			}
		}


		return true;
	}
}
?>