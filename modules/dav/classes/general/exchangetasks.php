<?
/*
$e = new CDavExchangeTasks("http", "test-exch2007", 80, 'alex', 'P@$$w0rd');
//$e->Debug();
$r = $e->GetFoldersList(array("mailbox" => "anti_bug@test.local"));
$r = $e->GetFolderById("AQATAGFud...");
$r = $e->AddFolder(array("NAME" => "AbFolder", "mailbox" => "anti_bug@test.local"));
$r = $e->UpdateFolder(array("XML_ID" => "AQATAGFud...", "MODIFICATION_LABEL" => "AwAAA..."), array("NAME" => "AbFolder1"));
$r = $e->DeleteFolder("AQATAGFud...");

$r = $e->GetList(array("mailbox" => "anti_bug@test.local"));
$r = $e->GetList(
	array("Mailbox" => "anti_bug@test.local", "FolderId" => "JTJFHDTrs..."),
	array("ItemShape" => "IdOnly")
);
$r = $e->GetById("AAATAGFud...");

$arFields = array(
	"MAILBOX" => "anti_bug@test.local",
	"FOLDER_ID" => "JTJFHDTrs...",
	"SUBJECT" => "New task",
	"BODY" => "ToDo!",
	"BODY_TYPE" => "text",		// text, html
	"IMPORTANCE" => "High",		// High, Normal, Low
	"START_DATE" => "20.03.2011",
	"DUE_DATE" => "25.03.2011",
	"PERCENT_COMPLETE" => "0",
	"STATUS" => "NotStarted",		//  NotStarted, InProgress, Completed, WaitingOnOthers, Deferred
	"TOTAL_WORK" => "123",
	"REMINDER_MINUTES_BEFORE_START" => 365,
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
	"mailbox" => "anti_bug@test.local",
	"SUBJECT" => "New task 111",
	"BODY" => "ToDo!",
	"BODY_TYPE" => "text",
	"IMPORTANCE" => "High",
	"START_DATE" => "20.03.2011",
	"DUE_DATE" => "25.03.2011",
	"PERCENT_COMPLETE" => "20",
	"STATUS" => "WaitingOnOthers",
	"TOTAL_WORK" => "123",
	"REMINDER_MINUTES_BEFORE_START" => 365,
	"RECURRING_TYPE" => "MONTHLY_RELATIVE",
	"RECURRING_INTERVAL" => 1,
	"RECURRING_DAYSOFWEEK" => "Thursday",
	"RECURRING_DAYOFWEEKINDEX" => "Last",
	"RECURRING_STARTDATE" => "20.03.2011",
	"RECURRING_ENDDATE" => "20.10.2011",
);
$r = $e->Update(
	array(
		"XML_ID" => "AAATAGFud...",
		"MODIFICATION_LABEL" => "EQAAAB...",
	),
	$arFields
);

$r = $e->Delete("AAATAGFud...");

print_r($e->GetErrors());
*/

IncludeModuleLangFile(__FILE__);

class CDavExchangeTasks extends CDavExchangeClient
{
	static $arMapItem = array("MimeContent", "ItemId", "ParentFolderId", "ItemClass", "Subject", "Sensitivity", "Body", "Attachments", "DateTimeReceived", "Size", "Categories", "Importance", "InReplyTo", "IsSubmitted", "IsDraft", "IsFromMe", "IsResend", "IsUnmodified", "InternetMessageHeaders", "DateTimeSent", "DateTimeCreated", "ResponseObjects", "ReminderDueBy", "ReminderIsSet", "ReminderMinutesBeforeStart", "DisplayCc", "DisplayTo", "HasAttachments", "ExtendedProperty", "Culture", "EffectiveRights", "LastModifiedName", "LastModifiedTime");
	static $arMapTask = array("ActualWork", "AssignedTime", "BillingInformation", "ChangeCount", "Companies", "CompleteDate", "Contacts", "DelegationState", "Delegator", "DueDate", "IsAssignmentEditable", "IsComplete", "IsRecurring", "IsTeamTask", "Mileage", "Owner", "PercentComplete", "Recurrence", "StartDate", "Status", "StatusDescription", "TotalWork");
	static $arMapExtendedFields = array('GUID', 'SERIALIZED_DATA');

	public function __construct($scheme, $server, $port, $userName, $userPassword, $siteId = null)
	{
		parent::__construct($scheme, $server, $port, $userName, $userPassword);
		$this->SetCurrentEncoding($siteId);
	}

	public function GetList($arFilter = array(), $arMode = array(), $arAdditionalExtendedProperties = array())
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/FindItem");
		$request->AddHeader("Connection", "Keep-Alive");

