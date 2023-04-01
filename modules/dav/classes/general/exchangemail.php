<?
IncludeModuleLangFile(__FILE__);

class CDavExchangeMail extends CDavExchangeClient
{
	public function __construct($scheme, $server, $port, $userName, $userPassword, $siteId = null)
	{
		parent::__construct($scheme, $server, $port, $userName, $userPassword);
		$this->SetCurrentEncoding($siteId);
	}

	public function GetFoldersList($arFilter)
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/FindFolder");
		$request->AddHeader("Connection", "Keep-Alive");

		$arMapTmp = array("mailbox" => "Mailbox", "id" => "Id", "xml_id" => "Id");
		CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);

		$arParentFolderId = array();
		if (array_key_exists("Id", $arFilter))
		{
			$arParentFolderId["id"] = $arFilter["Id"];
		}
		else
		{
			$arParentFolderId["id"] = "inbox";
		}

		if (array_key_exists("Mailbox", $arFilter))
		{
			$arParentFolderId["mailbox"] = $arFilter["Mailbox"];
		}

		$request->CreateFindFolderBody($arParentFolderId, "AllProperties");

		$this->Connect();
		$response = $this->Send($request);
		$this->Disconnect();

		if (is_null($response))
		{
			return null;
		}

		if ($this->ParseError($response))
		{
			return null;
		}

		$arResultFoldersList = [];
		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/FindFolderResponse/ResponseMessages/FindFolderResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/FindFolderResponseMessage/ResponseCode");
			$responseCode = null;
			if (!empty($arResponseCode))
			{
				$responseCode = $arResponseCode[0]->GetContent();
			}

			$responseClass = $responseMessage->GetAttribute("ResponseClass");

			if (
				(!is_null($responseClass) && ($responseClass !== "Success"))
				|| (!is_null($responseCode) && ($responseCode !== "NoError"))
			)
			{
				$arMessageText = $responseMessage->GetPath("/FindFolderResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arMailFolder = $responseMessage->GetPath("/FindFolderResponseMessage/RootFolder/Folders/Folder");
			foreach ($arMailFolder as $mailFolder)
			{
				$arResultFoldersList[] = $this->ConvertMailFolderToArray($mailFolder);
			}
		}

		return $arResultFoldersList;
	}

	public function GetFolderById($id)
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/GetFolder");
		$request->AddHeader("Connection", "Keep-Alive");

		$request->CreateGetFolderBody($id, "AllProperties");

		$this->Connect();
		$response = $this->Send($request);
		$this->Disconnect();

		if (is_null($response))
		{
			return null;
		}

		if ($this->ParseError($response))
		{
			return null;
		}

		$arResultFoldersList = [];
		try
		{
			$xmlDoc = $response->GetBodyXml();
		}
		catch (Exception $e)
		{
			$this->AddError($e->getCode(), $e->getMessage());
			return null;
		}

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/GetFolderResponse/ResponseMessages/GetFolderResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/GetFolderResponseMessage/ResponseCode");
			$responseCode = null;
			if (!empty($arResponseCode))
			{
				$responseCode = $arResponseCode[0]->GetContent();
			}

			$responseClass = $responseMessage->GetAttribute("ResponseClass");

			if (
				(!is_null($responseClass) && ($responseClass !== "Success"))
				|| (!is_null($responseCode) && ($responseCode !== "NoError"))
			)
			{
				$arMessageText = $responseMessage->GetPath("/GetFolderResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arCalendarFolder = $responseMessage->GetPath("/GetFolderResponseMessage/Folders/Folder");
			foreach ($arCalendarFolder as $calendarFolder)
			{
				$arResultFoldersList[] = $this->ConvertMailFolderToArray($calendarFolder);
			}
		}

		return $arResultFoldersList;
	}

	private function ConvertMailFolderToArray($mailFolder)
	{
		$arResultFolder = [];

		$arFolderId = $mailFolder->GetPath("/Folder/FolderId");
		if (!empty($arFolderId))
		{
			$arResultFolder["XML_ID"] = $arFolderId[0]->GetAttribute("Id");
			$arResultFolder["MODIFICATION_LABEL"] = $arFolderId[0]->GetAttribute("ChangeKey");
		}

		$arDisplayName = $mailFolder->GetPath("/Folder/DisplayName");
		if (!empty($arDisplayName))
		{
			$arResultFolder["NAME"] = $this->Encode($arDisplayName[0]->GetContent());
		}

		$arTotalCount = $mailFolder->GetPath("/Folder/TotalCount");
		if (!empty($arTotalCount))
		{
			$arResultFolder["TOTAL_COUNT"] = $arTotalCount[0]->GetContent();
		}

		$arChildFolderCount = $mailFolder->GetPath("/Folder/ChildFolderCount");
		if (!empty($arChildFolderCount))
		{
			$arResultFolder["CHILD_FOLDER_COUNT"] = $arChildFolderCount[0]->GetContent();
		}

		$arUnreadCount = $mailFolder->GetPath("/Folder/UnreadCount");
		if (!empty($arUnreadCount))
		{
			$arResultFolder["UNREAD_COUNT"] = $arUnreadCount[0]->GetContent();
		}

		return $arResultFolder;
	}

	private static function InitUserEntity()
	{
		$arRequiredFields = array(
			"UF_BXDAVEX_MLSYNC" => array(
				"USER_TYPE_ID" => "datetime",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Mail sync date",
			),
			"UF_UNREAD_MAIL_COUNT" => array(
				"USER_TYPE_ID" => "integer",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Unread mail count",
			)
		);

		$arUserCustomFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER");
		foreach ($arUserCustomFields as $key => $value)
		{
			if (array_key_exists($key, $arRequiredFields))
			{
				unset($arRequiredFields[$key]);
			}
		}

		foreach ($arRequiredFields as $requiredFieldKey => $requiredFieldValue)
		{
			$arFields = array(
				"ENTITY_ID" => "USER",
				"FIELD_NAME" => $requiredFieldKey,
				"SHOW_IN_LIST" => "N",
				"IS_SEARCHABLE" => "N",
				"SHOW_FILTER" => "N",
				"EDIT_IN_LIST" => "N",
				"EDIT_FORM_LABEL" => CDavExchangeClient::InitUserEntityLoadMessages($requiredFieldKey, $requiredFieldValue["EDIT_FORM_LABEL_DEFAULT_MESSAGE"]),
			);
			$obUserField = new CUserTypeEntity;
			$obUserField->Add(array_merge($arFields, $requiredFieldValue));
		}
	}

	public static function DataSync($paramUserId = 0)
	{
		$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
		$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
		$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

		if (empty($exchangeServer)/* || (COption::GetOptionString("dav", "agent_mail", "N") != "Y")*/)
		{
			CAgent::RemoveAgent("CDavExchangeMail::DataSync();", "dav");
			COption::SetOptionString("dav", "agent_mail", "N");

			return "";
		}

		$exchange = new CDavExchangeMail($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);
		//$exchange->Debug();

		$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
		$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

		self::InitUserEntity();

		$maxNumber = 5;
		$index = 0;

		$paramUserId = (int)$paramUserId;
		$arUserFilter = array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false);
		if ($paramUserId)
		{
			$arUserFilter["ID_EQUAL_EXACT"] = $paramUserId;
		}
		if ($exchangeUseLogin === "N")
		{
			$arUserFilter["!UF_BXDAVEX_MAILBOX"] = false;
		}

		$dbUserList = CUser::GetList(
			"UF_BXDAVEX_MLSYNC", "asc",
			$arUserFilter,
			array(
				'SELECT' => array('UF_BXDAVEX_MAILBOX', 'UF_BXDAVEX_MLSYNC'),
				'FIELDS' => array('ID', 'LOGIN')
			));
		while ($arUser = $dbUserList->Fetch())
		{
			$index++;
			if ($index > $maxNumber)
			{
				break;
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $arUser["ID"], array("UF_BXDAVEX_MLSYNC" => ConvertTimeStamp(time(), "FULL")));

			$mailbox = (($exchangeUseLogin === "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
			if (empty($mailbox))
			{
				continue;
			}

			$numberOfUnread = 0;

			$arInbox = $exchange->GetFolderById(array("XML_ID" => "inbox", "Mailbox" => $mailbox));
			if (is_array($arInbox))
			{
				foreach ($arInbox as $inbox)
				{
					if (isset($inbox["UNREAD_COUNT"]))
					{
						$numberOfUnread += (int)$inbox["UNREAD_COUNT"];
					}

					$arInbox1 = $exchange->GetFoldersList(array("XML_ID" => $inbox["XML_ID"], "Mailbox" => $mailbox));
					if (is_array($arInbox1))
					{
						foreach ($arInbox1 as $inbox1)
						{
							if (isset($inbox1["UNREAD_COUNT"]))
							{
								$numberOfUnread += (int)$inbox1["UNREAD_COUNT"];
							}
						}
					}
				}
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $arUser["ID"], array("UF_UNREAD_MAIL_COUNT" => $numberOfUnread));
			CUserCounter::Set($arUser["ID"], 'dav_unread_mail', $numberOfUnread, '**');
		}

		return "CDavExchangeMail::DataSync();";
	}

	public static function IsExchangeEnabled()
	{
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$agentMail = COption::GetOptionString("dav", "agent_mail", "N");

		return (!empty($exchangeServer) && ($agentMail === "Y"));
	}

	public static function GetTicker($user)
	{
		$userId = null;
		$numberOfUnreadMessages = null;

		if (!self::IsExchangeEnabled())
		{
			return null;
		}

		if (is_object($user))
		{
			if ($user->IsAuthorized())
			{
				$userId = (int)$user->GetID();
			}
		}
		elseif (is_array($user))
		{
			if (array_key_exists("UF_UNREAD_MAIL_COUNT", $user))
			{
				$numberOfUnreadMessages = $user["UF_UNREAD_MAIL_COUNT"];
			}
			elseif (array_key_exists("ID", $user))
			{
				$userId = (int)$user["ID"];
			}
		}
		elseif (((int)$user ."!" == $user."!") && ((int)$user > 0))
		{
			$userId = (int)$user;
		}

		if (is_null($numberOfUnreadMessages) && !is_null($userId))
		{
			$numberOfUnreadMessages = CUserCounter::GetValue($userId, 'dav_unread_mail');
		}

		if (empty($numberOfUnreadMessages))
		{
			return null;
		}

		$exchangeMailboxPath = COption::GetOptionString("dav", "exchange_mailbox_path", "");
		return array("numberOfUnreadMessages" => $numberOfUnreadMessages, "exchangeMailboxPath" => $exchangeMailboxPath);
	}

	public static function handleUserChange($arFields)
	{
		return true;
	}

	public static function handleUserTypeDelete($arField)
	{
		if (is_array($arField) && isset($arField['FIELD_NAME']) && $arField['FIELD_NAME'] === 'UF_BXDAVEX_MAILBOX')
		{
			CUserOptions::DeleteOptionsByName('dav', 'davex_mailbox');
		}
	}
}
?>