<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

function RegisterNewUser($SITE_ID, $arFields)
{
	global $USER;

	$arEmailToRegister = array();
	$arEmailToReinvite = array();
	$arEmailExist = array();

	if ($arFields["EMAIL"] <> '')
	{
		$arEmailOriginal = preg_split("/[\n\r\t\\,;\\ ]+/", trim($arFields["EMAIL"]));

		$arEmail = array();
		foreach($arEmailOriginal as $addr)
		{
			if($addr <> '' && check_email($addr))
			{
				$arEmail[] = $addr;
			}
		}
		if (count($arEmailOriginal) > count($arEmail))
		{
			return array(GetMessage("BX24_INVITE_DIALOG_EMAIL_ERROR"));
		}

		foreach($arEmail as $email)
		{
			$arFilter = array(
				"=EMAIL"=>$email
			);

			$rsUser = CUser::GetList("id", "asc", $arFilter);
			$bFound = false;
			while ($arUser = $rsUser->GetNext())
			{
				$bFound = true;

				if ($arUser["LAST_LOGIN"] == "")
				{
					$arEmailToReinvite[] = array(
						"EMAIL" => $email,
						"REINVITE" => true,
						"ID" => $arUser["ID"],
						"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
					);
				}
				else
				{
					$arEmailExist[] = $email;
				}
			}

			if (!$bFound )
			{
				$arEmailToRegister[] = array("EMAIL" => $email, "REINVITE" => false);
			}
		}
	}

	$messageText = (isset($arFields["MESSAGE_TEXT"])) ? htmlspecialcharsbx($arFields["MESSAGE_TEXT"]) : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");
	if (isset($arFields["MESSAGE_TEXT"]))
	{
		CUserOptions::SetOption("bitrix24", "invite_message_text", $arFields["MESSAGE_TEXT"]);
	}

	//reinvite users
	foreach ($arEmailToReinvite as $userData)
	{
		$event = new CEvent;
		$event->SendImmediate("BITRIX24_USER_INVITATION", $SITE_ID, array(
			"EMAIL_FROM" => $USER->GetEmail(),
			"EMAIL_TO" => $userData["EMAIL"],
			"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$userData["ID"]."&checkword=".urlencode($userData["CONFIRM_CODE"])),
			"USER_TEXT" => $messageText,
		));
	}

	//register users
	if (!empty($arEmailToRegister))
	{
		$arGroups = array();
		$rsGroups = CGroup::GetList('', '', array(
			"STRING_ID" => "EMPLOYEES_".$SITE_ID,
		));
		while($arGroup = $rsGroups->Fetch())
			$arGroups[] = $arGroup["ID"];

		$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
		$arIBlock = $rsIBlock->Fetch();
		$iblockID = $arIBlock["ID"];

		if (!(isset($arFields["UF_DEPARTMENT"]) && intval($arFields["UF_DEPARTMENT"]) > 0))
		{
			$db_up_department = CIBlockSection::GetList(Array(), Array("SECTION_ID"=>0, "IBLOCK_ID"=>$iblockID));
			if ($ar_up_department = $db_up_department->Fetch())
			{
				$arFields["UF_DEPARTMENT"] = $ar_up_department['ID'];
			}
		}

		foreach ($arEmailToRegister as $userData)
		{
			$arUser = array(
				"LOGIN" => $userData["EMAIL"],
				"EMAIL" => $userData["EMAIL"],
				"UF_DEPARTMENT" => array($arFields["UF_DEPARTMENT"]),
				"PASSWORD" => randString(12, $password_chars = array(
					"abcdefghijklnmopqrstuvwxyz",
					"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
					"0123456789",
					"(*)",
				)),
				"CONFIRM_CODE" => randString(8),
				"GROUP_ID" => $arGroups,
			);

			$User = new CUser;
			$ID = $User->Add($arUser);

			if(!$ID)
			{
				$arErrors = preg_split("/<br>/", $User->LAST_ERROR);
				return $arErrors;
			}
			else
			{
				$event = new CEvent;
				$event->SendImmediate("BITRIX24_USER_INVITATION", $SITE_ID, array(
					"EMAIL_FROM" => $USER->GetEmail(),
					"EMAIL_TO" => $userData["EMAIL"],
					"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$ID."&checkword=".urlencode($arUser["CONFIRM_CODE"])),
					"USER_TEXT" => $messageText,
				));
			}
		}
		return true;
	}
	if (!empty($arEmailExist))
	{
		return array(GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR"));
	}
	return true;
}

function ReinviteUser($SITE_ID, $USER_ID)
{
	global $USER;
	$USER_ID = intval($USER_ID);

	$rsUser = CUser::GetList(
		"ID",
		"DESC",
		array("ID_EQUAL_EXACT" => $USER_ID)
	);
	if($arUser = $rsUser->Fetch())
	{
		$messageText = ($userMessageText = CUserOptions::GetOption("bitrix24", "invite_message_text")) ? htmlspecialcharsbx($userMessageText) : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");

		$event = new CEvent;
		$event->SendImmediate("BITRIX24_USER_INVITATION", $SITE_ID, array(
			"EMAIL_FROM" => $USER->GetEmail(),
			"EMAIL_TO" => $arUser["EMAIL"],
			"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$USER_ID."&checkword=".urlencode($arUser["CONFIRM_CODE"])),
			"USER_TEXT" => $messageText,
		));
		return true;
	}
	return false;
}
