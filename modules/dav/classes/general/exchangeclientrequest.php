<?
// http://msdn.microsoft.com/en-us/library/aa580675(v=EXCHG.140).aspx

class CDavExchangeClientRequest
{
	private $method = '';
	private $path = '';
	private $arHeaders = array();
	private $body = '';

	private $exchangeClient = null;

	static $arDistinguishedFolderIdNameType = array("calendar", "contacts", "deleteditems", "drafts", "inbox", "journal", "notes", "outbox", "sentitems", "tasks", "msgfolderroot", "publicfoldersroot", "root", "junkemail", "searchfolders", "voicemail");

	public function __construct($exchangeClient)
	{
		$this->exchangeClient = $exchangeClient;
	}

	public function AddHeader($key, $value)
	{
		if (empty($key) || empty($value))
			return;

		if (array_key_exists($key, $this->arHeaders))
		{
			if (is_array($this->arHeaders[$key]))
			{
				$this->arHeaders[$key][] = $value;
			}
			else
			{
				$ar = array($this->arHeaders[$key], $value);
				$this->arHeaders[$key] = $ar;
			}
		}
		else
		{
			$this->arHeaders[$key] = $value;
		}
	}

	public function SetHeader($key, $value)
	{
		if (empty($key))
		{
			return;
		}

		if (array_key_exists($key, $this->arHeaders) && empty($value))
		{
			unset($this->arHeaders[$key]);
		}
		else
		{
			$this->arHeaders[$key] = $value;
		}
	}

	public function SetMethod($method)
	{
		$this->method = $method;
	}

	public function GetMethod()
	{
		return $this->method;
	}

	public function SetPath($path)
	{
		$this->path = $path;
	}

	public function GetPath()
	{
		return $this->path;
	}

	public function SetBody($body)
	{
		$this->body = $body;
	}

