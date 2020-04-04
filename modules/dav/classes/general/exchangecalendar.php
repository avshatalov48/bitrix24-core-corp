<?
/*
$e = new CDavExchangeCalendar("http", "test-exch2007", 80, 'alex', 'P@$$w0rd');
//$e->Debug();
$r = $e->GetCalendarsList(array("mailbox" => "anti_bug@test.local"));
$r = $e->GetCalendarById("AQATAGFud...");
$r = $e->AddCalendar(array("NAME" => "CalFolder", "mailbox" => "anti_bug@test.local"));
$r = $e->UpdateCalendar(array("XML_ID" => "AQATAGFud...", "MODIFICATION_LABEL" => "AgAAAB..."), array("NAME" => "CalFolder 1"));
$r = $e->DeleteCalendar("AQATAGFud...");

$r = $e->GetList(
	array("Mailbox" => "anti_bug@test.local", "CalendarId" => "JTJFHDTrs..."),
	array(
		"CalendarView" => array("StartDate" => "01.01.2011 00:00:00", "EndDate" => "31.12.2011 23:59:59"),
		"ItemShape" => "AllProperties"
	)
);
$r = $e->GetList(
	array("Mailbox" => "anti_bug@test.local"),
	array("ItemShape" => "IdOnly")
);
$r = $e->GetList(
	array("Mailbox" => "anti_bug@test.local", "CalendarId" => "JTJFHDTrs..."),
	array(
		"CalendarView" => array("01.01.2011 00:00:00", "31.12.2011 23:59:59"),
		"ItemShape" => "IdOnly"
	)
);
If the CalendarView mode is specified, the GetList method returns a list of single calendar items and occurrences of recurring calendar items within the range specified by StartDate and EndDate.
If the CalendarView element is not specified, the GetList method returns a list of single calendar items and recurring master calendar items. Calendar occurrences of a recurring calendar item are not expanded.

$r = $e->GetById("AAATAGFudGlf...");

$arFields = array(
	"MAILBOX" => "anti_bug@test.local",
	"CALENDAR_ID" => "JTJFHDTrs...",
	"NAME" => "Rec 121",
	"DETAIL_TEXT" => "Rec 122",
	"DETAIL_TEXT_TYPE" => "html",			// text, html
	"PROPERTY_IMPORTANCE" => "normal",		// High, Normal, Low
	"PROPERTY_SENSITIVITY" => "Normal",		// Normal, Personal, Private, Confidential
	"PROPERTY_FREEBUSY" => "Busy",			// Free, Tentative, Busy, OOF, NoData
	"PROPERTY_REMIND_SETTINGS" => "20_min",
	"ACTIVE_FROM" => "16.03.2011 09:00:00",
	"ACTIVE_TO" => "16.05.2011 09:30:00",
	"PROPERTY_LOCATION" => "Rec 123",
	"REQUIRED_ATTENDEES" => array("alex@test.local"),
	"RECURRING_TYPE" => "MONTHLY_RELATIVE",	// MONTHLY_ABSOLUTE, MONTHLY_RELATIVE, YEARLY_ABSOLUTE, YEARLY_RELATIVE, WEEKLY, DAILY
	"RECURRING_INTERVAL" => 3,
	"RECURRING_DAYOFMONTH" => 5,
	"RECURRING_DAYSOFWEEK" => "Monday",		// Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Day, Weekday, WeekendDay
	"RECURRING_DAYOFWEEKINDEX" => "Third",	// First, Second, Third, Fourth, Last
	"RECURRING_MONTH" => "November",		// January, February, March, April, May, June, July, August, September, October, November, December
	"RECURRING_STARTDATE" => "20.03.2011",
	"RECURRING_NUMBEROFOCCURRENCES" => 5,
);
$r = $e->Add($arFields);

$arFields = array(
	"NAME" => "ReCoRd 11",
	"DETAIL_TEXT" => "ReCoRd 12",
	"DETAIL_TEXT_TYPE" => "text",
	"PROPERTY_IMPORTANCE" => "normal",
	"PROPERTY_REMIND_SETTINGS" => "20_min",
	"ACTIVE_FROM" => "16.03.2011 10:00:00",
	"ACTIVE_TO" => "16.03.2011 10:30:00",
	"PROPERTY_LOCATION" => "ReCoRd 13",
);
$r = $e->Update(
	array("XML_ID" => "AAATAGFudG...", "MODIFICATION_LABEL" => "DwAAABY..."),
	$arFields
);

$r = $e->Delete("AAATAGFud...");

print_r($e->GetErrors());
*/