		$arMapTmp = array("folder_id" => "FolderId", "folderid" => "FolderId", "mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);
		if (!array_key_exists("FolderId", $arFilter))
		{
			$arFilter["FolderId"] = "tasks";
		}

		$arMapTmp = array("itemshape" => "ItemShape", "item_shape" => "ItemShape");
		CDavExchangeClient::NormalizeArray($arMode, $arMapTmp);
		if (!array_key_exists("ItemShape", $arMode))
		{
			$arMode["ItemShape"] = "AllProperties";
		}

		$arParentFolderId = array("id" => $arFilter["FolderId"]);
		if (array_key_exists("Mailbox", $arFilter))
		{
			$arParentFolderId["mailbox"] = $arFilter["Mailbox"];
		}

		$arAdditionalProperties = array();

		$request->CreateFindItemBody($arParentFolderId, null,
			$arMode["ItemShape"], $arAdditionalProperties,
			$arAdditionalExtendedProperties
		);

		$this->Connect();
		$response = $this->Send($request);
		$this->Disconnect();

		if (is_null($response))
		{
			return null;
		}

		if ($this->ParseError($response))
			return null;

		$arResultItemsList = array();
		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/FindItemResponse/ResponseMessages/FindItemResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/FindItemResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/FindItemResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arTaskItem = $responseMessage->GetPath("/FindItemResponseMessage/RootFolder/Items/Task");
			foreach ($arTaskItem as $taskItem)
			{
				$arResultItemsList[] = $this->ConvertTaskToArray($taskItem);
			}
		}

