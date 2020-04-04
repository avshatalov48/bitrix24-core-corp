<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
{
	ShowError(GetMessage("INTRANET_MODULE_NOT_INSTALL"));
	return;
}

if ($arParams["TEMPLATE_TYPE"] == "USER_INVITATION" || $arParams["TEMPLATE_TYPE"] == "EXTRANET_INVITATION")
{
	$arParams["USER_TEXT"] = htmlspecialcharsback($arParams["USER_TEXT"]);

	if (isset($arParams["USER_ID_FROM"]))
	{
		$rsUsers = CUser::GetList(($by="ID"), ($order="ASC"), array("ID_EQUAL_EXACT" => $arParams["USER_ID_FROM"]), array("FIELDS" => array("NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
		if ($arUser = $rsUsers->Fetch())
		{
			$arResult["USER_NAME"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
				"NAME" => $arUser["NAME"],
				"LAST_NAME" => $arUser["LAST_NAME"],
				"SECOND_NAME" => $arUser["SECOND_NAME"],
				"LOGIN" => $arUser["LOGIN"]
			));

			if (intval($arUser["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 40, "height" => 40),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult["USER_PHOTO"] = $arFileTmp["src"];
				}
			}
		}
	}
}

if ($arParams["TEMPLATE_TYPE"] == "EXTRANET_INVITATION")
{
	$arParams["LINK"] = "https://".$arParams["SERVER_NAME"]."/extranet/confirm/?checkword=".$arParams["CHECKWORD"]."&user_id=".$arParams["USER_ID"];
}

if ($arParams["TEMPLATE_TYPE"] == "IM_NEW_NOTIFY" || $arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE")
{
	if (isset($arParams["FROM_USER_ID"]))
	{
		$rsUsers = CUser::GetList(($by="ID"), ($order="ASC"), array("ID_EQUAL_EXACT" => $arParams["FROM_USER_ID"]), array("FIELDS" => array("PERSONAL_PHOTO")));
		if ($arUser = $rsUsers->Fetch())
		{
			if (intval($arUser["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 40, "height" => 40),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult["USER_PHOTO"] = $arFileTmp["src"];
				}
			}
		}
	}

	$parser = new CTextParser();
	$parser->allow = array('ANCHOR' => 'N');
	$arParams["MESSAGE"] = $parser->convertText($arParams["MESSAGE"]);
}

if ($arParams["TEMPLATE_TYPE"] == "IM_NEW_MESSAGE_GROUP")
{
	$arResult["MESSAGES_FROM_USERS"] = array();
	$fromUserId = explode(",", $arParams["FROM_USER_ID"]);

	if (is_array($fromUserId) && !empty($fromUserId))
	{
		$rsUsers = CUser::GetList(($by="ID"), ($order="ASC"), array("ID" => implode("|", $fromUserId)), array("FIELDS" => array("ID", "PERSONAL_PHOTO")));
		while ($arUser = $rsUsers->Fetch())
		{
			if (intval($arUser["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 40, "height" => 40),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult["FROM_USERS"][$arUser["ID"]] = $arFileTmp["src"];
				}
			}
		}
	}

	$messagesFromUser = unserialize($arParams["~MESSAGES_FROM_USERS"]);

	foreach ($messagesFromUser as $userId => $message)
	{
		$arResult["MESSAGES_FROM_USERS"][$userId] = [
			"MESSAGE" => $message,
			"USER_PHOTO" => $arResult["FROM_USERS"][$userId]
		];
	}
}

$this->IncludeComponentTemplate();
?>