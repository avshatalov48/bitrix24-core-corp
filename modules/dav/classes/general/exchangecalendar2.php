<?

if (!class_exists("CDavExchangeCalendar"))
{
	IncludeModuleLangFile(__FILE__);
	\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/classes/general/exchangecalendar.php");

	class CDavExchangeCalendar
		extends CDavExchangeClient
	{
		static $arMapItem = array("MimeContent", "ItemId", "ParentFolderId", "ItemClass", "Subject", "Sensitivity", "Body", "Attachments", "DateTimeReceived", "Size", "Categories", "Importance", "InReplyTo", "IsSubmitted", "IsDraft", "IsFromMe", "IsResend", "IsUnmodified", "InternetMessageHeaders", "DateTimeSent", "DateTimeCreated", "ResponseObjects", "ReminderDueBy", "ReminderIsSet", "ReminderMinutesBeforeStart", "DisplayCc", "DisplayTo", "HasAttachments", "ExtendedProperty", "Culture", "EffectiveRights", "LastModifiedName", "LastModifiedTime");
		static $arMapCalendar = array("UID", "RecurrenceId", "DateTimeStamp", "Start", "End", "OriginalStart", "IsAllDayEvent", "LegacyFreeBusyStatus", "Location", "When", "IsMeeting", "IsCancelled", "IsRecurring", "MeetingRequestWasSent", "IsResponseRequested", "CalendarItemType", "MyResponseType", "Organizer", "RequiredAttendees", "OptionalAttendees", "Resources", "ConflictingMeetingCount", "AdjacentMeetingCount", "ConflictingMeetings", "AdjacentMeetings", "Duration", "TimeZone", "AppointmentReplyTime", "AppointmentSequenceNumber", "AppointmentState", "Recurrence", "FirstOccurrence", "LastOccurrence", "ModifiedOccurrences", "DeletedOccurrences", "MeetingTimeZone", "ConferenceType", "AllowNewTimeProposal", "IsOnlineMeeting", "MeetingWorkspaceUrl", "NetShowUrl");

		public function __construct($scheme, $server, $port, $userName, $userPassword, $siteId = null)
		{
			parent::__construct($scheme, $server, $port, $userName, $userPassword);
			$this->SetCurrentEncoding($siteId);
		}

		public function GetList($arFilter = array(), $arMode = array())
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/FindItem");
			$request->AddHeader("Connection", "Keep-Alive");

			$arMapTmp = array("calendar_id" => "CalendarId", "calendarid" => "CalendarId", "mailbox" => "Mailbox");
			CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);
			if (!array_key_exists("CalendarId", $arFilter))
				$arFilter["CalendarId"] = "calendar";

			$arMapTmp = array("calendarview" => "CalendarView", "calendar_view" => "CalendarView", "itemshape" => "ItemShape", "item_shape" => "ItemShape");
			CDavExchangeClient::NormalizeArray($arMode, $arMapTmp);
			if (!array_key_exists("ItemShape", $arMode))
				$arMode["ItemShape"] = "AllProperties";

			$arParentFolderId = array("id" => $arFilter["CalendarId"]);
			if (array_key_exists("Mailbox", $arFilter))
				$arParentFolderId["mailbox"] = $arFilter["Mailbox"];

			$arItem = null;
			if (array_key_exists("CalendarView", $arMode))
			{
				if (is_array($arMode["CalendarView"]))
				{
					$arCalendarView = $arMode["CalendarView"];
					$arMapTmp = array(0 => "StartDate", "startdate" => "StartDate", "start_date" => "StartDate", 1 => "EndDate", "enddate" => "EndDate", "end_date" => "EndDate");
					CDavExchangeClient::NormalizeArray($arCalendarView, $arMapTmp);

					if (array_key_exists("StartDate", $arCalendarView) && array_key_exists("EndDate", $arCalendarView))
					{
						$arItem = array(
							"type" => "CalendarView",
							"properties" => array(
								"StartDate" => date("c", MakeTimeStamp($arCalendarView["StartDate"])),
								"EndDate" => date("c", MakeTimeStamp($arCalendarView["EndDate"]))
							)
						);
					}
				}

				if (is_null($arItem))
					$this->AddError("WrongCalendarViewMode", "Wrong CalendarView mode.");
			}

			$request->CreateFindItemBody($arParentFolderId, $arItem, $arMode["ItemShape"]);

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

			$arResultItemsList = array();
			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/FindItemResponse/ResponseMessages/FindItemResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/FindItemResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/FindItemResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarItem = $responseMessage->GetPath("/FindItemResponseMessage/RootFolder/Items/CalendarItem");
				foreach ($arCalendarItem as $calendarItem)
					$arResultItemsList[] = $this->ConvertCalendarToArray($calendarItem);
			}

			return $arResultItemsList;
		}

		public function GetById($id)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/GetItem");
			$request->AddHeader("Connection", "Keep-Alive");
			$request->CreateGetItemBody($id, "AllProperties");

			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultItemsList = array();
			$xmlDoc = $response->GetBodyXml();
			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/GetItemResponse/ResponseMessages/GetItemResponseMessage");

			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/GetItemResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/GetItemResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarItem = $responseMessage->GetPath("/GetItemResponseMessage/Items/CalendarItem");
				foreach ($arCalendarItem as $calendarItem)
				{
					$arResultItemsList[] = $this->ConvertCalendarToArray($calendarItem);
				}
			}

			return $arResultItemsList;
		}

		public function Add($arFields)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/CreateItem");
			$request->AddHeader("Connection", "Keep-Alive");

			$arMapTmp = array("calendar_id" => "CalendarId", "calendarid" => "CalendarId", "mailbox" => "Mailbox");
			CDavExchangeClient::NormalizeArray($arFields, $arMapTmp);
			if (!array_key_exists("CalendarId", $arFields))
				$arFields["CalendarId"] = "calendar";

			$arFieldsNew = $this->FormatFieldsArray($arFields);

			$arParentFolderId = array("id" => $arFields["CalendarId"]);
			if (array_key_exists("Mailbox", $arFields))
				$arParentFolderId["mailbox"] = $arFields["Mailbox"];

			$request->CreateCreateItemBody($arParentFolderId, $arFieldsNew);

			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultItemsList = array();
			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/CreateItemResponse/ResponseMessages/CreateItemResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/CreateItemResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/CreateItemResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarItem = $responseMessage->GetPath("/CreateItemResponseMessage/Items/CalendarItem");
				foreach ($arCalendarItem as $calendarItem)
					$arResultItemsList[] = $this->ConvertCalendarToArray($calendarItem);
			}

			return $arResultItemsList;
		}

		public function Update($id, $arFields)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/UpdateItem");
			$request->AddHeader("Connection", "Keep-Alive");

			$arFieldsNew = $this->FormatFieldsArray($arFields);
			$request->CreateUpdateItemBody($id, $arFieldsNew);

			$this->Connect();
			$response = $this->Send($request);

			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultItemsList = array();
			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/UpdateItemResponse/ResponseMessages/UpdateItemResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/UpdateItemResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/UpdateItemResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarItem = $responseMessage->GetPath("/UpdateItemResponseMessage/Items/CalendarItem");
				foreach ($arCalendarItem as $calendarItem)
					$arResultItemsList[] = $this->ConvertCalendarToArray($calendarItem);
			}

			return $arResultItemsList;
		}

		public function Delete($id)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/DeleteItem");
			$request->AddHeader("Connection", "Keep-Alive");

			$request->CreateDeleteItemBody($id);


			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();


			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/DeleteItemResponse/ResponseMessages/DeleteItemResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/DeleteItemResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/DeleteItemResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					return false;
				}
			}

			return true;
		}


		public function GetCalendarsList($arFilter)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/FindFolder");
			$request->AddHeader("Connection", "Keep-Alive");

			$arMapTmp = array("mailbox" => "Mailbox");
			CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);

			$arParentFolderId = array("id" => "calendar");
			if (array_key_exists("Mailbox", $arFilter))
				$arParentFolderId["mailbox"] = $arFilter["Mailbox"];

			$request->CreateFindFolderBody($arParentFolderId, "AllProperties");

			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultFoldersList = array();
			try
			{
				$xmlDoc = $response->GetBodyXml();
			}
			catch (Exception $e)
			{
				$this->AddError($e->getCode(), $e->getMessage());
				return null;
			}

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/FindFolderResponse/ResponseMessages/FindFolderResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/FindFolderResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/FindFolderResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarFolder = $responseMessage->GetPath("/FindFolderResponseMessage/RootFolder/Folders/CalendarFolder");
				foreach ($arCalendarFolder as $calendarFolder)
					$arResultFoldersList[] = $this->ConvertCalendarFolderToArray($calendarFolder);
			}

			return $arResultFoldersList;
		}

		public function GetCalendarById($id)
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
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultFoldersList = array();
			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/GetFolderResponse/ResponseMessages/GetFolderResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/GetFolderResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/GetFolderResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarFolder = $responseMessage->GetPath("/GetFolderResponseMessage/Folders/CalendarFolder");
				foreach ($arCalendarFolder as $calendarFolder)
					$arResultFoldersList[] = $this->ConvertCalendarFolderToArray($calendarFolder);
			}

			return $arResultFoldersList;
		}

		public function AddCalendar($arFields)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/CreateFolder");
			$request->AddHeader("Connection", "Keep-Alive");

			$arMapTmp = array("mailbox" => "Mailbox");
			CDavExchangeClient::NormalizeArray($arFields, $arMapTmp);

			$arFieldsNew = $this->FormatFolderFieldsArray($arFields);

			$arParentFolderId = array("id" => "calendar");
			if (array_key_exists("Mailbox", $arFields))
				$arParentFolderId["mailbox"] = $arFields["Mailbox"];

			$request->CreateCreateFolderBody($arParentFolderId, $arFieldsNew);

			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultFoldersList = array();
			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/CreateFolderResponse/ResponseMessages/CreateFolderResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/CreateFolderResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/CreateFolderResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarFolder = $responseMessage->GetPath("/CreateFolderResponseMessage/Folders/CalendarFolder");
				foreach ($arCalendarFolder as $calendarFolder)
					$arResultFoldersList[] = $this->ConvertCalendarFolderToArray($calendarFolder);
			}

			return $arResultFoldersList;
		}

		public function UpdateCalendar($id, $arFields)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/UpdateFolder");
			$request->AddHeader("Connection", "Keep-Alive");

			$arFieldsNew = $this->FormatFolderFieldsArray($arFields);

			$request->CreateUpdateFolderBody($id, $arFieldsNew);

			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$arResultFoldersList = array();
			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/UpdateFolderResponse/ResponseMessages/UpdateFolderResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/UpdateFolderResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/UpdateFolderResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					continue;
				}

				$arCalendarFolder = $responseMessage->GetPath("/UpdateFolderResponseMessage/Folders/CalendarFolder");
				foreach ($arCalendarFolder as $calendarFolder)
					$arResultFoldersList[] = $this->ConvertCalendarFolderToArray($calendarFolder);
			}

			return $arResultFoldersList;
		}

		public function DeleteCalendar($id)
		{
			$this->ClearErrors();

			$request = $this->CreateSOAPRequest("POST", $this->GetPath());
			$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
			$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/DeleteFolder");
			$request->AddHeader("Connection", "Keep-Alive");

			$request->CreateDeleteFolderBody($id);

			$this->Connect();
			$response = $this->Send($request);
			$this->Disconnect();

			if (is_null($response))
				return null;

			if ($this->ParseError($response))
				return null;

			$xmlDoc = $response->GetBodyXml();

			$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/DeleteFolderResponse/ResponseMessages/DeleteFolderResponseMessage");
			foreach ($arResponseMessage as $responseMessage)
			{
				$arResponseCode = $responseMessage->GetPath("/DeleteFolderResponseMessage/ResponseCode");
				$responseCode = null;
				if (count($arResponseCode) > 0)
					$responseCode = $arResponseCode[0]->GetContent();

				$responseClass = $responseMessage->GetAttribute("ResponseClass");

				if ((!is_null($responseClass) && ($responseClass != "Success")) || (!is_null($responseCode) && ($responseCode != "NoError")))
				{
					$arMessageText = $responseMessage->GetPath("/DeleteFolderResponseMessage/MessageText");
					$messageText = "Error";
					if (count($arMessageText) > 0)
						$messageText = $arMessageText[0]->GetContent();

					$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
					return false;
				}
			}

			return true;
		}

		private function FormatFieldsArray($arFields)
		{
			if (array_key_exists("REMIND", $arFields) && isset($arFields["REMIND"][0]))
			{
				if (isset($arFields["REMIND"][0]))
				{
					$type = $arFields["REMIND"][0]["type"];
					$val = intval($arFields["REMIND"][0]["count"]);

					if ($type == "hour")
						$val = $val * 60;
					elseif ($type == "day")
						$val = $val * 60 * 24;

					if ($val > 0)
						$arFields["REMINDER_MINUTES_BEFORE_START"] = $val;
				}
			}

			$arFields["PROPERTY_SENSITIVITY"] = "Normal";
			if ($arFields["PRIVATE_EVENT"])
			{
				$arFields["PROPERTY_SENSITIVITY"] = "Private";
			}

			if (isset($arFields['ACCESSIBILITY']))
			{
				$arFields['ACCESSIBILITY'] = strtolower($arFields['ACCESSIBILITY']);
				if ($arFields['ACCESSIBILITY'] == "absent")
				{
					$arFields["PROPERTY_FREEBUSY"] = "OOF";
				}
				else if ($arFields['ACCESSIBILITY'] == "free")
				{
					$arFields["PROPERTY_FREEBUSY"] = "Free";
				}
				else if ($arFields['ACCESSIBILITY'] == "quest")
				{
					$arFields["PROPERTY_FREEBUSY"] = "Tentative";
				}
				else
				{
					$arFields["PROPERTY_FREEBUSY"] = "Busy";
				}
			}

			if (isset($arFields['RRULE']))
			{
				$rrule = $arFields["RRULE"];
				if (is_array($rrule) && in_array($rrule["FREQ"], array("DAILY", "WEEKLY", "MONTHLY", "YEARLY")))
				{
					$arFields["RECURRING_TYPE"] = $rrule["FREQ"];
					$arFields["RECURRING_INTERVAL"] = $rrule["INTERVAL"];

					if ($rrule["FREQ"] == "WEEKLY")
					{
						$bydays = explode(',', $rrule["BYDAY"]);
						if (empty($bydays))
							$bydays = array("MO");

						$weekMap = array('SU' => "Sunday", 'MO' => "Monday", 'TU' => "Tuesday", 'WE' => "Wednesday", 'TH' => "Thursday", 'FR' => "Friday", 'SA' => "Saturday");
						$ar1 = array();
						foreach ($bydays as $v)
							$ar1[] = $weekMap[trim($v)];
						$arFields["RECURRING_DAYSOFWEEK"] = implode(" ", $ar1);
					}

					$arFields["RECURRING_STARTDATE"] = ConvertTimeStamp($arFields["DATE_FROM_TS_UTC"], SHORT);
					if ($rrule["COUNT"])
					{
						$arFields["RECURRING_NUMBEROFOCCURRENCES"] = $rrule["COUNT"];
					}
					else
					{
						$arFields["RECURRING_ENDDATE"] = ConvertTimeStamp($arFields["DATE_TO_TS_UTC"], SHORT);
					}
				}
				else
				{
					$arFields["RECURRING_TYPE"] = null;
				}
			}
			else if (array_key_exists("PROPERTY_PERIOD_TYPE", $arFields)) // Deprecated
			{
				if (in_array($arFields["PROPERTY_PERIOD_TYPE"], array("DAILY", "WEEKLY", "MONTHLY", "YEARLY")))
				{
					$ar = array("DAILY" => "DAILY", "WEEKLY" => "WEEKLY", "MONTHLY" => "MONTHLY_ABSOLUTE", "YEARLY" => "YEARLY_ABSOLUTE");
					$arFields["RECURRING_TYPE"] = $ar[$arFields["PROPERTY_PERIOD_TYPE"]];

					if (isset($arFields["PROPERTY_PERIOD_COUNT"]) && strlen($arFields["PROPERTY_PERIOD_COUNT"]) > 0)
						$arFields["RECURRING_INTERVAL"] = $arFields["PROPERTY_PERIOD_COUNT"];

					if ($arFields["PROPERTY_PERIOD_TYPE"] == "WEEKLY" && strlen($arFields["PROPERTY_PERIOD_ADDITIONAL"]) > 0)
					{
						static $arWeekDayMap = array(6 => "Sunday", 0 => "Monday", 1 => "Tuesday", 2 => "Wednesday", 3 => "Thursday", 4 => "Friday", 5 => "Saturday");

						$ar = explode(",", $arFields["PROPERTY_PERIOD_ADDITIONAL"]);
						$ar1 = array();
						foreach ($ar as $v)
							$ar1[] = $arWeekDayMap[trim($v)];

						$arFields["RECURRING_DAYSOFWEEK"] = implode(" ", $ar1);
					}

					$arFields["RECURRING_STARTDATE"] = ConvertTimeStamp(MakeTimeStamp($arFields["ACTIVE_FROM"]), SHORT);
					$arFields["RECURRING_ENDDATE"] = ConvertTimeStamp(MakeTimeStamp($arFields["ACTIVE_TO"]), SHORT);
				}
				else
				{
					$arFields["RECURRING_TYPE"] = null;
				}
			}

			$arFieldsNew = array();

			$arMap = array(
				"XML_ID" => "Id",
				"NAME" => "Subject",
				"DESCRIPTION" => "Body",
				"DETAIL_TEXT_TYPE" => "BodyType",
				"PROPERTY_IMPORTANCE" => "Importance",
				"PROPERTY_SENSITIVITY" => "Sensitivity",
				"PROPERTY_FREEBUSY" => "LegacyFreeBusyStatus",
				//"DATE_CREATE" => "DateTimeCreated",
				"DATE_FROM" => "Start",
				"DATE_TO" => "End",
				"PROPERTY_LOCATION" => "Location",
				"REMINDER_MINUTES_BEFORE_START" => "ReminderMinutesBeforeStart",
				"RECURRING_TYPE" => "RecurringType",
				"RECURRING_INTERVAL" => "RecurringInterval",
				"RECURRING_DAYOFMONTH" => "RecurringDayOfMonth",
				"RECURRING_DAYSOFWEEK" => "RecurringDaysOfWeek",
				"RECURRING_DAYOFWEEKINDEX" => "RecurringDayOfWeekIndex",
				"RECURRING_MONTH" => "RecurringMonth",
				"RECURRING_STARTDATE" => "RecurringStartDate",
				"RECURRING_NUMBEROFOCCURRENCES" => "RecurringNumberOfOccurrences",
				"RECURRING_ENDDATE" => "RecurringEndDate",
			);

			$arFieldsNew["IsAllDayEvent"] = $arFields['DT_SKIP_TIME'] === 'Y';
			$arFields['DETAIL_TEXT_TYPE'] = 'HTML';

			foreach ($arFields as $key => $value)
			{
				if (!array_key_exists($key, $arMap))
					continue;

				$newKey = $arMap[$key];
				if (in_array($newKey, array("Start", "End")))
				{
					if ($arFields['DT_SKIP_TIME'] === 'Y')
					{
						if ($newKey == "End")
							$arFieldsNew[$newKey] = date("c", MakeTimeStamp($value) + 24*60*60);
						else
							$arFieldsNew[$newKey] = date("c", MakeTimeStamp($value));
					}
					else
					{
						$arFieldsNew[$newKey] = date("c", MakeTimeStamp($value));
					}
				}
				elseif ($this->FormatStandartFieldsArray($newKey, $value, $arFieldsNew)
					|| $this->FormatRecurrenceFieldsArray($newKey, $value, $arFieldsNew))
				{

				}
				else
				{
					$arFieldsNew[$newKey] = $this->Decode($value);
				}
			}

			if (isset($arFieldsNew["ReminderMinutesBeforeStart"]) && intval($arFieldsNew["ReminderMinutesBeforeStart"]) > 0)
			{
				$arFieldsNew["ReminderMinutesBeforeStart"] = intval($arFieldsNew["ReminderMinutesBeforeStart"]);
				$arFieldsNew["ReminderIsSet"] = true;
			}

			if (array_key_exists("REQUIRED_ATTENDEES", $arFields))
			{
				$val = $arFields["REQUIRED_ATTENDEES"];
				if (!is_array($val))
					$val = array($val);

				$arFieldsNew["RequiredAttendees"] = $val;
			}

			return $arFieldsNew;
		}

		private function FormatFolderFieldsArray($arFields)
		{
			$arFieldsNew = array();

			foreach ($arFields as $key => $value)
			{
				switch ($key)
				{
					case "NAME":
						$arFieldsNew["DisplayName"] = $this->Decode($value);
						break;
				}
			}

			return $arFieldsNew;
		}

		private function ConvertCalendarToArray($calendarItem)
		{
			$arResultItem = array();

			$arItemId = $calendarItem->GetPath("/CalendarItem/ItemId");
			if (count($arItemId) > 0)
			{
				$arResultItem["XML_ID"] = $arItemId[0]->GetAttribute("Id");
				$arResultItem["MODIFICATION_LABEL"] = $arItemId[0]->GetAttribute("ChangeKey");
			}

			$arSubject = $calendarItem->GetPath("/CalendarItem/Subject");
			if (count($arSubject) > 0)
				$arResultItem["NAME"] = $this->Encode($arSubject[0]->GetContent());

			$arBody = $calendarItem->GetPath("/CalendarItem/Body");
			if (count($arBody) > 0)
			{
				$arResultItem["DESCRIPTION"] = $this->Encode($arBody[0]->GetContent());
				$arResultItem["DETAIL_TEXT_TYPE"] = strtolower($arBody[0]->GetAttribute("BodyType"));
				if (strtolower($arResultItem["DETAIL_TEXT_TYPE"]) == "html")
				{
					$arResultItem["DESCRIPTION"] = preg_replace("/[\s|\S]*?<body[^>]*?>([\s|\S]*?)<\/body>[\s|\S]*/is".BX_UTF_PCRE_MODIFIER, "\\1", $arResultItem["DESCRIPTION"]);
				}
			}

			$arImportance = $calendarItem->GetPath("/CalendarItem/Importance");
			if (count($arImportance) > 0)
				$arResultItem["PROPERTY_IMPORTANCE"] = $arImportance[0]->GetContent();

			$arSensitivity = $calendarItem->GetPath("/CalendarItem/Sensitivity");
			if (count($arSensitivity) > 0)
				$arResultItem["PROPERTY_SENSITIVITY"] = $arSensitivity[0]->GetContent();

			$arLegacyFreeBusyStatus = $calendarItem->GetPath("/CalendarItem/LegacyFreeBusyStatus");
			if (count($arLegacyFreeBusyStatus) > 0)
				$arResultItem["PROPERTY_FREEBUSY"] = $arLegacyFreeBusyStatus[0]->GetContent();

			$arDateTimeCreated = $calendarItem->GetPath("/CalendarItem/DateTimeCreated");
			if (count($arDateTimeCreated) > 0)
				$arResultItem["DATE_CREATE"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arDateTimeCreated[0]->GetContent());

			$arStart = $calendarItem->GetPath("/CalendarItem/Start");
			if (count($arStart) > 0)
			{
				$arResultItem["ACTIVE_FROM"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arStart[0]->GetContent());
				$arResultItem["DT_FROM_TS"] = MakeTimeStamp(CDavICalendarTimeZone::GetFormattedServerDateTime($arStart[0]->GetContent()));
			}

			$arResultItem["SKIP_TIME"] = false;
			$arIsAllDayEvent = $calendarItem->GetPath("/CalendarItem/IsAllDayEvent");
			if (count($arIsAllDayEvent) > 0)
			{
				$arResultItem["SKIP_TIME"] = $arIsAllDayEvent[0]->GetContent() === 'true';
			}

			$arEnd = $calendarItem->GetPath("/CalendarItem/End");
			if (count($arEnd) > 0)
			{
				$arResultItem["ACTIVE_TO"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arEnd[0]->GetContent());
				$arResultItem["DT_TO_TS"] = MakeTimeStamp(CDavICalendarTimeZone::GetFormattedServerDateTime($arEnd[0]->GetContent()));
			}

			$arLocation = $calendarItem->GetPath("/CalendarItem/Location");
			if (count($arLocation) > 0)
				$arResultItem["PROPERTY_LOCATION"] = $this->Encode($arLocation[0]->GetContent());

			$arReminderIsSet = $calendarItem->GetPath("/CalendarItem/ReminderIsSet");
			if ((count($arReminderIsSet) > 0) && ($arReminderIsSet[0]->GetContent() == "true"))
			{
				$arReminderMinutesBeforeStart = $calendarItem->GetPath("/CalendarItem/ReminderMinutesBeforeStart");
				if (count($arReminderMinutesBeforeStart) > 0)
				{
					$arResultItem["PROPERTY_REMIND_SETTINGS"] = $arReminderMinutesBeforeStart[0]->GetContent()."_min";
					$arResultItem["REMINDER_MINUTES_BEFORE_START"] = $arReminderMinutesBeforeStart[0]->GetContent();
				}
			}

			$arIsRecurring = $calendarItem->GetPath("/CalendarItem/IsRecurring");
			if (count($arIsRecurring) > 0)
				$arResultItem["IS_RECURRING"] = ($arIsRecurring[0]->GetContent() == "true");

			$arCalendarItemType = $calendarItem->GetPath("/CalendarItem/CalendarItemType");
			if (count($arCalendarItemType) > 0)
			{
				$arResultItem["CALENDAR_ITEM_TYPE"] = $arCalendarItemType[0]->GetContent();
				$arResultItem["IS_RECURRING"] = ($arResultItem["CALENDAR_ITEM_TYPE"] != "Single");
			}

			$arRecurrence = $calendarItem->GetPath("/CalendarItem/Recurrence");

			if (count($arRecurrence) > 0)
			{
				$arResultItem = array_merge($arResultItem, $this->ConvertRecurrenceToArray($arRecurrence[0]));
			}

			$arIsMeeting = $calendarItem->GetPath("/CalendarItem/IsMeeting");
			if (count($arIsMeeting) > 0)
			{
				$arResultItem["IS_MEETING"] = ($arIsMeeting[0]->GetContent() == "true");
			}

			$arResultItem["ATTENDEES_EMAIL_LIST"] = array();
			$arResultItem["ATTENDEES_RESPONSE"] = array();
			if ($arResultItem["IS_MEETING"])
			{
				$arRequiredAttendees = $calendarItem->GetPath("/CalendarItem/RequiredAttendees");

				if(count($arRequiredAttendees) > 0)
				{
					$arRequiredAttendees = $arRequiredAttendees[0]->GetContent();
					for($i = 0, $l = count($arRequiredAttendees); $i < $l; $i++)
					{
						$email = $arRequiredAttendees[$i]->GetPath("/Attendee/Mailbox/EmailAddress");
						if(count($email) > 0)
						{
							$email = $email[0]->GetContent();
							$response = $arRequiredAttendees[$i]->GetPath("/Attendee/ResponseType");
							$response = count($response) > 0 ? $response[0]->GetContent() : 'Unknown';

							$arResultItem["ATTENDEES_EMAIL_LIST"][] = $email;
							$arResultItem["ATTENDEES_RESPONSE"][$email] = $response;
						}
					}
				}

				// IsResponseRequested
				// MyResponseType > NoResponseReceived|Organizer
				$organizerEmail = $calendarItem->GetPath("/CalendarItem/Organizer/Mailbox/EmailAddress");
				if ($organizerEmail && $organizerEmail[0])
				{
					$arResultItem["ORGANIZER_EMAIL"] = $organizerEmail[0]->GetContent();
					if(count($organizerEmail) > 0 && !in_array($arResultItem["ORGANIZER_EMAIL"], $arResultItem["ATTENDEES_EMAIL_LIST"]))
					{
						$arResultItem["ATTENDEES_EMAIL_LIST"] = array_merge(array($arResultItem["ORGANIZER_EMAIL"]), $arResultItem["ATTENDEES_EMAIL_LIST"]);
					}
				}

				$arResultItem["IS_MEETING"] = count($arResultItem["ATTENDEES_EMAIL_LIST"]) > 0;
			}

			return $arResultItem;
		}

		private function ConvertCalendarFolderToArray($calendarFolder)
		{
			$arResultFolder = array();

			$arFolderId = $calendarFolder->GetPath("/CalendarFolder/FolderId");
			if (count($arFolderId) > 0)
			{
				$arResultFolder["XML_ID"] = $arFolderId[0]->GetAttribute("Id");
				$arResultFolder["MODIFICATION_LABEL"] = $arFolderId[0]->GetAttribute("ChangeKey");
			}

			$arDisplayName = $calendarFolder->GetPath("/CalendarFolder/DisplayName");
			if (count($arDisplayName) > 0)
				$arResultFolder["NAME"] = $this->Encode($arDisplayName[0]->GetContent());

			$arTotalCount = $calendarFolder->GetPath("/CalendarFolder/TotalCount");
			if (count($arTotalCount) > 0)
				$arResultFolder["TOTAL_COUNT"] = $arTotalCount[0]->GetContent();

			$arChildFolderCount = $calendarFolder->GetPath("/CalendarFolder/ChildFolderCount");
			if (count($arChildFolderCount) > 0)
				$arResultFolder["CHILD_FOLDER_COUNT"] = $arChildFolderCount[0]->GetContent();

			return $arResultFolder;
		}

		public function CreateItemBody($arFields)
		{
			$itemBody  = "    <CalendarItem xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
			$itemBody .= "     <ItemClass>IPM.Appointment</ItemClass>\r\n";

			$arMap = array_merge(self::$arMapItem, self::$arMapCalendar);
			foreach ($arMap as $key)
			{
				if (!array_key_exists($key, $arFields))
					continue;

				$value = $arFields[$key];

				$itemBody .= $this->CreateUpdateField($key, $value, $arFields);
			}

			$itemBody .= "    </CalendarItem>\r\n";

			return $itemBody;
		}

		public function UpdateItemAttributes()
		{
			return "SendMeetingInvitationsOrCancellations=\"SendOnlyToAll\" MessageDisposition=\"SaveOnly\" ConflictResolution=\"AutoResolve\"";
		}

		public function UpdateItemBody($arFields)
		{
			$itemBody = "";

			$arMap = array_merge(self::$arMapItem, self::$arMapCalendar);
			foreach ($arMap as $key)
			{
				if (!array_key_exists($key, $arFields))
					continue;

				$value = $arFields[$key];
				$fieldUri = (in_array($key, self::$arMapCalendar) ? "calendar" : "item").":".htmlspecialcharsbx($key);

				if (is_null($value))
				{
					//$itemBody .= "      <DeleteItemField><FieldURI FieldURI=\"".$fieldUri."\"/></DeleteItemField>\r\n";
				}
				else
				{
					$itemBody .= "      <SetItemField>\r\n";
					$itemBody .= "       <FieldURI FieldURI=\"".$fieldUri."\"/>\r\n";
					$itemBody .= "       <CalendarItem>\r\n";

					$itemBody .= $this->CreateUpdateField($key, $value, $arFields);

					$itemBody .= "       </CalendarItem>\r\n";
					$itemBody .= "      </SetItemField>\r\n";
				}
			}
			return $itemBody;
		}

		private function CreateUpdateField($key, $value, &$arFields)
		{
			$itemBody = "";

			if ($key == "Body")
			{
				$itemBody .= "     <Body";
				if (array_key_exists("BodyType", $arFields))
					$itemBody .= " BodyType=\"".(strtolower($arFields["BodyType"]) == "html" ? "HTML" : "Text")."\"";
				$itemBody .= ">".htmlspecialcharsbx($value)."</Body>\r\n";
			}
			elseif ($key == "RequiredAttendees")
			{
				$itemBody .= "     <RequiredAttendees>\r\n";
				foreach ($value as $val)
				{
					$itemBody .= "      <Attendee><Mailbox><EmailAddress>".htmlspecialcharsbx($val)."</EmailAddress></Mailbox><ResponseType>Accept</ResponseType></Attendee>\r\n";
					$itemBody .= "      <Attendee><Mailbox><EmailAddress>".htmlspecialcharsbx($val)."</EmailAddress></Mailbox></Attendee>\r\n";
				}

				$itemBody .= "     </RequiredAttendees>\r\n";
			}
			elseif ($key == "Recurrence")
			{
				$itemBody .= "     <Recurrence>\r\n";

				if ($arFields["RecurringType"] == "DAILY")
					$rt = "DailyRecurrence";
				elseif ($arFields["RecurringType"] == "WEEKLY")
					$rt = "WeeklyRecurrence";
				elseif ($arFields["RecurringType"] == "MONTHLY")
					$rt = "AbsoluteMonthlyRecurrence";
				elseif ($arFields["RecurringType"] == "YEARLY")
					$rt = "YearlyRecurrence";
				if(!empty($rt))
					$itemBody .= "      <".$rt.">\r\n";

				if (isset($arFields["RecurringInterval"]))
					$itemBody .= "       <Interval>".$arFields["RecurringInterval"]."</Interval>\r\n";
				if (isset($arFields["RecurringDaysOfWeek"]))
				{
					if (!is_array($arFields["RecurringDaysOfWeek"]))
						$arFields["RecurringDaysOfWeek"] = array($arFields["RecurringDaysOfWeek"]);

					foreach ($arFields["RecurringDaysOfWeek"] as $value)
						$itemBody .= "       <DaysOfWeek>".$value."</DaysOfWeek>\r\n";
				}

				// TODO: mantis:#67383
				if ($arFields["RecurringType"] == "MONTHLY")
				{
					//$itemBody .= "       <DayOfMonth>1"."</DayOfMonth>\r\n";
				}

				$itemBody .= "      </".$rt.">\r\n";

				if (isset($arFields["RecurringNumberOfOccurrences"]) && $arFields["RecurringNumberOfOccurrences"] > 0)
				{
					$itemBody .= "      <NumberedRecurrence>\r\n";
					$itemBody .= "       <StartDate>".$arFields["RecurringStartDate"]."</StartDate>\r\n";
					$itemBody .= "       <NumberOfOccurrences>".$arFields["RecurringNumberOfOccurrences"]."</NumberOfOccurrences>\r\n";
					$itemBody .= "      </NumberedRecurrence>\r\n";
				}
				elseif (isset($arFields["RecurringEndDate"]))
				{
					$itemBody .= "      <EndDateRecurrence>\r\n";
					$itemBody .= "       <StartDate>".$arFields["RecurringStartDate"]."</StartDate>\r\n";
					$itemBody .= "       <EndDate>".$arFields["RecurringEndDate"]."</EndDate>\r\n";
					$itemBody .= "      </EndDateRecurrence>\r\n";
				}
				else
				{
					$itemBody .= "      <NoEndRecurrence>\r\n";
					$itemBody .= "       <StartDate>".$arFields["RecurringStartDate"]."</StartDate>\r\n";
					$itemBody .= "      </EndDateRecurrence>\r\n";
				}

				$itemBody .= "     </Recurrence>\r\n";
			}
			else
			{
				$itemBody .= "     <".htmlspecialcharsbx($key).">";
				if (is_bool($value))
					$itemBody .= ($value ? "true" : "false");
				else
					$itemBody .= htmlspecialcharsbx($value);
				$itemBody .= "</".htmlspecialcharsbx($key).">\r\n";
			}

			return $itemBody;
		}

		public function CreateFolderBody($arFields)
		{
			$itemBody  = "    <CalendarFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
			//$itemBody .= "     <FolderClass>IPF.Appointment</FolderClass>\r\n";
			$itemBody .= "     <DisplayName>".htmlspecialcharsbx($arFields["DisplayName"])."</DisplayName>\r\n";
			$itemBody .= "    </CalendarFolder>\r\n";
			return $itemBody;
		}

		public function UpdateFolderBody($arFields)
		{
			$itemBody = "";

			$itemBody .= "      <SetFolderField>\r\n";
			$itemBody .= "       <FieldURI FieldURI=\"folder:DisplayName\"/>\r\n";
			$itemBody .= "       <CalendarFolder>\r\n";
			$itemBody .= "        <DisplayName>".htmlspecialcharsbx($arFields["DisplayName"])."</DisplayName>\r\n";
			$itemBody .= "       </CalendarFolder>\r\n";
			$itemBody .= "      </SetFolderField>\r\n";

			return $itemBody;
		}

		public static function InitUserEntity()
		{
			if (!CModule::IncludeModule("calendar"))
				return;

			//if (!defined("BX_NO_ACCELERATOR_RESET"))
			//	define("BX_NO_ACCELERATOR_RESET", true);

			$arRequiredFields = array(
				"UF_BXDAVEX_CALSYNC" => array(
					"USER_TYPE_ID" => "datetime",
					"SORT" => 100,
					"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Calendar sync date",
				)
			);

			$arUserCustomFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER");
			foreach ($arUserCustomFields as $key => $value)
			{
				if (array_key_exists($key, $arRequiredFields))
					unset($arRequiredFields[$key]);
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

		public static function DoDataSync($paramUserId, &$lastError)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("Starting EXCHANGE sync...", "SYNCE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer)/* || (COption::GetOptionString("dav", "agent_calendar", "N") != "Y")*/)
			{
				CAgent::RemoveAgent("CDavExchangeCalendar::DataSync();", "dav");
				COption::SetOptionString("dav", "agent_calendar", "N");
				return null;
			}

			static $arWeekDayMap = array("sunday" => 6, "monday" => 0, "tuesday" => 1, "wednesday" => 2, "thursday" => 3, "friday" => 4, "saturday" => 5);
			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);
			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			$maxNumber = 15;
			$index = 0;
			$bShouldClearCache = null;

			$paramUserId = intval($paramUserId);
			$arUserFilter = array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false);
			if ($paramUserId > 0)
				$arUserFilter["ID_EQUAL_EXACT"] = $paramUserId;
			if ($exchangeUseLogin == "N")
				$arUserFilter["!UF_BXDAVEX_MAILBOX"] = false;

			$dbUserList = CUser::GetList($by = "UF_BXDAVEX_CALSYNC", $order = "asc", $arUserFilter, array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC")));

			$usersToSync = array();
			$handledUsers = array();

			while ($arUser = $dbUserList->Fetch())
			{
				$index++;
				if ($index > $maxNumber)
					break;

				if (DAV_EXCH_DEBUG)
					CDav::WriteToLog("Processing user [".$arUser["ID"]."] ".$arUser["LOGIN"], "SYNCE");

				$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $arUser["ID"], array("UF_BXDAVEX_CALSYNC" => ConvertTimeStamp(time(), FULL)));

				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : trim($arUser["UF_BXDAVEX_MAILBOX"]));
				if (empty($mailbox))
				{
					$lastError = GetMessage("DAV_EC_EMPTY_MAILBOX");
					continue;
				}

				$arCalendarsList = $exchange->GetCalendarsList(array("mailbox" => $mailbox));
				$arErrorsTmp = $exchange->GetErrors();
				if (count($arErrorsTmp) > 0)
				{
					$txt = '';
					foreach ($arErrorsTmp as $v)
					{
						if (!empty($txt))
							$txt .= ", ";
						$txt .= "[".$v[0]."] ".$v[1];
					}
					if (DAV_EXCH_DEBUG)
						CDav::WriteToLog("ERROR: ".$txt, "SYNCE");
					$lastError = $txt;
					continue;
				}

				if (!is_array($arCalendarsList))
				{
					$lastError = "Incorrect Data from Exchange Server";
					continue;
				}

				$bShouldClearCache = false;
				$arUserCalendars = array(
					array(
						"XML_ID" => "calendar_".$arUser["ID"],
						"NAME" => GetMessage("DAV_EC_CALENDAR"),
						"MODIFICATION_LABEL" => "",
					)
				);
				foreach ($arCalendarsList as $value)
				{
					$arUserCalendars[] = array(
						"XML_ID" => $value["XML_ID"],
						"NAME" => $value["NAME"],
						"MODIFICATION_LABEL" => $value["MODIFICATION_LABEL"],
					);
				}

				$tmpNumCals = count($arUserCalendars);
				$arUserCalendars = CCalendarSync::SyncCalendarSections("exchange", $arUserCalendars, "user", $arUser["ID"]);
				$tmpNumItems = 0;

				foreach ($arUserCalendars as $userCalendar)
				{
					$userCalendarXmlId = $userCalendar["XML_ID"];
					$userCalendarXmlId = (($userCalendarXmlId == "calendar_".$arUser["ID"]) ? "calendar" : $userCalendarXmlId);

					$arCalendarItemsList = $exchange->GetList(
						array("mailbox" => $mailbox, "CalendarId" => $userCalendarXmlId),
						array("ItemShape" => "IdOnly")
					);

					if(!empty($arCalendarItemsList))
					{
						$arUserCalendarItems = array();
						foreach ($arCalendarItemsList as $value)
						{
							$arUserCalendarItems[] = array(
								"XML_ID" => $value["XML_ID"],
								"MODIFICATION_LABEL" => $value["MODIFICATION_LABEL"],
							);
						}

						$arModifiedUserCalendarItems = CCalendar::SyncCalendarItems("exchange", $userCalendar["CALENDAR_ID"], $arUserCalendarItems);

						$tmpNumItems += count($arModifiedUserCalendarItems);
						if (is_array($arModifiedUserCalendarItems))
						{
							foreach ($arModifiedUserCalendarItems as $value)
							{
								$modifiedItem = $exchange->GetById($value["XML_ID"]);

								if (is_array($modifiedItem) && count($modifiedItem) > 0)
								{
									$modifiedItem = $modifiedItem[0];
									$modifyEventFields = array(
										"ID" => $value["ID"],
										"NAME" => $modifiedItem["NAME"],
										"DESCRIPTION" => $modifiedItem["DESCRIPTION"],
										"XML_ID" => $modifiedItem["XML_ID"],
										"PROPERTY_LOCATION" => $modifiedItem["PROPERTY_LOCATION"],
										"DATE_FROM" => $modifiedItem["ACTIVE_FROM"],
										"DATE_TO" => $modifiedItem["ACTIVE_TO"],
										"SKIP_TIME" => $modifiedItem["SKIP_TIME"],
										"PROPERTY_IMPORTANCE" => $modifiedItem["PROPERTY_IMPORTANCE"],
										"PROPERTY_REMIND_SETTINGS" => $modifiedItem["PROPERTY_REMIND_SETTINGS"],
										"PROPERTY_PERIOD_TYPE" => "NONE",
										"PROPERTY_BXDAVEX_LABEL" => $modifiedItem["MODIFICATION_LABEL"],
										"PRIVATE_EVENT" => strtolower($modifiedItem["PROPERTY_SENSITIVITY"]) == 'private'
									);

									if ($modifiedItem["PROPERTY_FREEBUSY"])
									{
										$modifiedItem["PROPERTY_FREEBUSY"] = strtolower($modifiedItem["PROPERTY_FREEBUSY"]);
										if ($modifiedItem["PROPERTY_FREEBUSY"] == "oof")
										{
											$modifyEventFields["PROPERTY_ACCESSIBILITY"] = "absent";
										}
										else if ($modifiedItem["PROPERTY_FREEBUSY"] == "free")
										{
											$modifyEventFields["PROPERTY_ACCESSIBILITY"] = "free";
										}
										else if ($modifiedItem["PROPERTY_FREEBUSY"] == "tentative")
										{
											$modifyEventFields["PROPERTY_ACCESSIBILITY"] = "quest";
										}
										else
										{
											$modifyEventFields["PROPERTY_ACCESSIBILITY"] = "busy";
										}
									}


									if ($modifiedItem["IS_RECURRING"])
									{
										if ($modifiedItem["RECURRING_TYPE"] == "MONTHLY_ABSOLUTE" ||
											$modifiedItem["RECURRING_TYPE"] == "MONTHLY_RELATIVE" ||
											$modifiedItem["RECURRING_TYPE"] == "MONTHLY")
											$modifyEventFields["PROPERTY_PERIOD_TYPE"] = "MONTHLY";
										elseif ($modifiedItem["RECURRING_TYPE"] == "YEARLY_ABSOLUTE" ||
											$modifiedItem["RECURRING_TYPE"] == "YEARLY_RELATIVE" ||
											$modifiedItem["RECURRING_TYPE"] == "YEARLY")
											$modifyEventFields["PROPERTY_PERIOD_TYPE"] = "YEARLY";
										elseif ($modifiedItem["RECURRING_TYPE"] == "WEEKLY")
											$modifyEventFields["PROPERTY_PERIOD_TYPE"] = "WEEKLY";
										elseif ($modifiedItem["RECURRING_TYPE"] == "DAILY")
											$modifyEventFields["PROPERTY_PERIOD_TYPE"] = "DAILY";

										if (isset($modifiedItem["RECURRING_INTERVAL"]))
											$modifyEventFields["PROPERTY_PERIOD_COUNT"] = $modifiedItem["RECURRING_INTERVAL"];

										if ($modifyEventFields["PROPERTY_PERIOD_TYPE"] == "WEEKLY")
										{
											if (isset($modifiedItem["RECURRING_DAYSOFWEEK"]))
											{
												$ar = preg_split("/[;,\s]/i", $modifiedItem["RECURRING_DAYSOFWEEK"]);
												$ar1 = array();
												foreach ($ar as $v)
													$ar1[] = $arWeekDayMap[strtolower($v)];
												$modifyEventFields["PROPERTY_PERIOD_ADDITIONAL"] = implode(",", $ar1);
											}
										}

										$modifyEventFields["PROPERTY_EVENT_LENGTH"] = MakeTimeStamp($modifyEventFields["DATE_TO"]) - MakeTimeStamp($modifyEventFields["DATE_FROM"]);
										if ($modifyEventFields["PROPERTY_EVENT_LENGTH"] <= 0)
											$modifyEventFields["PROPERTY_EVENT_LENGTH"] = 86400;

										if (isset($modifiedItem["RECURRING_NUMBEROFOCCURRENCES"]) && $modifiedItem["RECURRING_NUMBEROFOCCURRENCES"] > 0)
										{
											$modifyEventFields["PROPERTY_RRULE_COUNT"] = intval($modifiedItem["RECURRING_NUMBEROFOCCURRENCES"]);
										}
										elseif (isset($modifiedItem["RECURRING_ENDDATE"]))
										{
											$modifyEventFields["PROPERTY_PERIOD_UNTIL"] = $modifiedItem["RECURRING_ENDDATE"];
										}
										else
										{
											$modifyEventFields["PROPERTY_PERIOD_UNTIL"] = ConvertTimeStamp(mktime(0, 0, 0, 12, 31, 2025), "FULL");
										}
									}

									if (class_exists('CCalendarSync') && method_exists('CCalendarSync', 'isExchangeMeetingEnabled')
										&& CCalendarSync::isExchangeMeetingEnabled()
										&& isset($modifiedItem["ATTENDEES_EMAIL_LIST"])
										&& !empty($modifiedItem["ATTENDEES_EMAIL_LIST"]))
									{
										$organizer = self::GetUsersByEmailList(array($modifiedItem["ORGANIZER_EMAIL"]));
										$entityId = $arUser["ID"];

										// Following code executes only for events from organizer
										if (count($organizer) > 0 && $organizer[0])
										{
											if ($organizer[0] == $entityId)
											{
												$attendeesMap = self::GetUsersEmailMap($modifiedItem["ATTENDEES_EMAIL_LIST"]);
												$modifyEventFields['IS_MEETING'] = true;
												$modifyEventFields['MEETING_HOST'] = $entityId;
												$modifyEventFields['MEETING'] = array(
													'HOST_NAME' => CCalendar::GetUserName($entityId)
												);

												$modifyEventFields['ATTENDEES_CODES'] = array();
												$modifyEventFields['ATTENDEES_RESPONSE'] = array();

												foreach($modifiedItem["ATTENDEES_EMAIL_LIST"] as $email)
												{
													$email = strtolower($email);
													if ($entityId == $attendeesMap[$email])
														continue;

													if(isset($attendeesMap[$email]) && $attendeesMap[$email])
													{
														$modifyEventFields['ATTENDEES_CODES'][] = 'U'.$attendeesMap[$email];
														if (!empty($modifiedItem['ATTENDEES_RESPONSE'][$email]))
														{
															$modifyEventFields['ATTENDEES_RESPONSE'][$attendeesMap[$email]] = self::ConvertExchangeResponse($modifiedItem['ATTENDEES_RESPONSE'][$email]);
														}
													}
													else
													{
														$modifyEventFields['ATTENDEES_CODES'][] = $email;
													}
												}
												$modifyEventFields['ATTENDEES_CODES'] = array_unique($modifyEventFields['ATTENDEES_CODES']);
												CCalendarSync::ModifyEvent($userCalendar["CALENDAR_ID"], $modifyEventFields);
											}
											else
											{
												$usersToSync[] = intval($organizer[0]);
											}
										}
									}
									// For not meetings
									else
									{
										CCalendarSync::ModifyEvent($userCalendar["CALENDAR_ID"], $modifyEventFields);
									}
									$bShouldClearCache = true;
								}
							}
						}

					}
				}

				if (DAV_EXCH_DEBUG)
					CDav::WriteToLog("Sync ".intval($tmpNumCals)." calendars, ".intval($tmpNumItems)." items", "SYNCE");

				$notify = new \Bitrix\Main\Event(
					'dav', 'OnExchandeCalendarDataSync',
					array(
						'userId' => $arUser["ID"],
						'shouldClearCache' => $bShouldClearCache,
						'lastError' => $lastError
					)
				);
				$notify->send();

				$handledUsers[] = intval($arUser["ID"]);
			}

			if (count($usersToSync) > 0)
			{
				$usersToSync = array_unique($usersToSync);
				$usersToSync = array_diff($usersToSync, $handledUsers);

				// Here we set UF_BXDAVEX_CALSYNC to value one day before now to triger
				// sync for these users as soon as possible
				foreach($usersToSync as $userId)
				{
					$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $userId, array("UF_BXDAVEX_CALSYNC" => ConvertTimeStamp(time() - 86400, FULL)));
				}
			}

			if ($bShouldClearCache)
				CCalendar::SyncClearCache();

			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE sync finished", "SYNCE");

			return $bShouldClearCache;
		}

		public static function DataSync($paramUserId = 0)
		{
			self::DoDataSync($paramUserId, $lastError);
			return "CDavExchangeCalendar::DataSync();";
		}

		public static function DoAddItem($userId, $calendarXmlId, $arFields)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE DoAddItem called for user ".$userId, "MDFE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer))
				return "";

			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				if (!empty($mailbox))
				{
					$arFields["MAILBOX"] = $mailbox;
					$arFields["CALENDAR_ID"] = (($calendarXmlId == "calendar_".$arUser["ID"]) ? "calendar" : $calendarXmlId);

					$arResult = $exchange->Add($arFields);

					if (is_array($arResult) && (count($arResult) > 0))
						return $arResult[0];
				}
			}

			return $exchange->GetErrors();
		}

		public static function DoUpdateItem($userId, $itemXmlId, $itemModificationLabel, $arFields)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE DoUpdateItem called for user ".$userId, "MDFE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer))
				return "";

			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				if (!empty($mailbox))
				{
					$arResult = $exchange->Update(
						array("XML_ID" => $itemXmlId, "MODIFICATION_LABEL" => $itemModificationLabel),
						$arFields
					);

					if (is_array($arResult) && (count($arResult) > 0))
						return $arResult[0];
				}
			}

			return $exchange->GetErrors();
		}

		public static function DoDeleteItem($userId, $itemXmlId)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE DoDeleteItem called for user ".$userId, "MDFE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer))
				return "";

			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				if (!empty($mailbox))
				{
					$arResult = $exchange->Delete($itemXmlId);
					if ($arResult)
						return $arResult;
				}
			}

			return $exchange->GetErrors();
		}

		public static function DoAddCalendar($userId, $arFields)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE DoAddCalendar called for user ".$userId, "MDFE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer))
				return "";

			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				if (!empty($mailbox))
				{
					$arFields["MAILBOX"] = $mailbox;
					$arResult = $exchange->AddCalendar($arFields);
					if (is_array($arResult) && (count($arResult) > 0))
						return $arResult[0];
				}
			}

			return $exchange->GetErrors();
		}

		public static function DoUpdateCalendar($userId, $itemXmlId, $itemModificationLabel, $arFields)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE DoUpdateCalendar called for user ".$userId, "MDFE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer))
				return "";

			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			if ($itemXmlId == "calendar_".$userId)
				return '';

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				if (!empty($mailbox))
				{
					$arResult = $exchange->UpdateCalendar(
						array("XML_ID" => $itemXmlId, "MODIFICATION_LABEL" => $itemModificationLabel),
						$arFields
					);

					if (is_array($arResult) && (count($arResult) > 0))
						return $arResult[0];
				}
			}

			return $exchange->GetErrors();
		}

		public static function DoDeleteCalendar($userId, $itemXmlId)
		{
			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE DoDeleteCalendar called for user ".$userId, "MDFE");

			$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
			$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
			$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

			if (empty($exchangeServer))
				return "";

			$exchange = new CDavExchangeCalendar($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

			if (GW_DEBUG)
				$exchange->Debug();

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			self::InitUserEntity();

			if ($itemXmlId == "calendar_".$userId)
				return '';

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CALSYNC"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				if (!empty($mailbox))
				{
					$arResult = $exchange->DeleteCalendar($itemXmlId);
					if ($arResult)
						return $arResult;
				}
			}

			return $exchange->GetErrors();
		}

		public static function IsExchangeEnabled()
		{
			$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
			$agentCalendar = COption::GetOptionString("dav", "agent_calendar", "N");
			return (!empty($exchangeServer) && ($agentCalendar == "Y"));
		}

		public static function IsExchangeEnabledForUser($userId)
		{
			if (!self::IsExchangeEnabled())
				return false;

			$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
			$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

			$userId = intval($userId);
			$dbUserList = CUser::GetList(
				$by = "",
				$order = "",
				array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
				array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX"))
			);
			if ($arUser = $dbUserList->Fetch())
			{
				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
				return (!empty($mailbox));
			}

			return false;
		}

		public static function GetUsersEmailMap($emailList = array())
		{
			global $DB;
			$emailMap = array();

			if (CCalendar::IsSocNet())
			{
				$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
				$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");
				$exchangeMailboxStrlen = strlen($exchangeMailbox);

				$strValue = "";
				foreach($emailList as $email)
				{
					$strValue .= ",'".CDatabase::ForSql($email)."'";
				}
				$strValue = trim($strValue, ', ');

				if($strValue != '')
				{
					$strSql = "SELECT U.ID, BUF.UF_BXDAVEX_MAILBOX
						FROM b_user U
						LEFT JOIN b_uts_user BUF ON (BUF.VALUE_ID = U.ID)
						WHERE
							U.ACTIVE = 'Y' AND
							BUF.UF_BXDAVEX_MAILBOX in (".$strValue.")";

					$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$checkedEmails = array();
					while($entry = $res->Fetch())
					{
						$checkedEmails[] = strtolower($entry["UF_BXDAVEX_MAILBOX"]);
						//$users[] = $entry['ID'];
						$emailMap[strtolower($entry["UF_BXDAVEX_MAILBOX"])] = $entry['ID'];
					}

					if ($exchangeUseLogin == "Y")
					{
						$strLogins = '';
						foreach($emailList as $email)
						{
							if(!in_array(strtolower($email), $checkedEmails) && strtolower(substr($email, strlen($email) - $exchangeMailboxStrlen)) == strtolower($exchangeMailbox))
							{
								$value = substr($email, 0, strlen($email) - $exchangeMailboxStrlen);
								$strLogins .= ",'".CDatabase::ForSql($value)."'";
							}
						}
						$strLogins = trim($strLogins, ', ');

						if ($strLogins !== '')
						{
							$res = $DB->Query("SELECT U.ID, U.LOGIN FROM b_user U WHERE U.ACTIVE = 'Y' AND U.LOGIN in (".$strLogins.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);

							while($entry = $res->Fetch())
							{
								//$users[] = $entry['ID'];
								$emailMap[strtolower($entry["LOGIN"].$exchangeMailbox)] = $entry['ID'];
							}
						}
					}
				}
			}

			return $emailMap;
		}

		public static function GetUsersByEmailList($emailList = array())
		{
			$users = array();
			$map = self::GetUsersEmailMap($emailList);
			foreach($emailList as $email)
			{
				$email = strtolower($email);
				if(isset($map[$email]))
				{
					$users[] = $map[$email];
				}
			}
			return $users;
		}

		public static function ConvertExchangeResponse($response)
		{
			$response = strtolower($response);
			if ($response == 'accept')
				return 'Y';
			if ($response == 'decline')
				return 'N';
			return 'Q';
		}
	}
}
?>