IncludeModuleLangFile(__FILE__);

if (COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar"))
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/classes/general/exchangecalendar2.php");
	return;
}
else
{
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
				return null;

			if ($this->ParseError($response))
				return null;

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
					$arResultItemsList[] = $this->ConvertCalendarToArray($calendarItem);
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
			if (array_key_exists("PROPERTY_REMIND_SETTINGS", $arFields))
			{
				if (!empty($arFields["PROPERTY_REMIND_SETTINGS"]))
				{
					$val = 0;
					$ar = explode("_", $arFields["PROPERTY_REMIND_SETTINGS"]);
					if ($ar[1] == "min")
						$val = intval($ar[0]);
					elseif ($ar[1] == "hour")
						$val = intval($ar[0]) * 60;
					elseif ($ar[1] == "day")
						$val = intval($ar[0]) * 60 * 24;

					if ($val > 0)
						$arFields["REMINDER_MINUTES_BEFORE_START"] = $val;
				}
				else
				{
					$arFields["REMINDER_MINUTES_BEFORE_START"] = null;
				}
			}

			if (array_key_exists("PROPERTY_PERIOD_TYPE", $arFields))
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

					$arFields["ACTIVE_TO"] = ConvertTimeStamp(MakeTimeStamp($arFields["ACTIVE_FROM"]) + $arFields["PROPERTY_EVENT_LENGTH"], FULL);
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
				"DETAIL_TEXT" => "Body",
				"DETAIL_TEXT_TYPE" => "BodyType",
				"PROPERTY_IMPORTANCE" => "Importance",
				"PROPERTY_SENSITIVITY" => "Sensitivity",
				"PROPERTY_FREEBUSY" => "LegacyFreeBusyStatus",
				//"DATE_CREATE" => "DateTimeCreated",
				"ACTIVE_FROM" => "Start",
				"ACTIVE_TO" => "End",
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

			foreach ($arFields as $key => $value)
			{
				if (!array_key_exists($key, $arMap))
					continue;

				$newKey = $arMap[$key];
				if (in_array($newKey, array("Start", "End")))
				{
					if ((!isset($arFields["RECURRING_TYPE"]) || is_null($arFields["RECURRING_TYPE"]) || ($arFields["RECURRING_TYPE"] == "NONE"))
						&& (date("H:i:s", MakeTimeStamp($arFields["ACTIVE_FROM"])) == "00:00:00" && date("H:i:s", MakeTimeStamp($arFields["ACTIVE_TO"])) == "00:00:00"))
					{
						if ($newKey == "End")
							$arFieldsNew[$newKey] = date("c", MakeTimeStamp($value) + 24*60*60);
						else
							$arFieldsNew[$newKey] = date("c", MakeTimeStamp($value));

						//$arFieldsNew["IsAllDayEvent"] = true;
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
				$arResultItem["DETAIL_TEXT"] = $this->Encode($arBody[0]->GetContent());
				$arResultItem["DETAIL_TEXT_TYPE"] = strtolower($arBody[0]->GetAttribute("BodyType"));
				if (strtolower($arResultItem["DETAIL_TEXT_TYPE"]) == "html")
					$arResultItem["DETAIL_TEXT"] = trim(strip_tags($arResultItem["DETAIL_TEXT"], '<b><i><u><p><img><a><br><ol><ul><li><hr>'));
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
				$arResultItem["ACTIVE_FROM"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arStart[0]->GetContent());

			$arEnd = $calendarItem->GetPath("/CalendarItem/End");
			if (count($arEnd) > 0)
				$arResultItem["ACTIVE_TO"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arEnd[0]->GetContent());

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

			//if (!isset($arResultItem["IS_RECURRING"]) || !$arResultItem["IS_RECURRING"])
			//{
				if (date("H:i:s", MakeTimeStamp($arResultItem["ACTIVE_FROM"])) == "00:00:00"
					&& date("H:i:s", MakeTimeStamp($arResultItem["ACTIVE_TO"])) == "00:00:00")
				{
					$arResultItem["ACTIVE_TO"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), MakeTimeStamp($arResultItem["ACTIVE_TO"]) - 24*60*60);
				}
			//}

			$arRecurrence = $calendarItem->GetPath("/CalendarItem/Recurrence");
			if (count($arRecurrence) > 0)
				$arResultItem = array_merge($arResultItem, $this->ConvertRecurrenceToArray($arRecurrence[0]));

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
			return "SendMeetingInvitationsOrCancellations=\"SendToNone\" MessageDisposition=\"SaveOnly\" ConflictResolution=\"AutoResolve\"";
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
					$itemBody .= "      <Attendee><Mailbox><EmailAddress>".htmlspecialcharsbx($val)."</EmailAddress></Mailbox></Attendee>\r\n";
				$itemBody .= "     </RequiredAttendees>\r\n";
			}
			elseif ($key == "Recurrence")
			{
				$itemBody .= "     <Recurrence>\r\n";

				if ($arFields["Recurrence"] == "DAILY")
					$rt = "DailyRecurrence";
				elseif ($arFields["Recurrence"] == "WEEKLY")
					$rt = "WeeklyRecurrence";
				elseif ($arFields["Recurrence"] == "MONTHLY")
					$rt = "MonthlyRecurrence";
				elseif ($arFields["Recurrence"] == "YEARLY")
					$rt = "YearlyRecurrence";

				$itemBody .= "      <".$rt.">\r\n";
				if (isset($arFields["Recurrence_Interval"]))
					$itemBody .= "       <Interval>".$arFields["Recurrence_Interval"]."</Interval>\r\n";
				if (isset($arFields["Recurrence_DaysOfWeek"]))
				{
					if (!is_array($arFields["Recurrence_DaysOfWeek"]))
						$arFields["Recurrence_DaysOfWeek"] = array($arFields["Recurrence_DaysOfWeek"]);

					foreach ($arFields["Recurrence_DaysOfWeek"] as $value)
						$itemBody .= "       <DaysOfWeek>".$value."</DaysOfWeek>\r\n";
				}
				$itemBody .= "      </".$rt.">\r\n";

				$itemBody .= "      <EndDateRecurrence>\r\n";
				$itemBody .= "       <StartDate>".$arFields["Recurrence_StartDate"]."</StartDate>\r\n";
				$itemBody .= "       <EndDate>".$arFields["Recurrence_EndDate"]."</EndDate>\r\n";
				$itemBody .= "      </EndDateRecurrence>\r\n";

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
			if (!CModule::IncludeModule("intranet"))
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

			$siteId = CDav::GetIntranetSite();
			CEventCalendar::InitCalendarEntry($siteId);
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
			$siteId = CDav::GetIntranetSite();

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
			while ($arUser = $dbUserList->Fetch())
			{
				$index++;
				if ($index > $maxNumber)
					break;

				if (DAV_EXCH_DEBUG)
					CDav::WriteToLog("Processing user [".$arUser["ID"]."] ".$arUser["LOGIN"], "SYNCE");

				$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $arUser["ID"], array("UF_BXDAVEX_CALSYNC" => ConvertTimeStamp(time(), FULL)));

				$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
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

				$arUserCalendars = CEventCalendar::SyncCalendars("exchange", $arUserCalendars, "user", $arUser["ID"], $siteId);
				$tmpNumItems = 0;

				foreach ($arUserCalendars as $userCalendar)
				{
					$userCalendarXmlId = $userCalendar["XML_ID"];
					$userCalendarXmlId = (($userCalendarXmlId == "calendar_".$arUser["ID"]) ? "calendar" : $userCalendarXmlId);

					$arCalendarItemsList = $exchange->GetList(
						array("mailbox" => $mailbox, "CalendarId" => $userCalendarXmlId),
						array("ItemShape" => "IdOnly")
					);

					$arUserCalendarItems = array();
					foreach ($arCalendarItemsList as $value)
					{
						$arUserCalendarItems[] = array(
							"XML_ID" => $value["XML_ID"],
							"MODIFICATION_LABEL" => $value["MODIFICATION_LABEL"],
						);
					}

					$arModifiedUserCalendarItems = CEventCalendar::SyncCalendarItems("exchange", $userCalendar["CALENDAR_ID"], $arUserCalendarItems);

					$tmpNumItems += count($arModifiedUserCalendarItems);

					foreach ($arModifiedUserCalendarItems as $value)
					{
						$arModifiedCalendarItem = $exchange->GetById($value["XML_ID"]);
						if (is_array($arModifiedCalendarItem) && count($arModifiedCalendarItem) > 0)
						{
							$arModifiedCalendarItem = $arModifiedCalendarItem[0];

							$arModifyEventArray = array(
								"ID" => $value["ID"],
								"NAME" => $arModifiedCalendarItem["NAME"],
								"DETAIL_TEXT" => $arModifiedCalendarItem["DETAIL_TEXT"],
								"DETAIL_TEXT_TYPE" => $arModifiedCalendarItem["DETAIL_TEXT_TYPE"],
								"XML_ID" => $arModifiedCalendarItem["XML_ID"],
								"PROPERTY_LOCATION" => $arModifiedCalendarItem["PROPERTY_LOCATION"],
								"ACTIVE_FROM" => $arModifiedCalendarItem["ACTIVE_FROM"],
								"ACTIVE_TO" => $arModifiedCalendarItem["ACTIVE_TO"],
								"PROPERTY_IMPORTANCE" => $arModifiedCalendarItem["PROPERTY_IMPORTANCE"],
								"PROPERTY_ACCESSIBILITY" => $arModifiedCalendarItem["PROPERTY_FREEBUSY"],
								"PROPERTY_REMIND_SETTINGS" => $arModifiedCalendarItem["PROPERTY_REMIND_SETTINGS"],
								"PROPERTY_PERIOD_TYPE" => "NONE",
								"PROPERTY_BXDAVEX_LABEL" => $arModifiedCalendarItem["MODIFICATION_LABEL"]
							);

							if ($arModifiedCalendarItem["IS_RECURRING"])
							{
								if ($arModifiedCalendarItem["RECURRING_TYPE"] == "MONTHLY_ABSOLUTE" || $arModifiedCalendarItem["RECURRING_TYPE"] == "MONTHLY_RELATIVE")
									$arModifyEventArray["PROPERTY_PERIOD_TYPE"] = "MONTHLY";
								elseif ($arModifiedCalendarItem["RECURRING_TYPE"] == "YEARLY_ABSOLUTE" || $arModifiedCalendarItem["RECURRING_TYPE"] == "YEARLY_RELATIVE")
									$arModifyEventArray["PROPERTY_PERIOD_TYPE"] = "YEARLY";
								elseif ($arModifiedCalendarItem["RECURRING_TYPE"] == "WEEKLY")
									$arModifyEventArray["PROPERTY_PERIOD_TYPE"] = "WEEKLY";
								elseif ($arModifiedCalendarItem["RECURRING_TYPE"] == "DAILY")
									$arModifyEventArray["PROPERTY_PERIOD_TYPE"] = "DAILY";

								if (isset($arModifiedCalendarItem["RECURRING_INTERVAL"]))
									$arModifyEventArray["PROPERTY_PERIOD_COUNT"] = $arModifiedCalendarItem["RECURRING_INTERVAL"];

								if ($arModifyEventArray["PROPERTY_PERIOD_TYPE"] == "WEEKLY")
								{
									if (isset($arModifiedCalendarItem["RECURRING_DAYSOFWEEK"]))
									{
										$ar = preg_split("/[;,\s]/i", $arModifiedCalendarItem["RECURRING_DAYSOFWEEK"]);
										$ar1 = array();
										foreach ($ar as $v)
											$ar1[] = $arWeekDayMap[strtolower($v)];
										$arModifyEventArray["PROPERTY_PERIOD_ADDITIONAL"] = implode(",", $ar1);
									}
								}

								$arModifyEventArray["PROPERTY_EVENT_LENGTH"] = MakeTimeStamp($arModifyEventArray["ACTIVE_TO"]) - MakeTimeStamp($arModifyEventArray["ACTIVE_FROM"]);

								if (isset($arModifiedCalendarItem["RECURRING_ENDDATE"]))
								{
									$arModifyEventArray["ACTIVE_TO"] = $arModifiedCalendarItem["RECURRING_ENDDATE"];
								}
								elseif (isset($arResultItem["RECURRING_NUMBEROFOCCURRENCES"]))
								{
									$eventTime = self::GetPeriodicEventTime(
										MakeTimeStamp($arModifyEventArray["ACTIVE_TO"]),
										array(
											"freq" => $arModifyEventArray["PROPERTY_PERIOD_TYPE"],
											"interval" => $arModifyEventArray["PROPERTY_PERIOD_COUNT"],
											"byday" => $arModifyEventArray["PROPERTY_PERIOD_ADDITIONAL"]
										),
										$arResultItem["RECURRING_NUMBEROFOCCURRENCES"]
									);
									$arModifyEventArray["ACTIVE_TO"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $eventTime);
								}
								else
								{
									$arModifyEventArray["ACTIVE_TO"] = ConvertTimeStamp(mktime(0, 0, 0, 12, 31, 2025), "FULL");
								}
							}


							CEventCalendar::ModifyEvent($userCalendar["CALENDAR_ID"], $arModifyEventArray);
							$bShouldClearCache = true;
						}
					}
				}

				if (DAV_EXCH_DEBUG)
					CDav::WriteToLog("Sync ".intval($tmpNumCals)." calendars, ".intval($tmpNumItems)." items", "SYNCE");
			}

			if ($bShouldClearCache)
				CEventCalendar::SyncClearCache($siteId);

			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("EXCHANGE sync finished", "SYNCE");

			return $bShouldClearCache;
		}

		public static function DataSync($paramUserId = 0)
		{
			self::DoDataSync($paramUserId, $lastError);
			return "CDavExchangeCalendar::DataSync();";
		}

		private static function GetPeriodicEventTime($eventDate, $arParams, $number)
		{
			$number = intval($number);
			if ($number < 1)
				$number = 1;

			if (!isset($arParams["interval"]))
				$arParams["interval"] = 1;
			$arParams["interval"] = intval($arParams["interval"]);

			if (!isset($arParams["freq"]) || !in_array(strtoupper($arParams["freq"]), array('DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY')))
				$arParams["freq"] = "DAILY";
			$arParams["freq"] = strtoupper($arParams["freq"]);

			if ($arParams["freq"] == 'WEEKLY')
			{
				if (isset($arParams["byday"]))
				{
					$arOld = explode(",", $arParams["byday"]);
					$arNew = array();
					foreach ($arOld as $v)
					{
						$v = trim($v);
						if (is_numeric($v))
						{
							$v = intval($v);
							if ($v >= 0 && $v < 7)
								$arNew[] = intval($v);
						}
					}
					if (count($arNew) > 0)
					{
						sort($arNew, SORT_NUMERIC);
						$arParams["byday"] = implode(",", $arNew);
					}
					else
					{
						unset($arParams["byday"]);
					}
				}

				if (!isset($arParams["byday"]))
				{
					$arParams["byday"] = date("w", $eventDate) - 1;
					if ($arParams["byday"] < 0)
						$arParams["byday"] = 6;
				}
			}

			$newEventDate = $eventDate;

			switch ($arParams["freq"])
			{
				case 'DAILY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + $arParams["interval"] * ($number - 1), date("Y", $newEventDate));
					break;
				case 'WEEKLY':
					$newEventDateDay = date("w", $newEventDateDay) - 1;
					if ($newEventDateDay < 0)
						$newEventDateDay = 6;

					$bStartFound = false;
					$arDays = explode(",", $arParams["byday"]);
					foreach ($arDays as $day)
					{
						if ($day >= $newEventDateDay)
						{
							$bStartFound = true;
							if ($day > $newEventDateDay)
							{
								$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + ($day - $newEventDateDay), date("Y", $newEventDate));
								$newEventDateDay = $day;
							}
							break;
						}
					}
					if (!$bStartFound)
					{
						$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + (($arParams["interval"] - 1) * 7 + (6 - $newEventDateDay) + $arDays[0] + 1), date("Y", $newEventDate));
						$newEventDateDay = $arDays[0];
					}

					$d = $i = 0;
					$priorDay = $newEventDateDay;
					foreach ($arDays as $day)
					{
						if ($newEventDateDay >= $day)
							continue;

						$d += $day - $priorDay;
						$priorDay = $day;

						$i++;
						if ($i >= $number - 1)
							break;
					}

					while ($i < $number - 1)
					{
						$bFirst = true;
						foreach ($arDays as $day)
						{
							if ($bFirst)
								$d += ($arParams["interval"] - 1) * 7 + (6 - $priorDay) + $day + 1;
							else
								$d += $day - $priorDay;
							$bFirst = false;

							$priorDay = $day;

							$i++;
							if ($i >= $number - 1)
								break;
						}
					}
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + $d, date("Y", $newEventDate));
					break;
				case 'MONTHLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate) + $arParams["interval"] * ($number - 1), date("d", $newEventDate), date("Y", $newEventDate));
					break;
				case 'YEARLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate), date("Y", $newEventDate) + $arParams["interval"] * ($number - 1));
					break;
			}

			return $newEventDate;
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
	}
}
?>