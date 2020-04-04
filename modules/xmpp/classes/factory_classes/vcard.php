<?
$className = "CXMPPReceiveIQVCard";
$classVersion = 2;

if (!class_exists("CXMPPReceiveIQVCard"))
{
	class CXMPPReceiveIQVCard
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler
	{
		public function GetIndex()
		{
			return 90;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (!$senderClient->IsAuthenticated())
				return false;
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("iq", $arMessage) || !array_key_exists("vcard", $arMessage["iq"]) || $arMessage["iq"]["vcard"]["."]["xmlns"] != "vcard-temp")
				return false;

			$type = "";
			if (array_key_exists("type", $arMessage["iq"]["."]))
				$type = $arMessage["iq"]["."]["type"];

			if ($type == "get")
			{
				$to = $arMessage["iq"]["."]["to"];

				$arUser = CXMPPUtility::GetUserByJId($to);
				if ($arUser)
				{
					$photoType = "";
					$photo = "";
					if (intval($arUser["PERSONAL_PHOTO"]) > 0)
					{
						$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
						if ($imageFile !== false)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$imageFile,
								array("width" => 300, "height" => 300),
								BX_RESIZE_IMAGE_PROPORTIONAL,
								false
							);

							if(is_array($arFileTmp))
								$arFileTmp = CFile::MakeFileArray($arFileTmp["src"]);

							$photoType = $imageFile["CONTENT_TYPE"];

							if (File_Exists($arFileTmp["tmp_name"]))
							{
								$photo = File_Get_Contents($arFileTmp["tmp_name"]);
								$photo = Base64_Encode($photo);
							}
						}
					}

					if (Empty($photo))
					{
						$photoType = "image/gif";
						if (File_Exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/images/socialnetwork/nopic_user_150.gif"))
						{
							$photo = File_Get_Contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/images/socialnetwork/nopic_user_150.gif");
							$photo = Base64_Encode($photo);
						}
					}

					$arResult = array(
						"iq" => array(
							"." => array(
								"type" => "result",
								"from" => $to,
								"to" => $senderJId,
								"id" => $arMessage['iq']['.']['id'],
							),
							"vCard" => array(
								"." => array("xmlns" => "vcard-temp", "prodid" => '-//HandGen//NONSGML vGen v1.0//EN', "version" => '2.0'),
								"FN" => array("#" => CUser::FormatName($this->nameTemplate, $arUser)),
								"N" => array(
									"FAMILY" => array("#" => $arUser["LAST_NAME"]),
									"GIVEN" => array("#" => $arUser["NAME"]),
									"MIDDLE" => array("#" => $arUser["SECOND_NAME"]),
								),
								"NICKNAME" => array("#" => CUser::FormatName($this->nameTemplate, $arUser)),
								"BDAY" => array("#" => ConvertDateTime($arUser["PERSONAL_BIRTHDAY"], "YYYY-MM-DD", SITE_ID)),
								"ORG" => array(
									"ORGNAME" => array("#" => $arUser["WORK_COMPANY"]),
									"ORGUNIT" => array("#" => $arUser["WORK_DEPARTMENT"])
								),
								"TITLE" => array("#" => $arUser["WORK_POSITION"]),
								"EMAIL" => array(
									"INTERNET" => array("#" => ""),
									"PREF" => array("#" => ""),
									"USERID" => array("#" => $arUser["EMAIL"]),
								),
								"JABBERID" => array("#" => $to),
								"PHOTO" => array(
									"TYPE" => array("#" => $photoType),
									"BINVAL" => array("#" => $photo),
								),
							),
						),
					);

					if (strlen($arUser["WORK_FAX"]) > 0)
						$arResult["iq"]["vCard"]["TEL"][] = array(
							"WORK" => array("#" => ""),
							"FAX" => array("#" => ""),
							"NUMBER" => array("#" => $arUser["WORK_FAX"]),
						);
					if (strlen($arUser["PERSONAL_MOBILE"]) > 0)
						$arResult["iq"]["vCard"]["TEL"][] = array(
							"HOME" => array("#" => ""),
							"CELL" => array("#" => ""),
							"NUMBER" => array("#" => $arUser["PERSONAL_MOBILE"]),
						);
					if (strlen($arUser["WORK_PHONE"]) > 0)
						$arResult["iq"]["vCard"]["TEL"][] = array(
							"WORK" => array("#" => ""),
							"VOICE" => array("#" => ""),
							"NUMBER" => array("#" => $arUser["WORK_PHONE"]),
						);
					if (strlen($arUser["PERSONAL_PHONE"]) > 0)
						$arResult["iq"]["vCard"]["TEL"][] = array(
							"HOME" => array("#" => ""),
							"VOICE" => array("#" => ""),
							"NUMBER" => array("#" => $arUser["PERSONAL_PHONE"]),
						);
					if (strlen($arUser["PERSONAL_FAX"]) > 0)
						$arResult["iq"]["vCard"]["TEL"][] = array(
							"HOME" => array("#" => ""),
							"FAX" => array("#" => ""),
							"NUMBER" => array("#" => $arUser["PERSONAL_FAX"]),
						);

					if (strlen($arUser["WORK_STREET"]) > 0 || strlen($arUser["WORK_CITY"]) > 0 || strlen($arUser["WORK_COUNTRY"]) > 0)
						$arResult["iq"]["vCard"]["ADR"][] = array(
							"WORK" => array("#" => ""),
							"EXTADD" => array("#" => ""),
							"STREET" => array("#" => $arUser["WORK_STREET"]),
							"LOCALITY" => array("#" => $arUser["WORK_CITY"]),
							"REGION" => array("#" => $arUser["WORK_STATE"]),
							"PCODE" => array("#" => $arUser["WORK_ZIP"]),
							"CTRY" => array("#" => GetCountryByID($arUser["WORK_COUNTRY"])),
						);
					if (strlen($arUser["PERSONAL_STREET"]) > 0 || strlen($arUser["PERSONAL_CITY"]) > 0 || strlen($arUser["PERSONAL_COUNTRY"]) > 0)
						$arResult["iq"]["vCard"]["ADR"][] = array(
							"HOME" => array("#" => ""),
							"EXTADD" => array("#" => ""),
							"STREET" => array("#" => $arUser["PERSONAL_STREET"]),
							"LOCALITY" => array("#" => $arUser["PERSONAL_CITY"]),
							"REGION" => array("#" => $arUser["PERSONAL_STATE"]),
							"PCODE" => array("#" => $arUser["PERSONAL_ZIP"]),
							"CTRY" => array("#" => GetCountryByID($arUser["PERSONAL_COUNTRY"])),
						);

					if ($senderJId != $to)
						$arResult["iq"]["."]["from"] = $to;

					//print_r($arResult);echo "\n*****************************************\n";
				}
				else
				{
					$arResult = array(
						"iq" => array(
							"." => array(
								"type" => "error",
								"to" => $senderJId,
								"id" => $arMessage['iq']['.']['id'],
							),
							"vCard" => array("." => array("xmlns" => "vcard-temp")),
							"error" => array(
								"." => array("type" => "cancel"),
								"item-not-found" => array("." => array("xmlns" => "urn:ietf:params:xml:ns:xmpp-stanzas")),
							),
						),
					);

					if ($senderJId != $to)
						$arResult["iq"]["."]["from"] = $to;
				}
			}
			elseif ($type == "set")
			{
				$arResult = array(
					"iq" => array(
						"." => array(
							"type" => "result",
							"from" => $senderClient->GetClientDomain(),
							"id" => $arMessage['iq']['.']['id'],
						),
					),
				);
			}

			return $arResult;
		}
	}
}
?>