	public function CreateFindItemBody($arParentFolderId, $item = null, $itemShape = "AllProperties",
		$additionalProperties = array(), $arAdditionalExtendedProperties = array()
	)
	{
		$arMapTmp = array("idonly" => "IdOnly", "id_only" => "IdOnly", "allproperties" => "AllProperties", "all_properties" => "AllProperties");
		$itemShapeLower = mb_strtolower($itemShape);
		if (array_key_exists($itemShapeLower, $arMapTmp))
		{
			$itemShape = $arMapTmp[$itemShapeLower];
		}
		else
		{
			$itemShape = "AllProperties";
		}

		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <FindItem xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\" Traversal=\"Shallow\">\r\n";
		$this->body .= "   <ItemShape>\r\n";
		$this->body .= "    <t:BaseShape>".$itemShape."</t:BaseShape>\r\n";

		$bAdditionalProperties = (is_array($additionalProperties) && !empty($additionalProperties));
		$bAdditionalExtendedProperties = (is_array($arAdditionalExtendedProperties) && !empty($arAdditionalExtendedProperties));

		if ($bAdditionalProperties || $bAdditionalExtendedProperties)
			$this->body .= "    <t:AdditionalProperties>\r\n";

		if ($bAdditionalProperties)
		{
			foreach ($additionalProperties as $v)
			{
				$this->body .= "     <t:FieldURI FieldURI=\"" . htmlspecialcharsbx($v) . "\"/>\r\n";
			}
		}

		if ($bAdditionalExtendedProperties)
		{
			$arProbablyExtendedFields = array('DistinguishedPropertySetId',
				'PropertyName', 'PropertyType', 'PropertySetId'
			);

			foreach ($arAdditionalExtendedProperties as $arAdditionalExtendedProperty)
			{
				$this->body .= '     <t:ExtendedFieldURI ';

				foreach($arProbablyExtendedFields as $probablyFieldName)
					if (isset($arAdditionalExtendedProperty[$probablyFieldName]))
						$this->body .= ' ' . $probablyFieldName . '="' . $arAdditionalExtendedProperty[$probablyFieldName] . '"';

				$this->body .= " />\r\n";
			}
		}

		if ($bAdditionalProperties || $bAdditionalExtendedProperties)
		{
			$this->body .= "    </t:AdditionalProperties>\r\n";
		}

		$this->body .= "   </ItemShape>\r\n";

		if (!is_null($item))
		{
			$this->body .= "   <".htmlspecialcharsbx($item["type"]);
			foreach ($item["properties"] as $key => $value)
			{
				$this->body .= " " . htmlspecialcharsbx($key) . "=\"" . htmlspecialcharsbx($value) . "\"";
			}
			$this->body .= "/>\r\n";
		}

		/*
		$this->body .= "   <Restriction>\r\n";
		$this->body .= "    <IsGreaterThan xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= "     <FieldURI FieldURI=\"item:LastModifiedTime\"/>\r\n";
		$this->body .= "     <FieldURIOrConstant><Constant Value=\"2011-03-01T00:00:00Z\"/></FieldURIOrConstant>\r\n";
		$this->body .= "    </IsGreaterThan>\r\n";
		$this->body .= "   </Restriction>\r\n";
		*/

		$this->body .= "   <ParentFolderIds>\r\n";

		/*
		"calendar"
		array("id" => "calendar")
		array("id" => "calendar", "mailbox" => "aaa@bbb.cc")
		array("id" => "calendar", "mailbox" => array("aaa@bbb.cc", "ddd@eee.ff"))
		*/
		if (!is_array($arParentFolderId))
		{
			$arParentFolderId = ["id" => $arParentFolderId];
		}

		if (!in_array($arParentFolderId["id"], self::$arDistinguishedFolderIdNameType))
		{
			$this->body .= "    <t:FolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\""." ChangeKey=\"".htmlspecialcharsbx($arParentFolderId['changekey'])."\""." />\r\n";
		}
		elseif (array_key_exists("mailbox", $arParentFolderId))
		{
			$arMailbox = $arParentFolderId["mailbox"];
			if (!is_array($arMailbox))
			{
				$arMailbox = [$arMailbox];
			}

			foreach ($arMailbox as $mailbox)
			{
				$this->body .= "    <t:DistinguishedFolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\">\r\n";
				$this->body .= "     <t:Mailbox><t:EmailAddress>".htmlspecialcharsbx($mailbox)."</t:EmailAddress></t:Mailbox>\r\n";
				$this->body .= "    </t:DistinguishedFolderId>\r\n";
			}
		}
		else
		{
			$this->body .= "    <t:DistinguishedFolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\"/>\r\n";
		}

		$this->body .= "   </ParentFolderIds>\r\n";

		$this->body .= "  </FindItem>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateGetItemBody($itemId = null, $itemShape = "AllProperties", $arAdditionalExtendedProperties = array())
	{
		$arMapTmp = [
			"idonly" => "IdOnly",
			"id_only" => "IdOnly",
			"allproperties" => "AllProperties",
			"all_properties" => "AllProperties"
		];
		$itemShapeLower = mb_strtolower($itemShape);
		if (array_key_exists($itemShapeLower, $arMapTmp))
		{
			$itemShape = $arMapTmp[$itemShapeLower];
		}
		else
		{
			$itemShape = "AllProperties";
		}

		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <GetItem xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= "   <ItemShape>\r\n";
		$this->body .= "    <t:BaseShape>".$itemShape."</t:BaseShape>\r\n";

		if (is_array($arAdditionalExtendedProperties) && !empty($arAdditionalExtendedProperties))
		{
			$this->body .= '<AdditionalProperties xmlns="http://schemas.microsoft.com/exchange/services/2006/types">' . "\r\n";

			foreach ($arAdditionalExtendedProperties as $arAdditionalExtendedProperty)
			{
				$this->body .= '     <ExtendedFieldURI ';

				$arProbablyExtendedFields = array('DistinguishedPropertySetId',
					'PropertyName', 'PropertyType', 'PropertySetId'
				);

				foreach($arProbablyExtendedFields as $probablyFieldName)
				{
					if (isset($arAdditionalExtendedProperty[$probablyFieldName]))
					{
						$this->body .= ' ' . $probablyFieldName . '="' . $arAdditionalExtendedProperty[$probablyFieldName] . '"';
					}
				}

				$this->body .= " />\r\n";
			}

			$this->body .= '</AdditionalProperties>' . "\r\n";
		}

		$this->body .= "   </ItemShape>\r\n";

		$this->body .= "   <ItemIds>\r\n";

		if (!is_array($itemId))
		{
			$itemId = array("id" => $itemId);
		}

		$arKeys = array_keys($itemId);
		if (!empty($itemId) && $arKeys[0] . "!" !== "0!")
		{
			$itemId = array($itemId);
		}

		foreach ($itemId as $value)
		{
			$id = (isset($value["id"]) ? $value["id"] : $value["XML_ID"]);
			$changekey = (isset($value["changekey"]) ? $value["changekey"] : (isset($value["MODIFICATION_LABEL"]) ? $value["MODIFICATION_LABEL"] : null));

			$this->body .= "    <t:ItemId Id=\"".htmlspecialcharsbx($id)."\"";
			if (!is_null($changekey) && !empty($changekey))
			{
				$this->body .= " ChangeKey=\"".htmlspecialcharsbx($changekey)."\"";
			}
			$this->body .= "/>\r\n";
		}

		$this->body .= "   </ItemIds>\r\n";


		$this->body .= "  </GetItem>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateCreateItemBody($arSavedItemFolderId, $arFields)
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">\r\n";
		if (method_exists($this->exchangeClient, "GetItemHeader"))
		{
			$this->body .= $this->exchangeClient->GetItemHeader($arFields);
		}
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <CreateItem SendMeetingInvitations=\"SendOnlyToAll\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";

		$this->body .= "   <SavedItemFolderId>\r\n";

		if (!is_array($arSavedItemFolderId))
		{
			$arSavedItemFolderId = array("id" => $arSavedItemFolderId);
		}

		if (!in_array($arSavedItemFolderId["id"], self::$arDistinguishedFolderIdNameType))
		{
			$this->body .= "    <FolderId Id=\"".htmlspecialcharsbx($arSavedItemFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}
		elseif (array_key_exists("mailbox", $arSavedItemFolderId))
		{
			$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($arSavedItemFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
			$this->body .= "     <Mailbox><EmailAddress>".htmlspecialcharsbx($arSavedItemFolderId["mailbox"])."</EmailAddress></Mailbox>\r\n";
			$this->body .= "    </DistinguishedFolderId>\r\n";
		}
		else
		{
			$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($arSavedItemFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}

		$this->body .= "   </SavedItemFolderId>\r\n";


		$this->body .= "   <Items>\r\n";
		$this->body .= $this->exchangeClient->CreateItemBody($arFields);
		$this->body .= "   </Items>\r\n";


		$this->body .= "  </CreateItem>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateUpdateItemBody($itemId, $arFields)
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <UpdateItem ".$this->exchangeClient->UpdateItemAttributes()." xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";
		$this->body .= "   <ItemChanges>\r\n";
		$this->body .= "    <ItemChange xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";

		if (!is_array($itemId))
			$itemId = array("id" => $itemId);

		$id = (isset($itemId["id"]) ? $itemId["id"] : $itemId["XML_ID"]);
		$changekey = (isset($itemId["changekey"]) ? $itemId["changekey"] : (isset($itemId["MODIFICATION_LABEL"]) ? $itemId["MODIFICATION_LABEL"] : null));


		$this->body .= "     <ItemId Id=\"".htmlspecialcharsbx($id)."\"";
		if (!is_null($changekey) && !empty($changekey))
			$this->body .= " ChangeKey=\"".htmlspecialcharsbx($changekey)."\"";
		$this->body .= "/>\r\n";

		$this->body .= "     <Updates>\r\n";
		$this->body .= $this->exchangeClient->UpdateItemBody($arFields);
		$this->body .= "     </Updates>\r\n";

		$this->body .= "    </ItemChange>\r\n";
		$this->body .= "   </ItemChanges>\r\n";

		$this->body .= "  </UpdateItem>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateDeleteItemBody($itemId = null)
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <DeleteItem xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\" SendMeetingCancellations=\"SendOnlyToAll\" DeleteType=\"HardDelete\" AffectedTaskOccurrences=\"AllOccurrences\">\r\n";
		$this->body .= "   <ItemIds>\r\n";

		if (!is_array($itemId))
		{
			$itemId = [$itemId];
		}

		foreach ($itemId as $value)
		{
			$this->body .= "    <t:ItemId Id=\"" . htmlspecialcharsbx($value) . "\"/>\r\n";
		}

		$this->body .= "   </ItemIds>\r\n";
		$this->body .= "  </DeleteItem>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateFindFolderBody($arParentFolderId, $folderShape = "AllProperties")
	{
		$arMapTmp = array("idonly" => "IdOnly", "id_only" => "IdOnly", "allproperties" => "AllProperties", "all_properties" => "AllProperties");
		$folderShapeLower = mb_strtolower($folderShape);
		if (array_key_exists($folderShapeLower, $arMapTmp))
		{
			$folderShape = $arMapTmp[$folderShapeLower];
		}
		else
		{
			$folderShape = "AllProperties";
		}

		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <FindFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\" Traversal=\"Shallow\">\r\n";
		$this->body .= "   <FolderShape>\r\n";
		$this->body .= "    <BaseShape xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">".$folderShape."</BaseShape>\r\n";
		$this->body .= "   </FolderShape>\r\n";

		$this->body .= "   <ParentFolderIds>\r\n";

		/*
		"calendar"
		array("id" => "calendar")
		array("id" => "calendar", "mailbox" => "aaa@bbb.cc")
		array("id" => "calendar", "mailbox" => array("aaa@bbb.cc", "ddd@eee.ff"))
		*/
		if (!is_array($arParentFolderId))
			$arParentFolderId = array("id" => $arParentFolderId);

		if (!in_array($arParentFolderId["id"], self::$arDistinguishedFolderIdNameType))
		{
			$this->body .= "    <FolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}
		elseif (array_key_exists("mailbox", $arParentFolderId))
		{
			$arMailbox = $arParentFolderId["mailbox"];
			if (!is_array($arMailbox))
				$arMailbox = array($arMailbox);

			foreach ($arMailbox as $mailbox)
			{
				$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\"  xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
				$this->body .= "     <Mailbox><EmailAddress>".htmlspecialcharsbx($mailbox)."</EmailAddress></Mailbox>\r\n";
				$this->body .= "    </DistinguishedFolderId>\r\n";
			}
		}
		else
		{
			$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\"  xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}

		$this->body .= "   </ParentFolderIds>\r\n";

		$this->body .= "  </FindFolder>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateGetFolderBody($folderId = null, $folderShape = "AllProperties")
	{
		$arMapTmp = array("idonly" => "IdOnly", "id_only" => "IdOnly", "allproperties" => "AllProperties", "all_properties" => "AllProperties");
		$folderShapeLower = mb_strtolower($folderShape);
		if (array_key_exists($folderShapeLower, $arMapTmp))
			$folderShape = $arMapTmp[$folderShapeLower];
		else
			$folderShape = "AllProperties";

		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <GetFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";
		$this->body .= "   <FolderShape>\r\n";
		$this->body .= "    <BaseShape xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">".$folderShape."</BaseShape>\r\n";
		$this->body .= "   </FolderShape>\r\n";

		$this->body .= "   <FolderIds>\r\n";

		if (!is_array($folderId))
			$folderId = array("id" => $folderId);

		$arKeys = array_keys($folderId);
		if (!empty($folderId))
		{
			if ($arKeys[0]."!" != "0!")
			{
				$folderId = [$folderId];
			}
		}

		$arMapTmp = array("mailbox" => "Mailbox", "id" => "Id", "xml_id" => "Id", "changekey" => "ChangeKey", "modification_label" => "ChangeKey");
		foreach ($folderId as $value)
		{
			CDavExchangeClient::NormalizeArray($value, $arMapTmp);

			$id = (isset($value["Id"]) ? $value["Id"] : null);
			$changekey = (isset($value["ChangeKey"]) ? $value["ChangeKey"] : null);
			$mailbox = (isset($value["Mailbox"]) ? $value["Mailbox"] : null);

			if (!in_array($id, self::$arDistinguishedFolderIdNameType))
			{
				$this->body .= "    <FolderId Id=\"".htmlspecialcharsbx($id)."\"";
				if (!is_null($changekey) && !empty($changekey))
					$this->body .= " ChangeKey=\"".htmlspecialcharsbx($changekey)."\"";
				$this->body .= " xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
			}
			else
			{
				$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($id)."\"";
				if (!is_null($changekey) && !empty($changekey))
					$this->body .= " ChangeKey=\"".htmlspecialcharsbx($changekey)."\"";
				$this->body .= " xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"";
				if (!is_null($mailbox) && !empty($mailbox))
					$this->body .= "><Mailbox><EmailAddress>".htmlspecialcharsbx($mailbox)."</EmailAddress></Mailbox></DistinguishedFolderId>\r\n";
				else
					$this->body .= "/>\r\n";
			}
		}

		$this->body .= "   </FolderIds>\r\n";

		$this->body .= "  </GetFolder>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateCreateFolderBody($arParentFolderId, $arFields)
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <CreateFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";

		$this->body .= "   <ParentFolderId>\r\n";

		if (!is_array($arParentFolderId))
			$arParentFolderId = array("id" => $arParentFolderId);

		if (!in_array($arParentFolderId["id"], self::$arDistinguishedFolderIdNameType))
		{
			$this->body .= "    <FolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}
		elseif (array_key_exists("mailbox", $arParentFolderId))
		{
			$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
			$this->body .= "     <Mailbox><EmailAddress>".htmlspecialcharsbx($arParentFolderId["mailbox"])."</EmailAddress></Mailbox>\r\n";
			$this->body .= "    </DistinguishedFolderId>\r\n";
		}
		else
		{
			$this->body .= "    <DistinguishedFolderId Id=\"".htmlspecialcharsbx($arParentFolderId["id"])."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}

		$this->body .= "   </ParentFolderId>\r\n";


		$this->body .= "   <Folders>\r\n";
		$this->body .= $this->exchangeClient->CreateFolderBody($arFields);
		$this->body .= "   </Folders>\r\n";


		$this->body .= "  </CreateFolder>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateUpdateFolderBody($folderId, $arFields)
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <UpdateFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";
		$this->body .= "   <FolderChanges>\r\n";
		$this->body .= "    <FolderChange xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";

		if (!is_array($folderId))
			$folderId = array("id" => $folderId);

		if (!in_array($folderId["id"], self::$arDistinguishedFolderIdNameType))
		{
			$id = (isset($folderId["id"]) ? $folderId["id"] : $folderId["XML_ID"]);
			$changekey = (isset($folderId["changekey"]) ? $folderId["changekey"] : (isset($folderId["MODIFICATION_LABEL"]) ? $folderId["MODIFICATION_LABEL"] : null));

			$this->body .= "     <FolderId Id=\"".htmlspecialcharsbx($id)."\"";
			if (!is_null($changekey) && !empty($changekey))
				$this->body .= " ChangeKey=\"".htmlspecialcharsbx($changekey)."\"";
			$this->body .= "/>\r\n";
		}
		else
		{
			$this->body .= "     <DistinguishedFolderId Id=\"".htmlspecialcharsbx($id)."\"";
			if (!is_null($changekey) && !empty($changekey))
				$this->body .= " ChangeKey=\"".htmlspecialcharsbx($changekey)."\"";
			$this->body .= "/>\r\n";
		}

		$this->body .= "     <Updates>\r\n";
		$this->body .= $this->exchangeClient->UpdateFolderBody($arFields);
		$this->body .= "     </Updates>\r\n";

		$this->body .= "    </FolderChange>\r\n";
		$this->body .= "   </FolderChanges>\r\n";

		$this->body .= "  </UpdateFolder>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateDeleteFolderBody($folderId = null)
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:t=\"http://schemas.microsoft.com/exchange/services/2006/types\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <DeleteFolder xmlns=\"http://schemas.microsoft.com/exchange/services/2006/messages\" DeleteType=\"HardDelete\">\r\n";
		$this->body .= "   <FolderIds>\r\n";

		if (!is_array($folderId))
			$folderId = array($folderId);

		foreach ($folderId as $value)
		{
			if (!in_array($value, self::$arDistinguishedFolderIdNameType))
				$this->body .= "     <FolderId Id=\"".htmlspecialcharsbx($value)."\" xmlns=\"http://schemas.microsoft.com/exchange/services/2006/types\"/>\r\n";
		}

		$this->body .= "   </FolderIds>\r\n";
		$this->body .= "  </DeleteFolder>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateGetRoomListsBody()
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" \r\n".
               "xmlns:soap = \"http://schemas.xmlsoap.org/soap/envelope/\" \r\n".
               "xmlns:t = \"http://schemas.microsoft.com/exchange/services/2006/types\" \r\n".
               "xmlns:m = \"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <m:GetRoomLists />\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function CreateGetRoomsBody()
	{
		$this->body  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\r\n";
		$this->body .= "<soap:Envelope xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" \r\n".
			"xmlns:soap = \"http://schemas.xmlsoap.org/soap/envelope/\" \r\n".
			"xmlns:t = \"http://schemas.microsoft.com/exchange/services/2006/types\" \r\n".
			"xmlns:m = \"http://schemas.microsoft.com/exchange/services/2006/messages\">\r\n";
		$this->body .= " <soap:Body>\r\n";
		$this->body .= "  <m:GetRooms>\r\n";
		$this->body .= "  </m:GetRooms>\r\n";
		$this->body .= " </soap:Body>\r\n";
		$this->body .= "</soap:Envelope>";
	}

	public function ToString()
	{
		$buffer = sprintf("%s %s HTTP/1.0\r\n", $this->method, $this->path);
		foreach ($this->arHeaders as $key => $value)
		{
			if (!is_array($value))
				$value = array($value);

			foreach ($value as $value1)
				$buffer .= sprintf("%s: %s\r\n", $key, $value1);
		}
		$buffer .= sprintf("Content-length: %s\r\n", ((function_exists('mb_strlen')? mb_strlen($this->body, 'latin1') : mb_strlen($this->body))));
		$buffer .= "\r\n";
		$buffer .= $this->body;
		return $buffer;
	}
}