		return $arResultItemsList;
	}

	public function GetById($id, $arAdditionalExtendedProperties = array())
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/GetItem");
		$request->AddHeader("Connection", "Keep-Alive");

		$request->CreateGetItemBody($id, "AllProperties", $arAdditionalExtendedProperties);

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

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/GetItemResponse/ResponseMessages/GetItemResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/GetItemResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/GetItemResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arTaskItem = $responseMessage->GetPath("/GetItemResponseMessage/Items/Task");
			foreach ($arTaskItem as $taskItem)
			{
				$arResultItemsList[] = $this->ConvertTaskToArray($taskItem);
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

		$arMapTmp = array("folder_id" => "FolderId", "folderid" => "FolderId", "mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFields, $arMapTmp);
		if (!array_key_exists("FolderId", $arFields))
		{
			$arFields["FolderId"] = "tasks";
		}

		$arFieldsNew = $this->FormatFieldsArray($arFields);

		$arParentFolderId = array("id" => $arFields["FolderId"]);
		if (array_key_exists("Mailbox", $arFields))
		{
			$arParentFolderId["mailbox"] = $arFields["Mailbox"];
		}

		$request->CreateCreateItemBody($arParentFolderId, $arFieldsNew);

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

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/CreateItemResponse/ResponseMessages/CreateItemResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/CreateItemResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/CreateItemResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arTaskItem = $responseMessage->GetPath("/CreateItemResponseMessage/Items/Task");
			foreach ($arTaskItem as $taskItem)
			{
				$arResultItemsList[] = $this->ConvertTaskToArray($taskItem);
			}
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
		{
			return null;
		}

		if ($this->ParseError($response))
		{
			return null;
		}

		$arResultItemsList = array();
		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/UpdateItemResponse/ResponseMessages/UpdateItemResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/UpdateItemResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/UpdateItemResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arTaskItem = $responseMessage->GetPath("/UpdateItemResponseMessage/Items/Task");
			foreach ($arTaskItem as $taskItem)
			{
				$arResultItemsList[] = $this->ConvertTaskToArray($taskItem);
			}
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
		{
			return null;
		}

		if ($this->ParseError($response))
		{
			return null;
		}

		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/DeleteItemResponse/ResponseMessages/DeleteItemResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/DeleteItemResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/DeleteItemResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				return false;
			}
		}

		return true;
	}

	public function GetFoldersList($arFilter)
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/FindFolder");
		$request->AddHeader("Connection", "Keep-Alive");

		$arMapTmp = array("mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);

		$arParentFolderId = array("id" => "tasks");
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

			$arTaskFolder = $responseMessage->GetPath("/FindFolderResponseMessage/RootFolder/Folders/TasksFolder");
			foreach ($arTaskFolder as $taskFolder)
			{
				$arResultFoldersList[] = $this->ConvertTaskFolderToArray($taskFolder);
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

		$arResultFoldersList = array();
		$xmlDoc = $response->GetBodyXml();

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

			$arTaskFolder = $responseMessage->GetPath("/GetFolderResponseMessage/Folders/TasksFolder");
			foreach ($arTaskFolder as $taskFolder)
			{
				$arResultFoldersList[] = $this->ConvertTaskFolderToArray($taskFolder);
			}
		}

		return $arResultFoldersList;
	}

	public function AddFolder($arFields)
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/CreateFolder");
		$request->AddHeader("Connection", "Keep-Alive");

		$arMapTmp = array("mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFields, $arMapTmp);

		$arFieldsNew = $this->FormatFolderFieldsArray($arFields);

		$arParentFolderId = array("id" => "tasks");
		if (array_key_exists("Mailbox", $arFields))
		{
			$arParentFolderId["mailbox"] = $arFields["Mailbox"];
		}

		$request->CreateCreateFolderBody($arParentFolderId, $arFieldsNew);

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

		$arResultFoldersList = array();
		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/CreateFolderResponse/ResponseMessages/CreateFolderResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/CreateFolderResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/CreateFolderResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arTaskFolder = $responseMessage->GetPath("/CreateFolderResponseMessage/Folders/TasksFolder");
			foreach ($arTaskFolder as $taskFolder)
			{
				$arResultFoldersList[] = $this->ConvertTaskFolderToArray($taskFolder);
			}
		}

		return $arResultFoldersList;
	}

	public function UpdateFolder($id, $arFields)
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
		{
			return null;
		}

		if ($this->ParseError($response))
		{
			return null;
		}

		$arResultFoldersList = array();
		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/UpdateFolderResponse/ResponseMessages/UpdateFolderResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/UpdateFolderResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/UpdateFolderResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				continue;
			}

			$arTaskFolder = $responseMessage->GetPath("/UpdateFolderResponseMessage/Folders/TasksFolder");
			foreach ($arTaskFolder as $taskFolder)
			{
				$arResultFoldersList[] = $this->ConvertTaskFolderToArray($taskFolder);
			}
		}

		return $arResultFoldersList;
	}

	public function DeleteFolder($id)
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
		{
			return null;
		}

		if ($this->ParseError($response))
		{
			return null;
		}

		$xmlDoc = $response->GetBodyXml();

		$arResponseMessage = $xmlDoc->GetPath("/Envelope/Body/DeleteFolderResponse/ResponseMessages/DeleteFolderResponseMessage");
		foreach ($arResponseMessage as $responseMessage)
		{
			$arResponseCode = $responseMessage->GetPath("/DeleteFolderResponseMessage/ResponseCode");
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
				$arMessageText = $responseMessage->GetPath("/DeleteFolderResponseMessage/MessageText");
				$messageText = "Error";
				if (!empty($arMessageText))
				{
					$messageText = $arMessageText[0]->GetContent();
				}

				$this->AddError(!is_null($responseCode) ? $this->Encode($responseCode) : $this->Encode($responseClass), $this->Encode($messageText));
				return false;
			}
		}

		return true;
	}

	private function FormatFieldsArray($arFields)
	{
		$arFieldsNew = array();

		$arMap = array(
			"XML_ID" => "Id",
			"SUBJECT" => "Subject",
			"BODY" => "Body",
			"BODY_TYPE" => "BodyType",
			"DATE_CREATE" => "DateTimeCreated",
			"IMPORTANCE" => "Importance",
			'GUID' => array(	// Extended field. Must be represented as array
				'PropertyName' => 'BX_TASKS_GUID',
				'PropertyType' => 'String',
				'Value'        => 'not inited yet'	// will be initialized later
			),
			'SERIALIZED_DATA' => array(	// Extended field. Must be represented as array
				'PropertyName' => 'BX_TASKS_SERIALIZED_DATA',
				'PropertyType' => 'String',
				'Value'        => 'not inited yet'	// will be initialized later
			),
			"ACTUAL_WORK" => "ActualWork",
			"BILLING_INFORMATION" => "BillingInformation",
			"MILEAGE" => "Mileage",
			"START_DATE" => "StartDate",
			"DUE_DATE" => "DueDate",
			"IS_COMPLETE" => "IsComplete",
			"PERCENT_COMPLETE" => "PercentComplete",
			"STATUS" => "Status",
			//"STATUS_DESCRIPTION" => "StatusDescription",
			"TOTAL_WORK" => "TotalWork",
			"OWNER" => "Owner",
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
			{
				continue;
			}

			$newKey = $arMap[$key];
			if (is_array($newKey))
			{
				$arFieldsNew[$key] = $newKey;
				$arFieldsNew[$key]['Value'] = base64_encode($this->Decode($value));
			}
			elseif (in_array($newKey, array("DateTimeCreated", "StartDate", "DueDate")))
			{
				$arFieldsNew[$newKey] = date("c", MakeTimeStamp($value));
			}
			elseif (in_array($newKey, array("RecurringStartDate", "RecurringEndDate")))
			{
				$arFieldsNew[$newKey] = date("Y-m-d\Z", MakeTimeStamp($value));
			}
			elseif (
				$this->FormatStandartFieldsArray($newKey, $value, $arFieldsNew)
				|| $this->FormatRecurrenceFieldsArray($newKey, $value, $arFieldsNew)
			)
			{

			}
			else
			{
				$arFieldsNew[$newKey] = $this->Decode($value);
			}
		}

		if (isset($arFieldsNew["ReminderMinutesBeforeStart"]) && (int)$arFieldsNew["ReminderMinutesBeforeStart"])
		{
			$arFieldsNew["ReminderMinutesBeforeStart"] = (int)$arFieldsNew["ReminderMinutesBeforeStart"];
			$arFieldsNew["ReminderIsSet"] = true;
		}

		return $arFieldsNew;
	}

	private function FormatFolderFieldsArray($arFields)
	{
		$arFieldsNew = array();

		foreach ($arFields as $key => $value)
		{
			if ($key == "NAME")
			{
				$arFieldsNew["DisplayName"] = $this->Decode($value);
			}
		}

		return $arFieldsNew;
	}

	private function ConvertTaskToArray($taskItem)
	{
		$arResultItem = array();

		$arItemId = $taskItem->GetPath("/Task/ItemId");
		if (!empty($arItemId))
		{
			$arResultItem["XML_ID"] = $arItemId[0]->GetAttribute("Id");
			$arResultItem["MODIFICATION_LABEL"] = $arItemId[0]->GetAttribute("ChangeKey");
		}

		$arSubject = $taskItem->GetPath("/Task/Subject");
		if (!empty($arSubject))
		{
			$arResultItem["SUBJECT"] = $this->Encode($arSubject[0]->GetContent());
		}

		$arBody = $taskItem->GetPath("/Task/Body");
		if (!empty($arBody))
		{
			$arResultItem["BODY"] = $this->Encode($arBody[0]->GetContent());
			$arResultItem["BODY_TYPE"] = $arBody[0]->GetAttribute("BodyType");
		}

		$arDateTimeCreated = $taskItem->GetPath("/Task/DateTimeCreated");
		if (!empty($arDateTimeCreated))
		{
			$arResultItem["DATE_CREATE"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arDateTimeCreated[0]->GetContent());
		}

		$arImportance = $taskItem->GetPath("/Task/Importance");
		if (!empty($arImportance))
		{
			$arResultItem["IMPORTANCE"] = $arImportance[0]->GetContent();
		}

		$arReminderIsSet = $taskItem->GetPath("/Task/ReminderIsSet");
		if (!empty($arReminderIsSet) && ($arReminderIsSet[0]->GetContent() === "true"))
		{
			$arReminderMinutesBeforeStart = $taskItem->GetPath("/Task/ReminderMinutesBeforeStart");
			if (!empty($arReminderMinutesBeforeStart))
			{
				$arResultItem["REMINDER_MINUTES_BEFORE_START"] = $arReminderMinutesBeforeStart[0]->GetContent();
			}
		}

		$arActualWork = $taskItem->GetPath("/Task/ActualWork");
		if (!empty($arActualWork))
		{
			$arResultItem["ACTUAL_WORK"] = $arActualWork[0]->GetContent();
		}

		$arBillingInformation = $taskItem->GetPath("/Task/BillingInformation");
		if (!empty($arBillingInformation))
		{
			$arResultItem["BILLING_INFORMATION"] = $arBillingInformation[0]->GetContent();
		}

		$arMileage = $taskItem->GetPath("/Task/Mileage");
		if (!empty($arMileage))
		{
			$arResultItem["MILEAGE"] = $arMileage[0]->GetContent();
		}

		$arStartDate = $taskItem->GetPath("/Task/StartDate");
		if (!empty($arStartDate))
		{
			$arResultItem["START_DATE"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arStartDate[0]->GetContent());
		}

		$arDueDate = $taskItem->GetPath("/Task/DueDate");
		if (!empty($arDueDate))
		{
			$arResultItem["DUE_DATE"] = CDavICalendarTimeZone::GetFormattedServerDateTime($arDueDate[0]->GetContent());
		}

		$arIsComplete = $taskItem->GetPath("/Task/IsComplete");
		if (!empty($arIsComplete))
		{
			$arResultItem["IS_COMPLETE"] = (($arIsComplete[0]->GetContent() === "true") ? true : false);
		}

		$arIsRecurring = $taskItem->GetPath("/Task/IsRecurring");
		if (!empty($arIsRecurring))
		{
			$arResultItem["IS_RECURRING"] = (($arIsRecurring[0]->GetContent() === "true") ? true : false);
		}

		$arRecurrence = $taskItem->GetPath("/Task/Recurrence");
		if (!empty($arRecurrence))
		{
			$ar = $this->ConvertRecurrenceToArray($arRecurrence[0]);
			if (!empty($ar))
			{
				$arResultItem = array_merge($arResultItem, $ar);
				$arResultItem["IS_RECURRING"] = true;
			}
		}

		$arPercentComplete = $taskItem->GetPath("/Task/PercentComplete");
		if (!empty($arPercentComplete))
		{
			$arResultItem["PERCENT_COMPLETE"] = $arPercentComplete[0]->GetContent();
		}

		$arStatus = $taskItem->GetPath("/Task/Status");
		if (!empty($arStatus))
		{
			$arResultItem["STATUS"] = $arStatus[0]->GetContent();
		}

		$arExtendedProperty = $taskItem->GetPath("/Task/ExtendedProperty");
		$extendedPropertiesCount = count($arExtendedProperty);
		for ($i = 0; $i < $extendedPropertiesCount; $i++)
		{
			$arTmp = $arExtendedProperty[$i]->GetContent();

			$propertyName  = $arTmp[0]->GetAttribute('PropertyName');
			$propertyValue = $arTmp[1]->GetContent();

			$arResultItem['ExtendedProperty'][$i] = array(
				'Name'  => $propertyName,
				'Value' => $this->Encode(base64_decode($propertyValue))
			);
		}

		$arStatusDescription = $taskItem->GetPath("/Task/StatusDescription");
		if (!empty($arStatusDescription))
		{
			$arResultItem["STATUS_DESCRIPTION"] = $arStatusDescription[0]->GetContent();
		}

		$arTotalWork = $taskItem->GetPath("/Task/TotalWork");
		if (!empty($arTotalWork))
		{
			$arResultItem["TOTAL_WORK"] = $arTotalWork[0]->GetContent();
		}

		$arOwner = $taskItem->GetPath("/Task/Owner");
		if (!empty($arOwner))
		{
			$arResultItem["OWNER"] = $this->Encode($arOwner[0]->GetContent());
		}

		return $arResultItem;
	}

	private function ConvertTaskFolderToArray($calendarFolder)
	{
		$arResultFolder = array();

		$arFolderId = $calendarFolder->GetPath("/TasksFolder/FolderId");
		if (!empty($arFolderId))
		{
			$arResultFolder["XML_ID"] = $arFolderId[0]->GetAttribute("Id");
			$arResultFolder["MODIFICATION_LABEL"] = $arFolderId[0]->GetAttribute("ChangeKey");
		}

		$arDisplayName = $calendarFolder->GetPath("/TasksFolder/DisplayName");
		if (!empty($arDisplayName))
		{
			$arResultFolder["NAME"] = $this->Encode($arDisplayName[0]->GetContent());
		}

		$arTotalCount = $calendarFolder->GetPath("/TasksFolder/TotalCount");
		if (!empty($arTotalCount))
		{
			$arResultFolder["TOTAL_COUNT"] = $arTotalCount[0]->GetContent();
		}

		$arChildFolderCount = $calendarFolder->GetPath("/TasksFolder/ChildFolderCount");
		if (!empty($arChildFolderCount))
		{
			$arResultFolder["CHILD_FOLDER_COUNT"] = $arChildFolderCount[0]->GetContent();
		}

		return $arResultFolder;
	}

	public function CreateItemBody($arFields)
	{
		$itemBody  = "    <Task xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$itemBody .= "     <ItemClass>IPM.Task</ItemClass>\r\n";

		$arMap = array_merge(self::$arMapItem, self::$arMapExtendedFields, self::$arMapTask);
		foreach ($arMap as $key)
		{
			if (!array_key_exists($key, $arFields))
			{
				continue;
			}

			$value = $arFields[$key];

			$itemBody .= $this->CreateUpdateField($key, $value, $arFields);
		}

		$itemBody .= "    </Task>\r\n";

		return $itemBody;
	}

	public function UpdateItemAttributes()
	{
		return "ConflictResolution=\"AlwaysOverwrite\"";
	}

	public function UpdateItemBody($arFields)
	{
		$itemBody = "";

		$arMap = array_merge(self::$arMapItem, self::$arMapExtendedFields, self::$arMapTask);
		foreach ($arMap as $key)
		{
			if (!array_key_exists($key, $arFields))
			{
				continue;
			}

			$value = $arFields[$key];
			$fieldUri = (in_array($key, self::$arMapTask) ? "task" : "item").":".htmlspecialcharsbx($key);

			if (is_null($value))
			{
				//$itemBody .= "      <DeleteItemField><FieldURI FieldURI=\"".$fieldUri."\"/></DeleteItemField>\r\n";
			}
			else
			{
				$itemBody .= "      <SetItemField>\r\n";

				if (is_array($value))	// Extended fields are represented as arrays
				{
					$itemBody .= '       <ExtendedFieldURI DistinguishedPropertySetId="PublicStrings" '
								. 'PropertyName="' . $value['PropertyName'] . '" '
								. 'PropertyType="' . $value['PropertyType'] . '"/>' . "\r\n";
				}
				else
					$itemBody .= "       <FieldURI FieldURI=\"".$fieldUri."\"/>\r\n";

				$itemBody .= "       <Task>\r\n";
				$itemBody .= $this->CreateUpdateField($key, $value, $arFields);
				$itemBody .= "       </Task>\r\n";
				$itemBody .= "      </SetItemField>\r\n";
			}
		}

		return $itemBody;
	}

	private function CreateUpdateField($key, $value, &$arFields)
	{
		$itemBody = "";

		if ($key === "Body")
		{
			$itemBody .= "     <Body";
			if (array_key_exists("BodyType", $arFields))
			{
				$itemBody .= " BodyType=\"" . (mb_strtolower($arFields["BodyType"]) == "html" ? "HTML" : "Text") . "\"";
			}
			$itemBody .= ">".htmlspecialcharsbx($value)."</Body>\r\n";
		}
		elseif ($key === "Recurrence")
		{
			$ar = array("MONTHLY_ABSOLUTE" => "AbsoluteMonthlyRecurrence", "MONTHLY_RELATIVE" => "RelativeMonthlyRecurrence", "YEARLY_ABSOLUTE" => "AbsoluteYearlyRecurrence", "YEARLY_RELATIVE" => "RelativeYearlyRecurrence", "WEEKLY" => "WeeklyRecurrence", "DAILY" => "DailyRecurrence");

			if (isset($arFields["RecurringType"]) && array_key_exists($arFields["RecurringType"], $ar))
			{
				$rnode = $ar[$arFields["RecurringType"]];

				$itemBody .= "     <Recurrence>\r\n";
				$itemBody .= "      <".$rnode.">\r\n";

				if ($arFields["RecurringType"] === "MONTHLY_ABSOLUTE")
				{
					if (isset($arFields["RecurringInterval"]))
					{
						$itemBody .= "       <Interval>"
							. htmlspecialcharsbx($arFields["RecurringInterval"])
							. "</Interval>\r\n";
					}
					if (isset($arFields["RecurringDayOfMonth"]))
					{
						$itemBody .= "       <DayOfMonth>"
							. htmlspecialcharsbx($arFields["RecurringDayOfMonth"])
							. "</DayOfMonth>\r\n";
					}
				}
				elseif ($arFields["RecurringType"] === "MONTHLY_RELATIVE")
				{
					if (isset($arFields["RecurringInterval"]))
					{
						$itemBody .= "       <Interval>"
							. htmlspecialcharsbx($arFields["RecurringInterval"])
							. "</Interval>\r\n";
					}
					if (isset($arFields["RecurringDaysOfWeek"]))
					{
						$itemBody .= "       <DaysOfWeek>"
							. htmlspecialcharsbx($arFields["RecurringDaysOfWeek"])
							. "</DaysOfWeek>\r\n";
					}
					if (isset($arFields["RecurringDayOfWeekIndex"]))
					{
						$itemBody .= "       <DayOfWeekIndex>"
							. htmlspecialcharsbx($arFields["RecurringDayOfWeekIndex"])
							. "</DayOfWeekIndex>\r\n";
					}
				}
				elseif ($arFields["RecurringType"] === "YEARLY_ABSOLUTE")
				{
					if (isset($arFields["RecurringDayOfMonth"]))
					{
						$itemBody .= "       <DayOfMonth>"
							. htmlspecialcharsbx($arFields["RecurringDayOfMonth"])
							. "</DayOfMonth>\r\n";
					}
					if (isset($arFields["RecurringMonth"]))
					{
						$itemBody .= "       <Month>"
							. htmlspecialcharsbx($arFields["RecurringMonth"])
							. "</Month>\r\n";
					}
				}
				elseif ($arFields["RecurringType"] === "YEARLY_RELATIVE")
				{
					if (isset($arFields["RecurringDaysOfWeek"]))
					{
						$itemBody .= "       <DaysOfWeek>"
							. htmlspecialcharsbx($arFields["RecurringDaysOfWeek"])
							. "</DaysOfWeek>\r\n";
					}
					if (isset($arFields["RecurringDayOfWeekIndex"]))
					{
						$itemBody .= "       <DayOfWeekIndex>"
							. htmlspecialcharsbx($arFields["RecurringDayOfWeekIndex"])
							. "</DayOfWeekIndex>\r\n";
					}
					if (isset($arFields["RecurringMonth"]))
					{
						$itemBody .= "       <Month>"
							. htmlspecialcharsbx($arFields["RecurringMonth"])
							. "</Month>\r\n";
					}
				}
				elseif ($arFields["RecurringType"] === "WEEKLY")
				{
					if (isset($arFields["RecurringInterval"]))
					{
						$itemBody .= "       <Interval>"
							. htmlspecialcharsbx($arFields["RecurringInterval"])
							. "</Interval>\r\n";
					}
					if (isset($arFields["RecurringDaysOfWeek"]))
					{
						$itemBody .= "       <DaysOfWeek>"
							. htmlspecialcharsbx($arFields["RecurringDaysOfWeek"])
							. "</DaysOfWeek>\r\n";
					}
				}
				elseif ($arFields["RecurringType"] === "DAILY")
				{
					if (isset($arFields["RecurringInterval"]))
					{
						$itemBody .= "       <Interval>"
							. htmlspecialcharsbx($arFields["RecurringInterval"])
							. "</Interval>\r\n";
					}
				}

				$itemBody .= "      </".$rnode.">\r\n";

				if (isset($arFields["RecurringEndDate"]))
				{
					$itemBody .= "      <EndDateRecurrence>\r\n";
					if (isset($arFields["RecurringStartDate"]))
					{
						$itemBody .= "      <StartDate>"
							. htmlspecialcharsbx($arFields["RecurringStartDate"])
							. "</StartDate>\r\n";
					}
					$itemBody .= "      <EndDate>".htmlspecialcharsbx($arFields["RecurringEndDate"])."</EndDate>\r\n";
					$itemBody .= "      </EndDateRecurrence>\r\n";
				}
				elseif (isset($arFields["RecurringNumberOfOccurrences"]))
				{
					$itemBody .= "      <NumberedRecurrence>\r\n";
					if (isset($arFields["RecurringStartDate"]))
					{
						$itemBody .= "      <StartDate>"
							. htmlspecialcharsbx($arFields["RecurringStartDate"])
							. "</StartDate>\r\n";
					}
					$itemBody .= "      <NumberOfOccurrences>".htmlspecialcharsbx($arFields["RecurringNumberOfOccurrences"])."</NumberOfOccurrences>\r\n";
					$itemBody .= "      </NumberedRecurrence>\r\n";
				}
				else
				{
					$itemBody .= "      <NoEndRecurrence>\r\n";
					if (isset($arFields["RecurringStartDate"]))
					{
						$itemBody .= "      <StartDate>"
							. htmlspecialcharsbx($arFields["RecurringStartDate"])
							. "</StartDate>\r\n";
					}
					$itemBody .= "      </NoEndRecurrence>\r\n";
				}

				$itemBody .= "     </Recurrence>\r\n";
			}
		}
		elseif (is_array($value))	// Extended fields represented as arrays
		{
			$propertyName = $value['PropertyName'];
			$propertyType = $value['PropertyType'];
			$propertyValue = $value['Value'];

			$itemBody .= '     <ExtendedProperty>' . "\r\n";
			$itemBody .= '      <ExtendedFieldURI '
				. 'DistinguishedPropertySetId="PublicStrings" '
				. 'PropertyName="' . $propertyName . '" '
				. 'PropertyType="' . $propertyType . '" /> ' . "\r\n"
				. '      <Value>' . htmlspecialcharsbx($propertyValue) . '</Value>' . "\r\n";
			$itemBody .= '     </ExtendedProperty>' . "\r\n";
		}
		else
		{
			$itemBody .= "     <".htmlspecialcharsbx($key).">";
			if (is_bool($value))
			{
				$itemBody .= ($value ? "true" : "false");
			}
			else
			{
				$itemBody .= htmlspecialcharsbx($value);
			}
			$itemBody .= "</".htmlspecialcharsbx($key).">\r\n";
		}

		return $itemBody;
	}

	public function CreateFolderBody($arFields)
	{
		$itemBody  = "    <TasksFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		//$itemBody .= "     <FolderClass>IPF.Task</FolderClass>\r\n";
		$itemBody .= "     <DisplayName>".htmlspecialcharsbx($arFields["DisplayName"])."</DisplayName>\r\n";
		$itemBody .= "    </TasksFolder>\r\n";
		return $itemBody;
	}

	public function UpdateFolderBody($arFields)
	{
		$itemBody = "";

		$itemBody .= "      <SetFolderField>\r\n";
		$itemBody .= "       <FieldURI FieldURI=\"folder:DisplayName\"/>\r\n";
		$itemBody .= "       <TasksFolder>\r\n";
		$itemBody .= "        <DisplayName>".htmlspecialcharsbx($arFields["DisplayName"])."</DisplayName>\r\n";
		$itemBody .= "       </TasksFolder>\r\n";
		$itemBody .= "      </SetFolderField>\r\n";

		return $itemBody;
	}

	private static function InitUserEntity()
	{
		if (!CModule::IncludeModule("tasks"))
		{
			return;
		}

		$arRequiredFields = array(
			"UF_BXDAVEX_TSKSYNC" => array(
				"USER_TYPE_ID" => "datetime",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Tasks sync date",
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

		if (empty($exchangeServer)/* || (COption::GetOptionString("dav", "agent_tasks", "N") != "Y")*/)
		{
			CAgent::RemoveAgent("CDavExchangeTasks::DataSync();", "dav");
			COption::SetOptionString("dav", "agent_tasks", "N");
			return "";
		}

		$exchange = new CDavExchangeTasks($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

		$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
		$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

		self::InitUserEntity();

		$maxNumber = COption::GetOptionString("dav", "users_by_step", "5");
		$index = 0;

		$paramUserId = (int)$paramUserId;
		$arUserFilter = array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false);
		if ($paramUserId > 0)
		{
			$arUserFilter["ID_EQUAL_EXACT"] = $paramUserId;
		}
		if ($exchangeUseLogin === "N")
		{
			$arUserFilter["!UF_BXDAVEX_MAILBOX"] = false;
		}

		$arAdditionalExtendedProperties = array(
			array(
				'DistinguishedPropertySetId' => 'PublicStrings',
				'PropertyName'               => 'BX_TASKS_GUID',
				'PropertyType'               => 'String'
			),
			array(
				'DistinguishedPropertySetId' => 'PublicStrings',
				'PropertyName'               => 'BX_TASKS_SERIALIZED_DATA',
				'PropertyType'               => 'String'
			),
		);

		$dbUserList = CUser::GetList("UF_BXDAVEX_TSKSYNC", "asc", $arUserFilter, array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_TSKSYNC")));
		while ($arUser = $dbUserList->Fetch())
		{
			$index++;
			if ($index > $maxNumber)
			{
				break;
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $arUser["ID"], array("UF_BXDAVEX_TSKSYNC" => ConvertTimeStamp(time(), "FULL")));

			$mailbox = (($exchangeUseLogin === "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
			if (empty($mailbox))
			{
				continue;
			}

			$arFoldersList = $exchange->GetFoldersList(array("mailbox" => $mailbox));
			if ( ! is_array($arFoldersList) )
			{
				if (DAV_CALDAV_DEBUG)
				{
					CDav::WriteToLog('Error during tasks exchange: $exchange->GetFoldersList() returns unexpected result', '');
				}
				continue;
			}

			$arUserFolders = array("tasks" => GetMessage("DAV_EC_TASKS"));
			foreach ($arFoldersList as $value)
			{
				$arUserFolders[$value["XML_ID"]] = $value["NAME"];
			}

			$arUserTaskItems = array();
			$arUserTaskItemsFolder = array();
			foreach ($arUserFolders as $userFolderXmlId => $userFolder)
			{
				$arTaskItemsList = $exchange->GetList(
					array("mailbox" => $mailbox, "FolderId" => $userFolderXmlId),
					array("ItemShape" => "IdOnly"),
					$arAdditionalExtendedProperties
				);
				if ( ! is_array($arTaskItemsList) )
				{
					if (DAV_CALDAV_DEBUG)
					{
						CDav::WriteToLog('Error during tasks exchange: $exchange->GetList() returns unexpected result', '');
					}
					continue;
				}

				foreach ($arTaskItemsList as $value)
				{
					$arUserTaskItems[] = array(
						"XML_ID" => $value["XML_ID"],
						"MODIFICATION_LABEL" => $value["MODIFICATION_LABEL"],
					);
					$arUserTaskItemsFolder[$value["XML_ID"]] = $userFolderXmlId;
				}
			}

			$arModifiedUserTaskItems = CTaskSync::SyncTaskItems("exchange", $arUser["ID"], $arUserTaskItems);

			foreach ($arModifiedUserTaskItems as $value)
			{
				$arModifiedTaskItem = $exchange->GetById($value["XML_ID"], $arAdditionalExtendedProperties);
				if (is_array($arModifiedTaskItem) && !empty($arModifiedTaskItem))
				{
					$arModifiedTaskItem = $arModifiedTaskItem[0];

					$arModifyEventArray = array_merge(
						$arModifiedTaskItem,
						array(
							"ID" => $value["ID"],
							"USER_ID" => $arUser["ID"],
							"FOLDER_ID" => $arUserFolders[$arUserTaskItemsFolder[$arModifiedTaskItem["XML_ID"]]]
						)
					);

					//XML_ID, MODIFICATION_LABEL, SUBJECT, BODY, BODY_TYPE, DATE_CREATE, IMPORTANCE, REMINDER_MINUTES_BEFORE_START, ACTUAL_WORK, BILLING_INFORMATION, MILEAGE, START_DATE, DUE_DATE, IS_COMPLETE, IS_RECURRING, PERCENT_COMPLETE, STATUS, STATUS_DESCRIPTION, TOTAL_WORK, OWNER, RECURRING_TYPE, RECURRING_INTERVAL, RECURRING_DAYOFMONTH, RECURRING_DAYSOFWEEK, RECURRING_DAYOFWEEKINDEX, RECURRING_MONTH, RECURRING_STARTDATE, RECURRING_NUMBEROFOCCURRENCES, RECURRING_ENDDATE

					CTaskSync::SyncModifyTaskItem($arModifyEventArray);
				}
			}
		}

		return "CDavExchangeTasks::DataSync();";
	}

	public static function DoAddItem($userId, $arFields)
	{
		$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
		$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
		$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

		if (empty($exchangeServer))
		{
			return "";
		}

		$exchange = new CDavExchangeTasks($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

		$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
		$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

		self::InitUserEntity();

		$userId = (int)$userId;
		$dbUserList = CUser::GetList(
			"",
			"",
			array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
			array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX"))
		);
		if ($arUser = $dbUserList->Fetch())
		{
			$mailbox = (($exchangeUseLogin === "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
			if (!empty($mailbox))
			{
				$arFields["MAILBOX"] = $mailbox;

				if (isset($arFields["FOLDER_ID"]))
				{
					$arFoldersList = $exchange->GetFoldersList(array("mailbox" => $mailbox));
					$arUserFolders = array(GetMessage("DAV_EC_TASKS") => "tasks");
					foreach ($arFoldersList as $value)
					{
						$arUserFolders[$value["NAME"]] = $value["XML_ID"];
					}

					if (array_key_exists($arFields["FOLDER_ID"], $arUserFolders))
					{
						$arFields["FOLDER_ID"] = $arUserFolders[$arFields["FOLDER_ID"]];
					}
					else
					{
						$arFields["FOLDER_ID"] = "tasks";
					}
				}
				else
				{
					$arFields["FOLDER_ID"] = "tasks";
				}

				$arResult = $exchange->Add($arFields);

				if (is_array($arResult) && !empty($arResult))
				{
					return $arResult[0];
				}
			}
		}

		return $exchange->GetErrors();
	}

	public static function DoUpdateItem($userId, $itemXmlId, $itemModificationLabel, $arFields)
	{
		$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
		$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
		$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

		if (empty($exchangeServer))
		{
			return "";
		}

		$exchange = new CDavExchangeTasks($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

		$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
		$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

		self::InitUserEntity();

		$userId = (int)$userId;
		$dbUserList = CUser::GetList(
			"",
			"",
			array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
			array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX"))
		);
		if ($arUser = $dbUserList->Fetch())
		{
			$mailbox = (($exchangeUseLogin === "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
			if (!empty($mailbox))
			{
				$arResult = $exchange->Update(
					array("XML_ID" => $itemXmlId, "MODIFICATION_LABEL" => $itemModificationLabel),
					$arFields
				);

				if (is_array($arResult) && !empty($arResult))
				{
					return $arResult[0];
				}
			}
		}

		return $exchange->GetErrors();
	}

	public static function DoDeleteItem($userId, $itemXmlId)
	{
		$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
		$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
		$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

		if (empty($exchangeServer))
		{
			return "";
		}

		$exchange = new CDavExchangeTasks($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

		$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
		$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

		self::InitUserEntity();

		$userId = (int)$userId;
		$dbUserList = CUser::GetList(
			"",
			"",
			array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false, "ID_EQUAL_EXACT" => $userId),
			array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX"))
		);
		if ($arUser = $dbUserList->Fetch())
		{
			$mailbox = (($exchangeUseLogin === "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
			if (!empty($mailbox))
			{
				$arResult = $exchange->Delete($itemXmlId);
				if ($arResult)
				{
					return $arResult;
				}
			}
		}

		return $exchange->GetErrors();
	}

	public static function IsExchangeEnabled()
	{
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$agentTasks = COption::GetOptionString("dav", "agent_tasks", "N");
		return (!empty($exchangeServer) && ($agentTasks === "Y"));
	}
}

