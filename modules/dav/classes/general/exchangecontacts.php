<?
/*
$e = new CDavExchangeContacts("http", "test-exch2007", 80, 'alex', 'P@$$w0rd');
//$e->Debug();
$r = $e->GetAddressbooksList(array("mailbox" => "anti_bug@test.local"));
$r = $e->GetAddressbookById("AQATAGFud...");
$r = $e->AddAddressbook(array("NAME" => "AbFolder", "mailbox" => "anti_bug@test.local"));
$r = $e->UpdateAddressbook(array("XML_ID" => "AQATAGFud...", "MODIFICATION_LABEL" => "AwAAA..."), array("NAME" => "AbFolder1"));
$r = $e->DeleteAddressbook("AQATAGFud...");

$r = $e->GetList(array("mailbox" => "anti_bug@test.local"));
$r = $e->GetList(
	array("Mailbox" => "anti_bug@test.local", "AddressbookId" => "JTJFHDTrs..."),
	array("ItemShape" => "IdOnly")
);
$r = $e->GetById("AAATAGFud...");

$arFields = array(
	"MAILBOX" => "anti_bug@test.local",
	"ADDRESSBOOK_ID" => "JTJFHDTrs...",
	"NAME" => "MyName",
	"LAST_NAME" => "MyLastName",
	"SECOND_NAME" => "MySecondName",
	"EMAIL" => "vas2@sfbdsgdf.df",
	"WORK_POSITION" => "Programmer",
	"WORK_ZIP" => "236001",
	"WORK_CITY" => "Kaliningrad",
	"WORK_STREET" => "Kirov str., 261",
	"PERSONAL_PHONE" => "6547646546",
	"PERSONAL_MOBILE" => "55435656",
	"WORK_PHONE" => "876467343",
	"WORK_FAX" => "345737365",
	"WORK_COMPANY" => "Bitrix",
	"WORK_WWW" => "http://www.1c-bitrix.com",
	"PERSONAL_ICQ" => "535435353",
	"WORK_COUNTRY" => 23,
);
$r = $e->Add($arFields);

$arFields = array(
	"NAME" => "MyName",
	"LAST_NAME" => "MyLastName",
	"SECOND_NAME" => "MySecondName",
	"EMAIL" => "vas2@sfbdsgdf.df",
	"WORK_POSITION" => "Programmer",
	"WORK_ZIP" => "236001",
	"WORK_CITY" => "Kaliningrad",
	"WORK_STREET" => "Kirov str., 261",
	"PERSONAL_PHONE" => "6547646546",
	"PERSONAL_MOBILE" => "55435656",
	"WORK_PHONE" => "876467343",
	"WORK_FAX" => "345737365",
	"WORK_COMPANY" => "Bitrix",
	"WORK_WWW" => "http://www.1c-bitrix.com",
	"PERSONAL_ICQ" => "535435353",
	"WORK_COUNTRY" => 80,
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
class CDavExchangeContacts
	extends CDavExchangeClient
{
	static $arMapItem = array("MimeContent", "ItemId", "ParentFolderId", "ItemClass", "Subject", "Sensitivity", "Body", "Attachments", "DateTimeReceived", "Size", "Categories", "Importance", "InReplyTo", "IsSubmitted", "IsDraft", "IsFromMe", "IsResend", "IsUnmodified", "InternetMessageHeaders", "DateTimeSent", "DateTimeCreated", "ResponseObjects", "ReminderDueBy", "ReminderIsSet", "ReminderMinutesBeforeStart", "DisplayCc", "DisplayTo", "HasAttachments", "ExtendedProperty", "Culture", "EffectiveRights", "LastModifiedName", "LastModifiedTime");
	static $arMapContact = array("FileAs", "FileAsMapping", "DisplayName", "GivenName", "Initials", "MiddleName", "Nickname", "CompleteName", "CompanyName", "EmailAddresses", "PhysicalAddresses", "PhoneNumbers", "AssistantName", "Birthday", "BusinessHomePage", "Children", "Companies", "ContactSource", "Department", "Generation", "ImAddresses", "JobTitle", "Manager", "Mileage", "OfficeLocation", "PostalAddressIndex", "Profession", "SpouseName", "Surname", "WeddingAnniversary");

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

		$arMapTmp = array("addressbook_id" => "AddressbookId", "addressbookid" => "AddressbookId", "mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);
		if (!array_key_exists("AddressbookId", $arFilter))
			$arFilter["AddressbookId"] = "contacts";

		$arMapTmp = array("itemshape" => "ItemShape", "item_shape" => "ItemShape", "additionalproperties" => "AdditionalProperties", "additional_properties" => "AdditionalProperties");
		CDavExchangeClient::NormalizeArray($arMode, $arMapTmp);
		if (!array_key_exists("ItemShape", $arMode))
			$arMode["ItemShape"] = "AllProperties";

		$arParentFolderId = array("id" => $arFilter["AddressbookId"]);
		if (array_key_exists("Mailbox", $arFilter))
			$arParentFolderId["mailbox"] = $arFilter["Mailbox"];

		$request->CreateFindItemBody($arParentFolderId, null, $arMode["ItemShape"], isset($arMode["AdditionalProperties"]) ? $arMode["AdditionalProperties"] : array());

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

			$arContactItem = $responseMessage->GetPath("/FindItemResponseMessage/RootFolder/Items/Contact");
			foreach ($arContactItem as $contactItem)
				$arResultItemsList[] = $this->ConvertContactToArray($contactItem);
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

			$arContactItem = $responseMessage->GetPath("/GetItemResponseMessage/Items/Contact");
			foreach ($arContactItem as $contactItem)
				$arResultItemsList[] = $this->ConvertContactToArray($contactItem);
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

		$arMapTmp = array("addressbook_id" => "AddressbookId", "addressbookid" => "AddressbookId", "mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFields, $arMapTmp);
		if (!array_key_exists("AddressbookId", $arFields))
			$arFields["AddressbookId"] = "contacts";

		$arFieldsNew = $this->FormatFieldsArray($arFields);

		$arParentFolderId = array("id" => $arFields["AddressbookId"]);
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

			$arContactItem = $responseMessage->GetPath("/CreateItemResponseMessage/Items/Contact");
			foreach ($arContactItem as $contactItem)
				$arResultItemsList[] = $this->ConvertContactToArray($contactItem);
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

			$arContactItem = $responseMessage->GetPath("/UpdateItemResponseMessage/Items/Contact");
			foreach ($arContactItem as $contactItem)
				$arResultItemsList[] = $this->ConvertContactToArray($contactItem);
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

	public function GetAddressbooksList($arFilter)
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/FindFolder");
		$request->AddHeader("Connection", "Keep-Alive");

		$arMapTmp = array("mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFilter, $arMapTmp);

		$arParentFolderId = array("id" => "contacts");
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
		$xmlDoc = $response->GetBodyXml();

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

			$arContactFolder = $responseMessage->GetPath("/FindFolderResponseMessage/RootFolder/Folders/ContactsFolder");
			foreach ($arContactFolder as $contactFolder)
				$arResultFoldersList[] = $this->ConvertContactFolderToArray($contactFolder);
		}

		return $arResultFoldersList;
	}

	public function GetAddressbookById($id)
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

			$arContactFolder = $responseMessage->GetPath("/GetFolderResponseMessage/Folders/ContactsFolder");
			foreach ($arContactFolder as $contactFolder)
				$arResultFoldersList[] = $this->ConvertContactFolderToArray($contactFolder);
		}

		return $arResultFoldersList;
	}

	public function AddAddressbook($arFields)
	{
		$this->ClearErrors();

		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/CreateFolder");
		$request->AddHeader("Connection", "Keep-Alive");

		$arMapTmp = array("mailbox" => "Mailbox");
		CDavExchangeClient::NormalizeArray($arFields, $arMapTmp);

		$arFieldsNew = $this->FormatFolderFieldsArray($arFields);

		$arParentFolderId = array("id" => "contacts");
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

			$arContactFolder = $responseMessage->GetPath("/CreateFolderResponseMessage/Folders/ContactsFolder");
			foreach ($arContactFolder as $contactFolder)
				$arResultFoldersList[] = $this->ConvertContactFolderToArray($contactFolder);
		}

		return $arResultFoldersList;
	}

	public function UpdateAddressbook($id, $arFields)
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

			$arContactFolder = $responseMessage->GetPath("/UpdateFolderResponseMessage/Folders/ContactsFolder");
			foreach ($arContactFolder as $contactFolder)
				$arResultFoldersList[] = $this->ConvertContactFolderToArray($contactFolder);
		}

		return $arResultFoldersList;
	}

	public function DeleteAddressbook($id)
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
		$arFieldsNew = array();

		$arMap = array(
			"NAME" => "GivenName",
			"LAST_NAME" => "Surname",
			"SECOND_NAME" => "MiddleName",
			"EMAIL" => "EmailAddresses",
			//"XML_ID" => "Id",
			"WORK_POSITION" => "JobTitle",
			"PERSONAL_ZIP" => "PhysicalAddresses_Home_PostalCode",
			"PERSONAL_STATE" => "PhysicalAddresses_Home_State",
			"PERSONAL_CITY" => "PhysicalAddresses_Home_City",
			"PERSONAL_STREET" => "PhysicalAddresses_Home_Street",
			"WORK_ZIP" => "PhysicalAddresses_Business_PostalCode",
			"WORK_STATE" => "PhysicalAddresses_Business_State",
			"WORK_CITY" => "PhysicalAddresses_Business_City",
			"WORK_STREET" => "PhysicalAddresses_Business_Street",
			"PERSONAL_PHONE" => "PhoneNumbers_HomePhone",
			"PERSONAL_FAX" => "PhoneNumbers_HomePhone2",
			"PERSONAL_MOBILE" => "PhoneNumbers_MobilePhone",
			"PERSONAL_PAGER" => "PhoneNumbers_Pager",
			"WORK_PHONE" => "PhoneNumbers_BusinessPhone",
			"WORK_FAX" => "PhoneNumbers_BusinessPhone2",
			"WORK_PAGER" => "PhoneNumbers_OtherTelephone",
			"WORK_COMPANY" => "CompanyName",
			"WORK_WWW" => "BusinessHomePage",
			"PERSONAL_ICQ" => "ImAddresses",
			"WORK_DEPARTMENT" => "Department",
		);

		foreach ($arFields as $key => $value)
		{
			if (array_key_exists($key, $arMap))
			{
				$arFieldsNew[$arMap[$key]] = $this->Decode($value);
			}
			else
			{
				switch ($key)
				{
					case "PERSONAL_COUNTRY":
						$arFieldsNew["PhysicalAddresses_Home_CountryOrRegion"] = $this->Decode(GetCountryByID($value));
						break;
					case "WORK_COUNTRY":
						$arFieldsNew["PhysicalAddresses_Business_CountryOrRegion"] = $this->Decode(GetCountryByID($value));
						break;
				}
			}
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

	private function ConvertContactToArray($contactItem)
	{
		$arResultItem = array();

		$arItemId = $contactItem->GetPath("/Contact/ItemId");
		if (count($arItemId) > 0)
		{
			$arResultItem["XML_ID"] = $arItemId[0]->GetAttribute("Id");
			$arResultItem["MODIFICATION_LABEL"] = $arItemId[0]->GetAttribute("ChangeKey");
		}

		$arGivenName = $contactItem->GetPath("/Contact/GivenName");
		if (count($arGivenName) > 0)
			$arResultItem["NAME"] = $this->Encode($arGivenName[0]->GetContent());

		$arMiddleName = $contactItem->GetPath("/Contact/MiddleName");
		if (count($arMiddleName) > 0)
			$arResultItem["SECOND_NAME"] = $this->Encode($arMiddleName[0]->GetContent());

		$arSurname = $contactItem->GetPath("/Contact/Surname");
		if (count($arSurname) > 0)
			$arResultItem["LAST_NAME"] = $this->Encode($arSurname[0]->GetContent());

		$arCompanyName = $contactItem->GetPath("/Contact/CompanyName");
		if (count($arCompanyName) > 0)
			$arResultItem["WORK_COMPANY"] = $this->Encode($arCompanyName[0]->GetContent());

		$arDepartment = $contactItem->GetPath("/Contact/Department");
		if (count($arDepartment) > 0)
			$arResultItem["WORK_DEPARTMENT"] = $this->Encode($arDepartment[0]->GetContent());

		$arJobTitle = $contactItem->GetPath("/Contact/JobTitle");
		if (count($arJobTitle) > 0)
			$arResultItem["WORK_POSITION"] = $this->Encode($arJobTitle[0]->GetContent());


		$arPhysicalAddresses = $contactItem->GetPath("/Contact/PhysicalAddresses/Entry");
		foreach ($arPhysicalAddresses as $physicalAddress)
		{
			$entryKey = strtolower($physicalAddress->GetAttribute("Key"));

			if ($entryKey == "business")
				$prefix = "WORK";
			elseif ($entryKey == "home")
				$prefix = "PERSONAL";
			elseif (!isset($arResultItem["PERSONAL_STREET"]))
				$prefix = "PERSONAL";

			$arStreet = $physicalAddress->GetPath("/Entry/Street");
			if (count($arStreet) > 0)
				$arResultItem[$prefix."_STREET"] = $this->Encode($arStreet[0]->GetContent());

			$arCity = $physicalAddress->GetPath("/Entry/City");
			if (count($arCity) > 0)
				$arResultItem[$prefix."_CITY"] = $this->Encode($arCity[0]->GetContent());

			$arState = $physicalAddress->GetPath("/Entry/State");
			if (count($arState) > 0)
				$arResultItem[$prefix."_STATE"] = $this->Encode($arState[0]->GetContent());

			$arCountryOrRegion = $physicalAddress->GetPath("/Entry/CountryOrRegion");
			if (count($arCountryOrRegion) > 0)
			{
				$country = $this->Encode($arCountryOrRegion[0]->GetContent());
				$ar = GetCountryArray();
				$i = array_search($country, $ar["reference"]);
				if ($i !== false)
					$arResultItem[$prefix."_COUNTRY"] = $ar["reference"][$i];
			}

			$arPostalCode = $physicalAddress->GetPath("/Entry/PostalCode");
			if (count($arPostalCode) > 0)
				$arResultItem[$prefix."_ZIP"] = $this->Encode($arPostalCode[0]->GetContent());
		}

		$arPhoneNumbers = $contactItem->GetPath("/Contact/PhoneNumbers/Entry");
		foreach ($arPhoneNumbers as $phoneNumber)
		{
			$entryKey = strtolower($phoneNumber->GetAttribute("Key"));
			$v = $phoneNumber->GetContent();

			if ($entryKey == "businessphone")
				$arResultItem["WORK_PHONE"] = $v;
			elseif ($entryKey == "homephone")
				$arResultItem["PERSONAL_PHONE"] = $v;
			elseif ($entryKey == "homephone2")
				$arResultItem["PERSONAL_FAX"] = $v;
			elseif ($entryKey == "mobilephone")
				$arResultItem["PERSONAL_MOBILE"] = $v;
			elseif ($entryKey == "pager")
				$arResultItem["PERSONAL_PAGER"] = $v;
			elseif ($entryKey == "businessphone2")
				$arResultItem["WORK_FAX"] = $v;
			else
			{
				if (!is_null($v) && !empty($v))
					$arResultItem["WORK_PAGER"] = $v;
			}
		}

		$arEmailAddresses = $contactItem->GetPath("/Contact/EmailAddresses/Entry");
		foreach ($arEmailAddresses as $emailAddress)
		{
			$v = $emailAddress->GetContent();
			if (!is_null($v) && !empty($v) && (!isset($arResultItem["EMAIL"]) || empty($arResultItem["EMAIL"])))
				$arResultItem["EMAIL"] = $v;
		}

		$arImAddresses = $contactItem->GetPath("/Contact/ImAddresses/Entry");
		foreach ($arImAddresses as $imAddress)
		{
			$v = $imAddress->GetContent();
			if (!is_null($v) && !empty($v) && (!isset($arResultItem["PERSONAL_ICQ"]) || empty($arResultItem["PERSONAL_ICQ"])))
				$arResultItem["PERSONAL_ICQ"] = $v;
		}

		$arBusinessHomePage = $contactItem->GetPath("/Contact/BusinessHomePage");
		if (count($arBusinessHomePage) > 0)
			$arResultItem["WORK_WWW"] = $this->Encode($arBusinessHomePage[0]->GetContent());

		return $arResultItem;
	}

	private function ConvertContactFolderToArray($calendarFolder)
	{
		$arResultFolder = array();

		$arFolderId = $calendarFolder->GetPath("/ContactsFolder/FolderId");
		if (count($arFolderId) > 0)
		{
			$arResultFolder["XML_ID"] = $arFolderId[0]->GetAttribute("Id");
			$arResultFolder["MODIFICATION_LABEL"] = $arFolderId[0]->GetAttribute("ChangeKey");
		}

		$arDisplayName = $calendarFolder->GetPath("/ContactsFolder/DisplayName");
		if (count($arDisplayName) > 0)
			$arResultFolder["NAME"] = $this->Encode($arDisplayName[0]->GetContent());

		$arTotalCount = $calendarFolder->GetPath("/ContactsFolder/TotalCount");
		if (count($arTotalCount) > 0)
			$arResultFolder["TOTAL_COUNT"] = $arTotalCount[0]->GetContent();

		$arChildFolderCount = $calendarFolder->GetPath("/ContactsFolder/ChildFolderCount");
		if (count($arChildFolderCount) > 0)
			$arResultFolder["CHILD_FOLDER_COUNT"] = $arChildFolderCount[0]->GetContent();

		return $arResultFolder;
	}

	public function CreateItemBody($arFields)
	{
		$itemBody  = "    <Contact xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$itemBody .= "     <ItemClass>IPM.Contact</ItemClass>\r\n";

		$arMap = array_merge(self::$arMapItem, self::$arMapContact);
		foreach ($arMap as $key)
		{
			if (!array_key_exists($key, $arFields) && !in_array($key, array("PhysicalAddresses", "PhoneNumbers", "FileAsMapping", "FileAs")))
				continue;

			$value = $arFields[$key];

			if ($key == "FileAsMapping")
			{
				$itemBody .= "     <FileAsMapping>LastCommaFirst</FileAsMapping>\r\n";
			}
			elseif ($key == "FileAs")
			{
				$v = $arFields["Surname"];
				if (strlen($v) > 0 && (strlen($arFields["GivenName"]) > 0 || strlen($arFields["MiddleName"]) > 0))
					$v .= ", ";
				$v .= $arFields["GivenName"];
				if (strlen($v) > 0 && strlen($arFields["MiddleName"]) > 0)
					$v .= " ";
				$v .= $arFields["MiddleName"];

				$itemBody .= "     <FileAs>".htmlspecialcharsbx($v)."</FileAs>\r\n";
			}
			elseif ($key == "EmailAddresses")
			{
				$itemBody .= "     <EmailAddresses>\r\n";
				$itemBody .= "      <Entry Key=\"EmailAddress1\">".htmlspecialcharsbx($arFields["EmailAddresses"])."</Entry>\r\n";
				$itemBody .= "     </EmailAddresses>\r\n";
			}
			elseif ($key == "PhysicalAddresses")
			{
				$itemBody .= "      <PhysicalAddresses>\r\n";
				$itemBody .= "       <Entry Key=\"Business\">\r\n";
				$itemBody .= "       <Street>".htmlspecialcharsbx($arFields["PhysicalAddresses_Business_Street"])."</Street>\r\n";
				$itemBody .= "       <City>".htmlspecialcharsbx($arFields["PhysicalAddresses_Business_City"])."</City>\r\n";
				$itemBody .= "       <State>".htmlspecialcharsbx($arFields["PhysicalAddresses_Business_State"])."</State>\r\n";
				$itemBody .= "       <CountryOrRegion>".htmlspecialcharsbx($arFields["PhysicalAddresses_Business_CountryOrRegion"])."</CountryOrRegion>\r\n";
				$itemBody .= "       <PostalCode>".htmlspecialcharsbx($arFields["PhysicalAddresses_Business_PostalCode"])."</PostalCode>\r\n";
				$itemBody .= "       </Entry>\r\n";
				$itemBody .= " 	     <Entry Key=\"Home\">\r\n";
				$itemBody .= "       <Street>".htmlspecialcharsbx($arFields["PhysicalAddresses_Home_Street"])."</Street>\r\n";
				$itemBody .= "       <City>".htmlspecialcharsbx($arFields["PhysicalAddresses_Home_City"])."</City>\r\n";
				$itemBody .= "       <State>".htmlspecialcharsbx($arFields["PhysicalAddresses_Home_State"])."</State>\r\n";
				$itemBody .= "       <CountryOrRegion>".htmlspecialcharsbx($arFields["PhysicalAddresses_Home_CountryOrRegion"])."</CountryOrRegion>\r\n";
				$itemBody .= "       <PostalCode>".htmlspecialcharsbx($arFields["PhysicalAddresses_Home_PostalCode"])."</PostalCode>\r\n";
				$itemBody .= "       </Entry>\r\n";
				$itemBody .= "      </PhysicalAddresses>\r\n";
			}
			elseif ($key == "PhoneNumbers")
			{
				$itemBody .= "	    <PhoneNumbers>\r\n";
				$itemBody .= "	     <Entry Key=\"HomePhone\">".htmlspecialcharsbx($arFields["PhoneNumbers_HomePhone"])."</Entry>\r\n";
				$itemBody .= "	     <Entry Key=\"HomePhone2\">".htmlspecialcharsbx($arFields["PhoneNumbers_HomePhone2"])."</Entry>\r\n";
				$itemBody .= "	     <Entry Key=\"MobilePhone\">".htmlspecialcharsbx($arFields["PhoneNumbers_MobilePhone"])."</Entry>\r\n";
				$itemBody .= "	     <Entry Key=\"Pager\">".htmlspecialcharsbx($arFields["PhoneNumbers_Pager"])."</Entry>\r\n";
				$itemBody .= "	     <Entry Key=\"BusinessPhone\">".htmlspecialcharsbx($arFields["PhoneNumbers_BusinessPhone"])."</Entry>\r\n";
				$itemBody .= "	     <Entry Key=\"BusinessPhone2\">".htmlspecialcharsbx($arFields["PhoneNumbers_BusinessPhone2"])."</Entry>\r\n";
				$itemBody .= "	     <Entry Key=\"OtherTelephone\">".htmlspecialcharsbx($arFields["PhoneNumbers_OtherTelephone"])."</Entry>\r\n";
				$itemBody .= "	    </PhoneNumbers>\r\n";
			}
			elseif ($key == "ImAddresses")
			{
				$itemBody .= "     <ImAddresses>\r\n";
				$itemBody .= "      <Entry Key=\"ImAddress1\">".htmlspecialcharsbx($arFields["ImAddresses"])."</Entry>\r\n";
				$itemBody .= "     </ImAddresses>\r\n";
			}
	//		elseif ($key == "ItemId")
	//		{
	//			$itemBody .= "	    <ItemId Id=\"".htmlspecialcharsbx($arFields["Id"])."\" />\r\n";
	//		}
			else
			{
				$itemBody .= "     <".htmlspecialcharsbx($key).">";
				if (is_bool($value))
					$itemBody .= ($value ? "true" : "false");
				else
					$itemBody .= htmlspecialcharsbx($value);
				$itemBody .= "</".htmlspecialcharsbx($key).">\r\n";
			}
		}

		$itemBody .= "    </Contact>\r\n";

		return $itemBody;
	}

	public function UpdateItemAttributes()
	{
		return "ConflictResolution=\"AlwaysOverwrite\"";
	}

	public function UpdateItemBody($arFields)
	{
		$itemBody = "";

		static $arDictionaryURITypeMap = array(
			"EmailAddresses" => array("contacts:EmailAddress", "EmailAddress1", "EmailAddresses"),
			"ImAddresses" => array("contacts:ImAddress", "ImAddress1", "ImAddresses"),
			"PhysicalAddresses_Business_Street" => array("contacts:PhysicalAddress:Street", "Business", "PhysicalAddresses", "Street"),
			"PhysicalAddresses_Business_City" => array("contacts:PhysicalAddress:City", "Business", "PhysicalAddresses", "City"),
			"PhysicalAddresses_Business_State" => array("contacts:PhysicalAddress:State", "Business", "PhysicalAddresses", "State"),
			"PhysicalAddresses_Business_CountryOrRegion" => array("contacts:PhysicalAddress:CountryOrRegion", "Business", "PhysicalAddresses", "CountryOrRegion"),
			"PhysicalAddresses_Business_PostalCode" => array("contacts:PhysicalAddress:PostalCode", "Business", "PhysicalAddresses", "PostalCode"),
			"PhysicalAddresses_Home_Street" => array("contacts:PhysicalAddress:Street", "Home", "PhysicalAddresses", "Street"),
			"PhysicalAddresses_Home_City" => array("contacts:PhysicalAddress:City", "Home", "PhysicalAddresses", "City"),
			"PhysicalAddresses_Home_State" => array("contacts:PhysicalAddress:State", "Home", "PhysicalAddresses", "State"),
			"PhysicalAddresses_Home_CountryOrRegion" => array("contacts:PhysicalAddress:CountryOrRegion", "Home", "PhysicalAddresses", "CountryOrRegion"),
			"PhysicalAddresses_Home_PostalCode" => array("contacts:PhysicalAddress:PostalCode", "Home", "PhysicalAddresses", "PostalCode"),
			"PhoneNumbers_HomePhone" => array("contacts:PhoneNumber", "HomePhone", "PhoneNumbers"),
			"PhoneNumbers_HomePhone2" => array("contacts:PhoneNumber", "HomePhone2", "PhoneNumbers"),
			"PhoneNumbers_MobilePhone" => array("contacts:PhoneNumber", "MobilePhone", "PhoneNumbers"),
			"PhoneNumbers_Pager" => array("contacts:PhoneNumber", "Pager", "PhoneNumbers"),
			"PhoneNumbers_BusinessPhone" => array("contacts:PhoneNumber", "BusinessPhone", "PhoneNumbers"),
			"PhoneNumbers_BusinessPhone2" => array("contacts:PhoneNumber", "BusinessPhone2", "PhoneNumbers"),
			"PhoneNumbers_OtherTelephone" => array("contacts:PhoneNumber", "OtherTelephone", "PhoneNumbers"),
		);

		foreach ($arFields as $key => $value)
		{
			$fieldUri = (in_array($key, self::$arMapContact) ? "contacts" : "item").":".htmlspecialcharsbx($key);
			if (array_key_exists($key, $arDictionaryURITypeMap))
				$fieldUri = $arDictionaryURITypeMap[$key][0];

			if (false && is_null($value))
			{
				$itemBody .= "      <DeleteItemField>";
				if (array_key_exists($key, $arDictionaryURITypeMap))
					$itemBody .= "<IndexedFieldURI FieldURI=\"".$arDictionaryURITypeMap[$key][0]."\" FieldIndex=\"".$arDictionaryURITypeMap[$key][1]."\"/>";
				else
					$itemBody .= "<FieldURI FieldURI=\"".$fieldUri."\"/>";
				$itemBody .= "</DeleteItemField>\r\n";
			}
			else
			{
				$itemBody .= "      <SetItemField>\r\n";
				if (array_key_exists($key, $arDictionaryURITypeMap))
					$itemBody .= "       <IndexedFieldURI FieldURI=\"".$arDictionaryURITypeMap[$key][0]."\" FieldIndex=\"".$arDictionaryURITypeMap[$key][1]."\"/>\r\n";
				else
					$itemBody .= "       <FieldURI FieldURI=\"".$fieldUri."\"/>\r\n";
				$itemBody .= "       <Contact>\r\n";

				if ($key == "Surname")
				{
					$itemBody .= "        <Surname>".htmlspecialcharsbx($value)."</Surname>\r\n";
					$itemBody .= "       </Contact>\r\n";
					$itemBody .= "      </SetItemField>\r\n";

					$itemBody .= "      <SetItemField>\r\n";
					$itemBody .= "       <FieldURI FieldURI=\"contacts:FileAs\"/>\r\n";
					$itemBody .= "       <Contact>\r\n";

					$v = $arFields["Surname"];
					if (strlen($v) > 0 && (strlen($arFields["GivenName"]) > 0 || strlen($arFields["MiddleName"]) > 0))
						$v .= ", ";
					$v .= $arFields["GivenName"];
					if (strlen($v) > 0 && strlen($arFields["MiddleName"]) > 0)
						$v .= " ";
					$v .= $arFields["MiddleName"];

					$itemBody .= "        <FileAs>".htmlspecialcharsbx($v)."</FileAs>\r\n";
				}
				elseif (array_key_exists($key, $arDictionaryURITypeMap))
				{
					if ($arDictionaryURITypeMap[$key][2] == "EmailAddresses")
					{
						$itemBody .= "     <EmailAddresses>\r\n";
						$itemBody .= "      <Entry Key=\"EmailAddress1\">".htmlspecialcharsbx($arFields["EmailAddresses"])."</Entry>\r\n";
						$itemBody .= "     </EmailAddresses>\r\n";
					}
					elseif ($arDictionaryURITypeMap[$key][2] == "PhysicalAddresses")
					{
						$itemBody .= "      <PhysicalAddresses>\r\n";
						$itemBody .= "       <Entry Key=\"".$arDictionaryURITypeMap[$key][1]."\">\r\n";
						$itemBody .= "        <".$arDictionaryURITypeMap[$key][3].">".htmlspecialcharsbx($value)."</".$arDictionaryURITypeMap[$key][3].">\r\n";
						$itemBody .= "       </Entry>\r\n";
						$itemBody .= "      </PhysicalAddresses>\r\n";
					}
					elseif ($arDictionaryURITypeMap[$key][2] == "PhoneNumbers")
					{
						$itemBody .= "	    <PhoneNumbers>\r\n";
						$itemBody .= "	     <Entry Key=\"".$arDictionaryURITypeMap[$key][1]."\">".htmlspecialcharsbx($value)."</Entry>\r\n";
						$itemBody .= "	    </PhoneNumbers>\r\n";
					}
					elseif ($arDictionaryURITypeMap[$key][2] == "ImAddresses")
					{
						$itemBody .= "     <ImAddresses>\r\n";
						$itemBody .= "      <Entry Key=\"ImAddress1\">".htmlspecialcharsbx($arFields["ImAddresses"])."</Entry>\r\n";
						$itemBody .= "     </ImAddresses>\r\n";
					}
				}
				else
				{
					$itemBody .= "        <".htmlspecialcharsbx($key).">";
					if (is_bool($value))
						$itemBody .= ($value ? "true" : "false");
					else
						$itemBody .= htmlspecialcharsbx($value);
					$itemBody .= "</".htmlspecialcharsbx($key).">\r\n";
				}

				$itemBody .= "       </Contact>\r\n";
				$itemBody .= "      </SetItemField>\r\n";
			}
		}

		return $itemBody;
	}

	public function CreateFolderBody($arFields)
	{
		$itemBody  = "    <ContactsFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		//$itemBody .= "     <FolderClass>IPF.Contact</FolderClass>\r\n";
		$itemBody .= "     <DisplayName>".htmlspecialcharsbx($arFields["DisplayName"])."</DisplayName>\r\n";
		$itemBody .= "    </ContactsFolder>\r\n";
		return $itemBody;
	}

	public function UpdateFolderBody($arFields)
	{
		$itemBody = "";

		$itemBody .= "      <SetFolderField>\r\n";
		$itemBody .= "       <FieldURI FieldURI=\"folder:DisplayName\"/>\r\n";
		$itemBody .= "       <ContactsFolder>\r\n";
		$itemBody .= "        <DisplayName>".htmlspecialcharsbx($arFields["DisplayName"])."</DisplayName>\r\n";
		$itemBody .= "       </ContactsFolder>\r\n";
		$itemBody .= "      </SetFolderField>\r\n";

		return $itemBody;
	}

	public static function InitUserEntity()
	{
		if (!CModule::IncludeModule("intranet"))
			return;

		$arRequiredFields = array(
			"UF_BXDAVEX_CNTSYNC" => array(
				"USER_TYPE_ID" => "datetime",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Contacts sync date",
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

	public static function DataSync($paramUserId = 0)
	{
		if (DAV_EXCH_DEBUG)
			CDav::WriteToLog("Starting EXCHANGE contacts sync...", "SC");

		$exchangeScheme = COption::GetOptionString("dav", "exchange_scheme", "http");
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$exchangePort = COption::GetOptionString("dav", "exchange_port", "80");
		$exchangeUsername = COption::GetOptionString("dav", "exchange_username", "");
		$exchangePassword = COption::GetOptionString("dav", "exchange_password", "");

		if (empty($exchangeServer)/* || (COption::GetOptionString("dav", "agent_contacts", "N") != "Y")*/)
		{
			CAgent::RemoveAgent("CDavExchangeContacts::DataSync();", "dav");
			COption::SetOptionString("dav", "agent_contacts", "N");
			return "";
		}

		$exchange = new CDavExchangeContacts($exchangeScheme, $exchangeServer, $exchangePort, $exchangeUsername, $exchangePassword);

		if (GW_DEBUG)
			$exchange->Debug();

		$exchangeMailbox = COption::GetOptionString("dav", "exchange_mailbox", "");
		$exchangeUseLogin = COption::GetOptionString("dav", "exchange_use_login", "Y");

		self::InitUserEntity();

		$maxNumber = 5;
		$index = 0;
		$rootStructureName = null;

		$paramUserId = intval($paramUserId);
		$arUserFilter = array("ACTIVE" => "Y", "!UF_DEPARTMENT" => false);
		if ($paramUserId > 0)
			$arUserFilter["ID_EQUAL_EXACT"] = $paramUserId;
		if ($exchangeUseLogin == "N")
			$arUserFilter["!UF_BXDAVEX_MAILBOX"] = false;

		$dbUserList = CUser::GetList($by = "UF_BXDAVEX_CNTSYNC", $order = "asc", $arUserFilter, array("SELECT" => array("ID", "LOGIN", "UF_BXDAVEX_MAILBOX", "UF_BXDAVEX_CNTSYNC")));
		while ($arUser = $dbUserList->Fetch())
		{
			$index++;
			if ($index > $maxNumber)
				break;

			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("Processing user [".$arUser["ID"]."] ".$arUser["LOGIN"], "SC");

			$lastSyncDate = $arUser["UF_BXDAVEX_CNTSYNC"];
			if (empty($lastSyncDate))
				$lastSyncDate = ConvertTimeStamp(mktime(0, 0, 0, 1, 1, 2000), FULL);

			$GLOBALS["USER_FIELD_MANAGER"]->Update("USER", $arUser["ID"], array("UF_BXDAVEX_CNTSYNC" => ConvertTimeStamp(time(), FULL)));

			$mailbox = (($exchangeUseLogin == "Y") ? $arUser["LOGIN"].$exchangeMailbox : $arUser["UF_BXDAVEX_MAILBOX"]);
			if (empty($mailbox))
				continue;

			$tmpNumItems = 0;
			$arAddressbookCache = null;

			$dbUserListTmp = CUser::GetList(
				$by = "ID",
				$order = "asc",
				array("TIMESTAMP_X_1" => $lastSyncDate, "ACTIVE" => "Y", "!UF_DEPARTMENT" => false)
			);
			if ($arUserTmp = $dbUserListTmp->Fetch())
			{
				if (is_null($rootStructureName))
				{
					$iblockStructure = COption::GetOptionInt("intranet", 'iblock_structure', 0);
					$db = CIBlockSection::GetList(array("LEFT_MARGIN" => "ASC"), array("IBLOCK_ID" => $iblockStructure));
					if ($ar = $db->Fetch())
						$rootStructureName = $ar["NAME"];
				}
				if (is_null($rootStructureName))
				{
					if (DAV_EXCH_DEBUG)
						CDav::WriteToLog("ERROR: Root structure is not found", "SC");
					break;
				}

				$arAddressbooksList = $exchange->GetAddressbooksList(array("mailbox" => $mailbox));

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
						CDav::WriteToLog("ERROR: ".$txt, "SC");

					continue;
				}

				$arAddressbook = null;
				foreach ($arAddressbooksList as $value)
				{
					if ($value["NAME"] == $rootStructureName)
					{
						$arAddressbook = $value;
						break;
					}
				}

				if (is_null($arAddressbook))
					$arAddressbook = $exchange->AddAddressbook(array("mailbox" => $mailbox, "NAME" => $rootStructureName));

				if (!is_array($arAddressbook) || !isset($arAddressbook["XML_ID"]))
				{
					if (DAV_EXCH_DEBUG)
						CDav::WriteToLog("ERROR: Addressbook '".$rootStructureName."' for mailbox '".$mailbox."' is not found", "SC");
					continue;
				}

				if (is_null($arAddressbookCache))
				{
					$arAddressbookCache = array();

					$arPrs = $exchange->GetList(
						array("Mailbox" => $mailbox, "AddressbookId" => $arAddressbook["XML_ID"]),
						array("ItemShape" => "IdOnly", "AdditionalProperties" => array("contacts:GivenName", "contacts:MiddleName", "contacts:Surname"))
					);
					foreach ($arPrs as $prs)
					{
						$s = $prs["NAME"]."/".$prs["SECOND_NAME"]."/".$prs["LAST_NAME"];
						$arAddressbookCache[$s] = array(
							"XML_ID" => $prs["XML_ID"],
							"MODIFICATION_LABEL" => $prs["MODIFICATION_LABEL"]
						);
					}
				}

				do
				{
					$s = $arUserTmp["NAME"]."/".$arUserTmp["SECOND_NAME"]."/".$arUserTmp["LAST_NAME"];
					if (array_key_exists($s, $arAddressbookCache))
					{
						$exchange->Update(
							$arAddressbookCache[$s],
							array_merge($arUserTmp, array("Mailbox" => $mailbox, "AddressbookId" => $arAddressbook["XML_ID"]))
						);
					}
					else
					{
						$exchange->Add(array_merge($arUserTmp, array("Mailbox" => $mailbox, "AddressbookId" => $arAddressbook["XML_ID"])));
					}

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
							CDav::WriteToLog("ERROR: ".$txt, "SC");
					}

					$tmpNumItems++;
				}
				while ($arUserTmp = $dbUserListTmp->Fetch());
			}

			if (DAV_EXCH_DEBUG)
				CDav::WriteToLog("Sync ".intval($tmpNumItems)." items", "SC");
		}

		if (DAV_EXCH_DEBUG)
			CDav::WriteToLog("EXCHANGE contacts sync finished", "SC");

		return "CDavExchangeContacts::DataSync();";
	}

	public static function IsExchangeEnabled()
	{
		$exchangeServer = COption::GetOptionString("dav", "exchange_server", "");
		$agentContacts = COption::GetOptionString("dav", "agent_contacts", "N");
		return (!empty($exchangeServer) && ($agentContacts == "Y"));
	}
}
?>