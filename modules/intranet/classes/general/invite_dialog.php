<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork;
use Bitrix\Main\UserTable;
use Bitrix\Main\FinderDestTable;

IncludeModuleLangFile(__FILE__);

class CIntranetInviteDialog
{
	public static $bSendPassword = false;

	public static function ShowInviteDialogLink($arParams = array())
	{
		CJSCore::Init(array('popup'));
		if (Loader::includeModule("bitrix24") && !CBitrix24::isMoreUserAvailable())
		{
			CBitrix24::initLicenseInfoPopupJS();
		}

		$arParams["MESS"] = array(
			"BX24_INVITE_TITLE_INVITE" => GetMessage("BX24_INVITE_TITLE_INVITE"),
			"BX24_INVITE_TITLE_ADD" => GetMessage("BX24_INVITE_TITLE_ADD"),
			"BX24_INVITE_BUTTON" => GetMessage("BX24_INVITE_BUTTON"),
			"BX24_CLOSE_BUTTON" => GetMessage("BX24_CLOSE_BUTTON"),
			"BX24_LOADING" => GetMessage("BX24_LOADING"),
		);
		return "B24.Bitrix24InviteDialog.ShowForm(".CUtil::PhpToJSObject($arParams).")";
	}

	public static function setSendPassword($value)
	{
		self::$bSendPassword = $value;
	}

	public static function getSendPassword()
	{
		return self::$bSendPassword;
	}

	public static function AddNewUser($SITE_ID, $arFields, &$strError)
	{
		global $APPLICATION, $USER;

		$ID_ADDED = 0;

		$iDepartmentId = intval($arFields["DEPARTMENT_ID"]);

		$siteIdByDepartmentId = self::getUserSiteId(array(
			"UF_DEPARTMENT" => $iDepartmentId,
			"SITE_ID" => $SITE_ID
		));

		$bExtranet = ($iDepartmentId <= 0);
		$arGroups = self::getUserGroups($siteIdByDepartmentId, $bExtranet);

		$strEmail = trim($arFields["ADD_EMAIL"]);
		$strName = trim($arFields["ADD_NAME"]);
		$strLastName = trim($arFields["ADD_LAST_NAME"]);
		$strPosition = trim($arFields["ADD_POSITION"]);

		if (strlen($strEmail) > 0)
		{
			$filter = array(
				"=EMAIL"=> $strEmail
			);

			if (Loader::includeModule('socialnetwork'))
			{
				$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array('bot', 'imconnector', 'replica', 'sale', 'shop', 'saleanonymous'));
				if (!empty($externalAuthIdList))
				{
					$filter['!@EXTERNAL_AUTH_ID'] = $externalAuthIdList;
				}
			}

			$rsUser = UserTable::getList(array(
				'filter' => $filter,
				'select' => array("ID", "EXTERNAL_AUTH_ID", "UF_DEPARTMENT")
			));

			if ($arUser = $rsUser->Fetch())
			{
				if ($arUser["EXTERNAL_AUTH_ID"] == 'email')
				{
					$ID_TRANSFERRED = self::TransferEmailUser($arUser["ID"], array(
						"GROUP_ID" => $arGroups,
						"UF_DEPARTMENT" => $iDepartmentId,
						"SITE_ID" => $siteIdByDepartmentId,
						"NAME" => $strName,
						"LAST_NAME" => $strLastName,
						"POSITION" => $strPosition
					));

					if (!$ID_TRANSFERRED)
					{
						if($e = $APPLICATION->GetException())
						{
							$strError = $e->GetString();
						}
						return false;
					}
					else
					{
						return $ID_TRANSFERRED;
					}
				}
				elseif (
					ModuleManager::isModuleInstalled('bitrix24')
					&& !ModuleManager::isModuleInstalled('extranet')
					&& $arUser["EXTERNAL_AUTH_ID"] == 'socservices'
					&& $iDepartmentId > 0
					&& (
						!isset($arUser["UF_DEPARTMENT"])
						|| (
							is_array($arUser["UF_DEPARTMENT"])
							&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
						)
						|| (
							!is_array($arUser["UF_DEPARTMENT"])
							&& intval($arUser["UF_DEPARTMENT"]) <= 0
						)
					) // past-extranet to intranet
				)
				{
					$ID_TRANSFERRED = self::TransferExtranetUser($arUser["ID"], array(
						"GROUP_ID" => $arGroups,
						"UF_DEPARTMENT" => $iDepartmentId,
						"SITE_ID" => $siteIdByDepartmentId
					));

					if (!$ID_TRANSFERRED)
					{
						if($e = $APPLICATION->GetException())
						{
							$strError = $e->GetString();
						}
						return false;
					}
					else
					{
						return $ID_TRANSFERRED;
					}
				}
				else
				{
					$strError = GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array(
						'#EMAIL#' => $strEmail
					));
				}
			}
		}

		if (strlen($strEmail) <= 0)
		{
			if (
				!isset($arFields["ADD_MAILBOX_ACTION"])
				|| !in_array($arFields["ADD_MAILBOX_ACTION"], array("create", "connect"))
				|| strlen($arFields['ADD_MAILBOX_USER']) <= 0
				|| strlen($arFields['ADD_MAILBOX_DOMAIN']) <= 0
			)
			{
				$strError = GetMessage("BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL");
			}
			else
			{
				// email from mailbox
				$strEmail = $arFields['ADD_MAILBOX_USER']."@".$arFields['ADD_MAILBOX_DOMAIN'];
			}
		}

		if (!$strError)
		{
			$strPassword = self::GeneratePassword($siteIdByDepartmentId, $bExtranet);
			self::setSendPassword($arFields["ADD_SEND_PASSWORD"] == "Y");

			$arUser = array(
				"LOGIN" => $strEmail,
				"NAME" => $strName,
				"LAST_NAME" => $strLastName,
				"EMAIL" => $strEmail,
				"PASSWORD" => $strPassword,
				"GROUP_ID" => $arGroups,
				"WORK_POSITION" => $strPosition,
				"LID" => $siteIdByDepartmentId,
				"UF_DEPARTMENT" => ($iDepartmentId > 0 ? array($iDepartmentId) : array())
			);

			if (!self::getSendPassword())
			{
				$arUser["CONFIRM_CODE"] = randString(8);
			}

			$obUser = new CUser;
			$ID_ADDED = $obUser->Add($arUser);

			if (!$ID_ADDED)
			{
				if($e = $APPLICATION->GetException())
				{
					$strError = $e->GetString();
				}
				else
				{
					$strError = $obUser->LAST_ERROR;
				}
			}
			else
			{
				if (self::getSendPassword())
				{
					$db_events = GetModuleEvents("main", "OnUserInitialize", true);
					foreach($db_events as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array($ID_ADDED, $arUser));
					}
				}

				if (
					$bExtranet
					&& !IsModuleInstalled("extranet")
					&& !IsModuleInstalled("bitrix24")
				)
				{
					$bExtranet = false;
				}

				$messageText = self::getInviteMessageText();

				$event = new CEvent;
				if (self::getSendPassword())
				{
					$rsSites = CSite::GetByID($siteIdByDepartmentId);
					$arSite = $rsSites->Fetch();
					$serverName = (
						strlen($arSite["SERVER_NAME"]) > 0
							? $arSite["SERVER_NAME"]
							: (
								defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
									? SITE_SERVER_NAME
									: COption::GetOptionString("main", "server_name", "")
							)
					);

					$url = (CMain::IsHTTPS() ? "https" : "http")."://".$serverName.$arSite["DIR"];
					$event->SendImmediate("INTRANET_USER_ADD", $siteIdByDepartmentId, array(
						"EMAIL_TO" => $arUser["EMAIL"],
						"LINK" => $url,
						"PASSWORD" => $strPassword,
						"USER_TEXT" => $messageText
					));
				}
				else
				{
					$dbUser = CUser::GetByID($ID_ADDED);
					$arUser = $dbUser->Fetch();

					if ($bExtranet)
					{
						$event->SendImmediate("EXTRANET_INVITATION", $siteIdByDepartmentId, array(
							"USER_ID" => $arUser["ID"],
							"CHECKWORD" => $arUser["CONFIRM_CODE"],
							"EMAIL" => $arUser["EMAIL"],
							"USER_TEXT" => ''
						));
					}
					elseif (IsModuleInstalled("bitrix24"))
					{
						$event->SendImmediate("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, array(
							"EMAIL_FROM" => $USER->GetEmail(),
							"USER_ID_FROM" => $USER->GetID(),
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
							"USER_TEXT" => $messageText
						));
					}
					else
					{
						$event->SendImmediate("INTRANET_USER_INVITATION", $siteIdByDepartmentId, array(
							"USER_ID_FROM" => $USER->GetID(),
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
							"USER_TEXT" => $messageText
						));
					}
				}
			}
		}

		return $ID_ADDED;
	}

	public static function RegisterNewUser($SITE_ID, $arFields, &$arError)
	{
		global $APPLICATION;

		$arCreatedUserId = array();

		$arEmailToRegister = array();
		$arEmailToReinvite = array();
		$arEmailUserId = $arExtranetUserId = array();

		$arEmailExist = array();
		$bExtranetUser = false;
		$bExtranetInstalled = (IsModuleInstalled("extranet") && strlen(COption::GetOptionString("extranet", "extranet_site")) > 0);

		if ($arFields["EMAIL"] <> '' || $arFields['PHONE'] <> '')
		{
			$isPhone = is_array($arFields['PHONE']) && !empty($arFields['PHONE']);
			$phoneCountryList = [];

			if($isPhone)
			{
				$arEmailOriginal = $arFields['PHONE'];
				$phoneCountryList = $arFields['PHONE_COUNTRY'];
			}
			else
			{
				$arEmailOriginal = preg_split("/[\n\r\t\\,;\\ ]+/", trim($arFields["EMAIL"]));
			}

			$arEmail = $errorEmails = array();
			$emailCnt = 0;

			foreach($arEmailOriginal as $index => $addr)
			{
				if ($emailCnt >= ($isPhone ? 5 : 100))
				{
					$arError = array($isPhone ? GetMessage("BX24_INVITE_DIALOG_PHONE_LIMIT_EXCEEDED") : GetMessage("BX24_INVITE_DIALOG_EMAIL_LIMIT_EXCEEDED"));
					return false;
				}

				if($isPhone)
				{
					$phoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($addr, $phoneCountryList[$index]);
					if($phoneNumber->isValid())
					{
						$arEmail[] = $phoneNumber->format(\Bitrix\Main\PhoneNumber\Format::E164);
						$emailCnt++;
					}
					else
					{
						$errorEmails[] = $addr;
					}
				}
				else
				{
					if(strlen($addr) > 0 && check_email($addr))
					{
						$arEmail[] = $addr;
						$emailCnt++;
					}
					else
					{
						$errorEmails[] = htmlspecialcharsbx($addr);
					}
				}
			}

			if (count($arEmailOriginal) > count($arEmail))
			{
				$arError = array(($isPhone ? GetMessage("BX24_INVITE_DIALOG_PHONE_ERROR"): GetMessage("BX24_INVITE_DIALOG_EMAIL_ERROR")).": ".implode(", ", $errorEmails));
				return false;
			}

			foreach($arEmail as $email)
			{
				if($isPhone)
				{
					$filter = array(
						"=PHONE_NUMBER" => $email
					);

					if (Loader::includeModule('socialnetwork'))
					{
						$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array('bot', 'imconnector', 'replica', 'sale', 'shop', 'saleanonymous'));
						if (!empty($externalAuthIdList))
						{
							$filter['!@USER.EXTERNAL_AUTH_ID'] = $externalAuthIdList;
						}
					}

					$rsUser = \Bitrix\Main\UserPhoneAuthTable::getList(array(
						'filter' => $filter,
						'select' => array("USER_ID", "USER_CONFIRM_CODE" => "USER.CONFIRM_CODE", "USER_EXTERNAL_AUTH_ID" => "USER.EXTERNAL_AUTH_ID", "USER_UF_DEPARTMENT" => "USER.UF_DEPARTMENT")
					));
				}
				else
				{
					$filter = array(
						"=EMAIL" => $email
					);

					if (Loader::includeModule('socialnetwork'))
					{
						$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array('bot', 'imconnector', 'replica', 'sale', 'shop', 'saleanonymous'));
						if (!empty($externalAuthIdList))
						{
							$filter['!@EXTERNAL_AUTH_ID'] = $externalAuthIdList;
						}
					}

					$rsUser = UserTable::getList(array(
						'filter' => $filter,
						'select' => array("ID", "CONFIRM_CODE", "EXTERNAL_AUTH_ID", "UF_DEPARTMENT")
					));
				}

				$bFound = false;
				while ($arUser = $rsUser->Fetch())
				{
					if($isPhone)
					{
						$arUser = array(
							'ID' => $arUser["USER_ID"],
							'CONFIRM_CODE' => $arUser["USER_CONFIRM_CODE"],
							'EXTERNAL_AUTH_ID' => $arUser["USER_ID"],
							'UF_DEPARTMENT' => $arUser["USER_UF_DEPARTMENT"],
						);
					}

					$bFound = true;

					if ($arUser["EXTERNAL_AUTH_ID"] == 'email')
					{
						$arEmailUserId[] = $arUser["ID"];
					}
					elseif (
						$arUser["CONFIRM_CODE"] != ""
						&& (
							!$bExtranetInstalled
							|| ( // both intranet
								isset($arFields["DEPARTMENT_ID"])
								&& intval($arFields["DEPARTMENT_ID"]) > 0
								&& isset($arUser["UF_DEPARTMENT"])
								&& (
									(
										is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"][0]) > 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"]) > 0
									)
								)
							)
							||
							(	// both extranet
								(
									!isset($arFields["DEPARTMENT_ID"])
									|| intval($arFields["DEPARTMENT_ID"]) <= 0
								)
								&& (
									!isset($arUser["UF_DEPARTMENT"])
									|| (
										is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"]) <= 0
									)
								)
							)
						)
					)
					{
						$arEmailToReinvite[] = array(
							"EMAIL" => $email,
							"REINVITE" => true,
							"ID" => $arUser["ID"],
							"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
							"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
						);
					}
					elseif (
						ModuleManager::isModuleInstalled('bitrix24')
						&& !ModuleManager::isModuleInstalled('extranet')
						&& $arUser["EXTERNAL_AUTH_ID"] == 'socservices'
						&& (
							isset($arFields["DEPARTMENT_ID"])
							&& intval($arFields["DEPARTMENT_ID"]) > 0
						)
						&& (
							!isset($arUser["UF_DEPARTMENT"])
							|| (
								is_array($arUser["UF_DEPARTMENT"])
								&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
							)
							|| (
								!is_array($arUser["UF_DEPARTMENT"])
								&& intval($arUser["UF_DEPARTMENT"]) <= 0
							)
						) // past-extranet to intranet
					)
					{
						$arExtranetUserId[] = $arUser["ID"];
					}
					else
					{
						$arEmailExist[] = $email;
					}
				}

				if (!$bFound)
				{
					$arEmailToRegister[] = array(
						"EMAIL" => $email,
						"REINVITE" => false
					);
				}
			}
		}

		if(!$isPhone)
		{
			if(
				isset($arFields["MESSAGE_TEXT"])
				&& (
					!Loader::includeModule('bitrix24')
					|| (
						CBitrix24::IsLicensePaid()
						&& !CBitrix24::IsDemoLicense()
					)
					|| CBitrix24::IsNfrLicense()
				)
			)
			{
				$messageText = $arFields["MESSAGE_TEXT"];
				CUserOptions::SetOption((IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet"), "invite_message_text", $arFields["MESSAGE_TEXT"]);
			}
			else
			{
				$messageText = GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");
			}

			if(
				empty($arEmailToRegister)
				&& empty($arEmailToReinvite)
				&& empty($arEmailUserId)
				&& empty($arExtranetUserId)
			)
			{
				$arError = array(
					(
					!empty($arEmailExist)
						? (
					count($arEmailExist) > 1
						? GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR2", array("#EMAIL_LIST#" => implode(', ', $arEmailExist)))
						: GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array("#EMAIL#" => $arEmailExist[0]))
					)
						: GetMessage("BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL_LIST")
					)
				);

				return false;
			}

			//reinvite users
			foreach($arEmailToReinvite as $userData)
			{
				self::InviteUser($userData, $messageText, array('checkB24' => false));
			}
		}
		else
		{
			// TODO: reinvite: self::InviteUserByPhone($userData)
		}

		$siteIdByDepartmentId = $arGroups = false;

		if (
			!empty($arEmailToRegister)
			|| !empty($arEmailUserId)
			|| !empty($arExtranetUserId)
		)
		{
			if (isset($arFields["DEPARTMENT_ID"]))
			{
				$arFields["UF_DEPARTMENT"] = $arFields["DEPARTMENT_ID"];
			}

			if (
				!(
					isset($arFields["UF_DEPARTMENT"])
					&& intval($arFields["UF_DEPARTMENT"]) > 0
				)
			)
			{
				if (!$bExtranetInstalled)
				{
					if (CModule::IncludeModule('iblock'))
					{
						$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
						$arIBlock = $rsIBlock->Fetch();
						$iblockID = $arIBlock["ID"];

						$db_up_department = CIBlockSection::GetList(
							array(),
							array(
								"SECTION_ID" => 0,
								"IBLOCK_ID" => $iblockID
							)
						);
						if ($ar_up_department = $db_up_department->Fetch())
						{
							$arFields["UF_DEPARTMENT"] = $ar_up_department['ID'];
						}
					}
				}
				else
				{
					$bExtranetUser = true;
				}
			}

			$siteIdByDepartmentId = self::getUserSiteId(array(
				"UF_DEPARTMENT" => (!$bExtranetUser ? $arFields["UF_DEPARTMENT"] : false),
				"SITE_ID" => $SITE_ID
			));

			$arGroups = self::getUserGroups($siteIdByDepartmentId, $bExtranetUser);
		}

		// transfer email users to employees or extranet
		if (!empty($arEmailUserId))
		{
			foreach ($arEmailUserId as $emailUserId)
			{
				$ID_TRANSFERRED = self::TransferEmailUser($emailUserId, array(
					"GROUP_ID" => $arGroups,
					"UF_DEPARTMENT" => $arFields["UF_DEPARTMENT"],
					"SITE_ID" => $siteIdByDepartmentId
				));

				if (!$ID_TRANSFERRED)
				{
					if($e = $APPLICATION->GetException())
					{
						$arError[] = $e->GetString();
					}
					return false;
				}
				else
				{
					$arCreatedUserId[] = $ID_TRANSFERRED;
				}
			}
		}

		// transfer past-extranet users to employees
		if (!empty($arExtranetUserId))
		{
			foreach ($arExtranetUserId as $extranetUserId)
			{
				$ID_TRANSFERRED = self::TransferExtranetUser($extranetUserId, array(
					"GROUP_ID" => $arGroups,
					"UF_DEPARTMENT" => $arFields["UF_DEPARTMENT"],
					"SITE_ID" => $siteIdByDepartmentId
				));

				if (!$ID_TRANSFERRED)
				{
					if($e = $APPLICATION->GetException())
					{
						$arError[] = $e->GetString();
					}
					return false;
				}
				else
				{
					$arCreatedUserId[] = $ID_TRANSFERRED;
				}
			}
		}

		//register users
		if (!empty($arEmailToRegister))
		{
			foreach ($arEmailToRegister as $userData)
			{
				if($isPhone)
				{
					$userData['LOGIN'] = $userData['EMAIL'];
					$userData['PHONE_NUMBER'] = $userData['EMAIL'];

					unset($userData['EMAIL']);
				}

				$userData["CONFIRM_CODE"] = randString(8);
				$userData["GROUP_ID"] = $arGroups;
				$userData["UF_DEPARTMENT"] = $arFields["UF_DEPARTMENT"];
				$ID = self::RegisterUser($userData, $siteIdByDepartmentId);

				if(is_array($ID))
				{
					$arError = $ID;
					return false;
				}
				else
				{
					$arCreatedUserId[] = $ID;
					$userData['ID'] = $ID;

					if(!$isPhone)
					{
						self::InviteUser($userData, $messageText, array('checkB24' => false));
					}
					else
					{
						//TODO: invite user self::InviteUserByPhone($userData);
					}
				}
			}
		}

		if (!empty($arEmailExist))
		{
			if($isPhone)
			{
				$arError = array(
					count($arEmailExist) > 1
						? GetMessage("BX24_INVITE_DIALOG_USER_PHONE_EXIST_ERROR2", array("#PHONE_LIST#" => implode(', ', $arEmailExist)))
						: GetMessage("BX24_INVITE_DIALOG_USER_PHONE_EXIST_ERROR1", array("#PHONE#" => $arEmailExist[0]))
				);
			}
			else
			{
				$arError = array(
					count($arEmailExist) > 1
						? GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR2", array("#EMAIL_LIST#" => implode(', ', $arEmailExist)))
						: GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array("#EMAIL#" => $arEmailExist[0]))
				);
			}

			return false;
		}
		else
		{
			return $arCreatedUserId;
		}
	}

	public static function inviteIntegrator($SITE_ID, $email, $messageText, &$strError)
	{
		CUserOptions::SetOption("bitrix24", "integrator_message_text", $messageText);

		$filter = array(
			"=LOGIN"=> $email,
			"!=EXTERNAL_AUTH_ID" => "imconnector"
		);

		$rsUser = UserTable::getList(array(
			'filter' => $filter,
			'select' => array("ID", "CONFIRM_CODE", "EXTERNAL_AUTH_ID", "UF_DEPARTMENT")
		));

		if  ($arUser = $rsUser->Fetch())
		{
			if (empty($arUser["CONFIRM_CODE"]))
			{
				$strError = GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array("#EMAIL#" => $email));
				return false;
			}
			else
			{
				$userData = array(
					"EMAIL" => $email,
					"REINVITE" => true,
					"ID" => $arUser["ID"],
					"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
					"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
				);

				self::InviteUser($userData, $messageText, array('checkB24' => false));
			}
		}
		else
		{
			$userData = array(
				"EMAIL" => $email,
				"REINVITE" => false
			);

			if (CModule::IncludeModule('iblock'))
			{
				$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
				$arIBlock = $rsIBlock->Fetch();
				$iblockID = $arIBlock["ID"];

				$db_up_department = CIBlockSection::GetList(
					array(),
					array(
						"SECTION_ID" => 0,
						"IBLOCK_ID" => $iblockID
					)
				);
				if ($ar_up_department = $db_up_department->Fetch())
				{
					$arFields["UF_DEPARTMENT"] = $ar_up_department['ID'];
				}
			}

			$arGroups = self::getAdminGroups($SITE_ID);
			if (CModule::IncludeModule('bitrix24'))
			{
				$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
				$arGroups[] = $integratorGroupId;
			}
			//register users
			$userData["CONFIRM_CODE"] = randString(8);
			$userData["GROUP_ID"] = $arGroups;
			$userData["UF_DEPARTMENT"] = $arFields["UF_DEPARTMENT"];

			$ID = self::RegisterUser($userData, $SITE_ID);
			if(is_array($ID))
			{
				$arError = $ID;
				return false;
			}
			else
			{
				$arCreatedUserId[] = $ID;
				$userData['ID'] = $ID;

				self::InviteUser($userData, $messageText, array('checkB24' => false));

				return $ID;
			}
		}
	}

	public static function getUserGroups($SITE_ID, $bExtranetUser = false)
	{
		$arGroups = array();

		if (
			$bExtranetUser
			&& CModule::IncludeModule("extranet")
		)
		{
			$extranetGroupID = CExtranet::GetExtranetUserGroupID();
			if (intval($extranetGroupID) > 0)
			{
				$arGroups[] = $extranetGroupID;
			}
		}
		else
		{
			$rsGroups = CGroup::GetList(
				$o="",
				$b="",
				array(
					"STRING_ID" => "EMPLOYEES_".$SITE_ID
				)
			);
			while($arGroup = $rsGroups->Fetch())
			{
				$arGroups[] = $arGroup["ID"];
			}
		}

		return $arGroups;
	}

	public static function getAdminGroups($SITE_ID)
	{
		$arGroups = array(1);
		$rsGroups = CGroup::GetList(
			$o="",
			$b="",
			array(
				"STRING_ID" => "PORTAL_ADMINISTRATION_".$SITE_ID
			)
		);
		while($arGroup = $rsGroups->Fetch())
		{
			$arGroups[] = $arGroup["ID"];
		}

		return $arGroups;
	}

	public static function checkUsersCount($cnt)
	{
		if (CModule::IncludeModule("bitrix24"))
		{
			$UserMaxCount = intval(COption::GetOptionString("main", "PARAM_MAX_USERS"));
			$currentUserCount = CBitrix24::getActiveUserCount();
			return $UserMaxCount <= 0 || $cnt <= $UserMaxCount - $currentUserCount;
		}
		return true;
	}

	public static function RegisterUser($userData, $SITE_ID = SITE_ID)
	{
		$bExtranetUser = (!isset($userData['UF_DEPARTMENT']) || empty($userData['UF_DEPARTMENT']));
		$strPassword = self::GeneratePassword($SITE_ID, $bExtranetUser);

		$arUser = array(
			"LOGIN" => isset($userData["LOGIN"]) ? $userData["LOGIN"] : $userData["EMAIL"],
			'EMAIL' => $userData['EMAIL'],
			"PASSWORD" => $strPassword,
			"CONFIRM_CODE" => $userData['CONFIRM_CODE'],
			"GROUP_ID" => $userData['GROUP_ID'],
			"LID" => $SITE_ID,
			"UF_DEPARTMENT" => (intval($userData["UF_DEPARTMENT"]) > 0 ? array($userData["UF_DEPARTMENT"]) : array())
		);

		if(isset($userData['PHONE_NUMBER']))
		{
			$arUser['PHONE_NUMBER'] = $userData['PHONE_NUMBER'];
			$arUser['PERSONAL_MOBILE'] = $userData['PHONE_NUMBER'];
		}

		if(isset($userData["ACTIVE"]))
		{
			$arUser["ACTIVE"] = $userData["ACTIVE"];
		}

		if(isset($userData['XML_ID']))
		{
			$arUser['XML_ID'] = $userData['XML_ID'];
		}

		$obUser = new CUser;
		$res = $obUser->Add($arUser);
		return ($res? $res : preg_split("/<br>/", $obUser->LAST_ERROR));
	}

	public static function InviteUserByPhone($arUser, $params = array())
	{


	}

	public static function InviteUser($arUser, $messageText, $params = array())
	{
		global $USER;

		if (
			!is_array($params)
			|| !isset($params['checkB24'])
			|| $params['checkB24'] !== false
		)
		{
			if (
				Loader::includeModule('bitrix24')
				&& (
					!CBitrix24::IsLicensePaid()
					|| CBitrix24::IsDemoLicense()
				)
				&& !CBitrix24::IsNfrLicense()
			)
			{
				$messageText = GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");
			}
		}

		$bExtranet = (
			IsModuleInstalled('extranet')
			&& (
				!isset($arUser["UF_DEPARTMENT"])
				|| (
					is_array($arUser["UF_DEPARTMENT"])
					&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
				)
				|| (
					!is_array($arUser["UF_DEPARTMENT"])
					&& intval($arUser["UF_DEPARTMENT"]) <= 0
				)
			)
		);

		$siteIdByDepartmentId = self::getUserSiteId(array(
			"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"],
			"SITE_ID" => SITE_ID
		));

		$event = new CEvent;
		if ($bExtranet)
		{
			$event->SendImmediate("EXTRANET_INVITATION", $siteIdByDepartmentId, array(
				"USER_ID" => $arUser["ID"],
				"USER_ID_FROM" => $USER->GetID(),
				"CHECKWORD" => $arUser["CONFIRM_CODE"],
				"EMAIL" => $arUser["EMAIL"],
				"USER_TEXT" => $messageText
			));
		}
		elseif (IsModuleInstalled("bitrix24"))
		{
			$event->SendImmediate("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, array(
				"EMAIL_FROM" => $USER->GetEmail(),
				"USER_ID_FROM" => $USER->GetID(),
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
				"USER_TEXT" => $messageText,
			));
		}
		else
		{
			$event->SendImmediate("INTRANET_USER_INVITATION", $siteIdByDepartmentId, array(
				"EMAIL_TO" => $arUser["EMAIL"],
				"USER_ID_FROM" => $USER->GetID(),
				"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
				"USER_TEXT" => $messageText,
			));
		}
	}

	public static function ReinviteUser($SITE_ID, $USER_ID)
	{
		$USER_ID = intval($USER_ID);

		$rsUser = CUser::GetList(
			($o = "ID"),
			($b = "DESC"),
			array("ID_EQUAL_EXACT" => $USER_ID),
			array("SELECT" => array("UF_DEPARTMENT"))
		);
		if($arUser = $rsUser->Fetch())
		{
			self::InviteUser($arUser, self::getInviteMessageText(), array('checkB24' => false));
			return true;
		}
		return false;
	}

	public static function ReinviteExtranetUser($SITE_ID, $USER_ID)
	{
		global $USER;

		$USER_ID = intval($USER_ID);

		$rsUser = CUser::GetList(
			($o = "ID"),
			($b = "DESC"),
			array("ID_EQUAL_EXACT" => $USER_ID)
		);

		if($arUser = $rsUser->Fetch())
		{
			$event = new CEvent;
			$arFields = Array(
				"USER_ID" => $USER_ID,
				"USER_ID_FROM" => $USER->GetID(),
				"CHECKWORD" => $arUser["CONFIRM_CODE"],
				"EMAIL" => $arUser["EMAIL"],
				"USER_TEXT" => self::getInviteMessageText()
			);
			$event->SendImmediate("EXTRANET_INVITATION", $SITE_ID, $arFields);
			return true;
		}
		return false;
	}

	public static function RequestToSonetGroups($arUserId, $arGroupCode, $arGroupName, $bExtranetUser = false)
	{
		global $APPLICATION, $USER;

		$arGroupToAdd = array();
		$strError = false;

		if (!is_array($arUserId))
		{
			$arUserId = array($arUserId);
		}

		if (
			is_array($arGroupCode)
			&& !empty($arGroupCode)
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			foreach($arGroupCode as $group_code)
			{
				if(
					$bExtranetUser
					&& preg_match('/^(SGN\d+)$/', $group_code, $match)
					&& is_array($arGroupName)
					&& isset($arGroupName[$match[1]])
					&& strlen($arGroupName[$match[1]]) > 0
					&& CModule::IncludeModule("extranet")
					&& (
						CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
						|| $APPLICATION->GetGroupRight("socialnetwork", false, "Y", "Y", array(CExtranet::GetExtranetSiteID(), false)) >= "K"
					)
				)
				{
					// check and create group, for extranet only

					$dbSubjects = CSocNetGroupSubject::GetList(
						array("SORT"=>"ASC", "NAME" => "ASC"),
						array("SITE_ID" => CExtranet::GetExtranetSiteID()),
						false,
						false,
						array("ID")
					);
					if ($arSubject = $dbSubjects->GetNext())
					{
						$arSocNetGroupFields = array(
							"NAME" => $arGroupName[$match[1]],
							"DESCRIPTION" => "",
							"VISIBLE" => "N",
							"OPENED" => "N",
							"CLOSED" => "N",
							"SUBJECT_ID" => $arSubject["ID"],
							"INITIATE_PERMS" => "E",
							"SPAM_PERMS" => "K",
							"SITE_ID" => array(SITE_ID, CExtranet::GetExtranetSiteID())
						);

						if ($group_id = CSocNetGroup::CreateGroup(
							$USER->GetID(),
							$arSocNetGroupFields,
							false
						))
						{
							$arGroupToAdd[] = $group_id;
						}
						elseif ($e = $APPLICATION->GetException())
						{
							$strError = $e->GetString();
						}
					}
				}
				elseif(preg_match('/^SG(\d+)$/', $group_code, $match))
				{
					$group_id = $match[1];
					if (
						($arGroup = CSocNetGroup::GetByID($group_id))
						&& ($arCurrentUserPerms = CSocNetUserToGroup::InitUserPerms($USER->GetID(), $arGroup, CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)))
						&& $arCurrentUserPerms["UserCanInitiate"]
						&& $arGroup["CLOSED"] != "Y"
					)
					{
						$arGroupToAdd[] = $group_id;
					}
				}
			}

			if (!$strError)
			{
				$arAccessCodes = array();
				foreach($arGroupToAdd as $group_id)
				{
					foreach($arUserId as $user_id)
					{
						if (!CSocNetUserToGroup::SendRequestToJoinGroup($USER->GetID(), $user_id, $group_id, "", false))
						{
							if ($e = $APPLICATION->GetException())
							{
								$strError .= $e->GetString();
							}
						}
					}

					$arAccessCodes[] = 'SG'.$group_id;
				}
			}
		}

		return $strError;
	}

	public static function OnAfterUserAuthorize($arParams)
	{
		global $CACHE_MANAGER;

		if (
			isset($arParams['update'])
			&& $arParams['update'] === false
		)
		{
			return false;
		}

		if ($arParams['user_fields']['ID'] <= 0)
		{
			return false;
		}

		if (
			array_key_exists('LAST_LOGIN', $arParams['user_fields'])
			&& strlen(trim($arParams['user_fields']['LAST_LOGIN'])) <= 0 // do not check CONFIRM_CODE, please
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$dbRelation = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $arParams['user_fields']['ID'],
					"ROLE" => SONET_ROLES_REQUEST,
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);
			while ($arRelation = $dbRelation->Fetch())
			{
				if (CSocNetUserToGroup::UserConfirmRequestToBeMember($arParams['user_fields']['ID'], $arRelation["ID"], false))
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->ClearByTag("sonet_user2group_G".$arRelation["GROUP_ID"]);
						$CACHE_MANAGER->ClearByTag("sonet_user2group_U".$arParams['user_fields']['ID']);
					}

					if (CModule::IncludeModule("im"))
					{
						CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".$arParams['user_fields']['ID']."|".intval($arRelation["ID"]));
					}
				}
			}
		}
	}

	private static function GeneratePassword($SITE_ID, $bExtranetUser)
	{
		global $USER;

		$arGroupID = self::getUserGroups($SITE_ID, $bExtranetUser);
		$arPolicy = $USER->GetGroupPolicy($arGroupID);

		$password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);
		if($password_min_length <= 0)
		{
			$password_min_length = 6;
		}

		$password_chars = array(
			"abcdefghijklnmopqrstuvwxyz",
			"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
			"0123456789",
		);

		if($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
		{
			$password_chars[] = ",.<>/?;:'\"[]{}\\|`~!@#\$%^&*()-_+=";
		}

		$password = randString($password_min_length, $password_chars);

		return $password;
	}

	public static function TransferEmailUser($userId, $arParams = array())
	{
		global $APPLICATION;

		$userId = intval($userId);

		if (!($arUser = self::checkUserId($userId)))
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_ID_NO_EXIST_ERROR"));
			return false;
		}

		$dbUser = CUser::GetList(
			$o = "ID",
			$b = "ASC",
			array(
				"=EMAIL" => $arUser["EMAIL"],
				"EXTERNAL_AUTH_ID" => "",
			),
			array("FIELDS" => array("ID"))
		);
		if ($arUserCheck = $dbUser->Fetch())
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array("#EMAIL#" => $arUser["EMAIL"])));
			return false;
		}

		if (
			!isset($arParams["SITE_ID"])
			|| empty($arParams["SITE_ID"])
		)
		{
			$arParams["SITE_ID"] = SITE_ID;
		}

		$bExtranetUser = (
			!isset($arParams['UF_DEPARTMENT'])
			|| empty($arParams['UF_DEPARTMENT'])
		);

		if (
			!isset($arParams["GROUP_ID"])
			|| empty($arParams["GROUP_ID"])
		)
		{
			$arParams["GROUP_ID"] = self::getUserGroups($arParams["SITE_ID"], $bExtranetUser);
		}

		self::$bSendPassword = true;
		$strPassword = self::GeneratePassword($arParams["SITE_ID"], $bExtranetUser);

		$arFields = array(
			"EXTERNAL_AUTH_ID" => '',
			"GROUP_ID" => $arParams['GROUP_ID'],
			"PASSWORD" => $strPassword,
			"EMAIL" => $arUser["EMAIL"]
		);

		if (
			isset($arParams["UF_DEPARTMENT"])
			&& intval($arParams["UF_DEPARTMENT"]) > 0
		)
		{
			$arFields["UF_DEPARTMENT"] = array($arParams["UF_DEPARTMENT"]);
		}

		if (
			isset($arParams["NAME"])
			&& strlen($arParams["NAME"]) > 0
		)
		{
			$arFields["NAME"] = $arParams["NAME"];
		}
		else
		{
			$arFields["NAME"] = $arUser["NAME"];
		}

		if (
			isset($arParams["LAST_NAME"])
			&& strlen($arParams["LAST_NAME"]) > 0
		)
		{
			$arFields["LAST_NAME"] = $arParams["LAST_NAME"];
		}
		else
		{
			$arFields["LAST_NAME"] = $arUser["LAST_NAME"];
		}

		if (
			isset($arParams["POSITION"])
			&& strlen($arParams["POSITION"]) > 0
		)
		{
			$arFields["POSITION"] = $arParams["POSITION"];
		}

		foreach(GetModuleEvents("intranet", "OnTransferEMailUser", true) as $arEvent)
		{
			if(!ExecuteModuleEventEx($arEvent, array(&$arFields)))
			{
				return false;
			}
		}

		$obUser = new CUser;
		if ($obUser->Update($userId, $arFields))
		{
			$dbUser = CUser::GetByID($userId);
			$arUser = $dbUser->Fetch();

			$arFields['ID'] = $userId;
			foreach(GetModuleEvents("intranet", "OnAfterTransferEMailUser", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arUser));
			}

			self::sentTransferNotification($arUser, $arFields, $arParams);

			return $userId;
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_ERROR_USER_TRANSFER"));
			return false;
		}
	}

	public static function TransferExtranetUser($userId, $arParams = array())
	{
		global $APPLICATION;

		$userId = intval($userId);

		if (!($arUser = self::checkUserId($userId)))
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_ID_NO_EXIST_ERROR"));
			return false;
		}

		$dbUser = CUser::GetList(
			$o = "ID",
			$b = "ASC",
			array(
				"=EMAIL" => $arUser["EMAIL"],
				"=EXTERNAL_AUTH_ID" => 'socservices',
			),
			array(
				"FIELDS" => array("ID", "ADMIN_NOTES", "EXTERNAL_AUTH_ID"),
				"SELECT" => array("UF_DEPARTMENT")
			)
		);
		if (
			($arUserCheck = $dbUser->Fetch())
			&& isset($arUserCheck["UF_DEPARTMENT"])
			&& (
				(
					is_array($arUserCheck["UF_DEPARTMENT"])
					&& intval($arUserCheck["UF_DEPARTMENT"][0]) > 0
				)
				|| (
					!is_array($arUserCheck["UF_DEPARTMENT"])
					&& intval($arUserCheck["UF_DEPARTMENT"]) > 0
				)
			)
		)
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array("#EMAIL#" => $arUser["EMAIL"])));
			return false;
		}

		if (
			!isset($arParams["SITE_ID"])
			|| empty($arParams["SITE_ID"])
		)
		{
			$arParams["SITE_ID"] = SITE_ID;
		}

		$bExtranetUser = (
			!isset($arParams['UF_DEPARTMENT'])
			|| empty($arParams['UF_DEPARTMENT'])
		);

		if (
			!isset($arParams["GROUP_ID"])
			|| empty($arParams["GROUP_ID"])
		)
		{
			$arParams["GROUP_ID"] = self::getUserGroups($arParams["SITE_ID"], $bExtranetUser);
		}

		self::$bSendPassword = true;
		$arFields = array(
			"EXTERNAL_AUTH_ID" => $arUser["EXTERNAL_AUTH_ID"],
			"GROUP_ID" => $arParams['GROUP_ID'],
			"PASSWORD" => \Bitrix\Main\Localization\Loc::getMessage('BX24_INVITE_DIALOG_PASSWORD_SAME'),
			"NAME" => $arUser["NAME"],
			"LAST_NAME" => $arUser["LAST_NAME"],
			"EMAIL" => $arUser["EMAIL"],
			"UF_DEPARTMENT" => array($arParams["UF_DEPARTMENT"]),
			"ADMIN_NOTES" => str_replace("~deactivated~", "", $arUser["ADMIN_NOTES"]),
			"ACTIVE" => "Y"
		);

		if (
			isset($arParams["POSITION"])
			&& strlen($arParams["POSITION"]) > 0
		)
		{
			$arFields["POSITION"] = $arParams["POSITION"];
		}

		foreach(GetModuleEvents("intranet", "OnTransferExtranetUser", true) as $arEvent)
		{
			if(!ExecuteModuleEventEx($arEvent, array(&$arFields)))
			{
				return false;
			}
		}

		$obUser = new CUser;
		if ($obUser->Update($userId, $arFields))
		{
			$dbUser = CUser::GetByID($userId);
			$arUser = $dbUser->Fetch();

			$arFields['ID'] = $userId;
			foreach(GetModuleEvents("intranet", "OnAfterTransferExtranetUser", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arUser));
			}

			self::sentTransferNotification($arUser, $arFields, $arParams);

			return $userId;
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_USER_TRANSFER"));
			return false;
		}
	}

	private static function checkUserId($userId = 0)
	{
		if ($userId <= 0)
		{
			return false;
		}

		$dbUser = CUser::GetByID($userId);
		$arUser = $dbUser->Fetch();
		if (!$arUser)
		{
			return false;
		}

		return $arUser;
	}

	private static function sentTransferNotification($arUser, $arFields, $arParams)
	{
		global $USER;

		$siteIdToSend = self::getUserSiteId(array(
			"UF_DEPARTMENT" => $arParams["UF_DEPARTMENT"],
			"SITE_ID" => $arParams["SITE_ID"]
		));

		$messageText = self::getInviteMessageText();

		$event = new CEvent;
		if(self::$bSendPassword)
		{
			$rsSites = CSite::GetByID($siteIdToSend);
			$arSite = $rsSites->Fetch();
			$serverName = (
				strlen($arSite["SERVER_NAME"]) > 0
					? $arSite["SERVER_NAME"]
					: (
						defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
							? SITE_SERVER_NAME
							: COption::GetOptionString("main", "server_name", "")
					)
			);

			$event->SendImmediate("INTRANET_USER_ADD", $arParams["SITE_ID"], array(
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => (CMain::IsHTTPS() ? "https" : "http")."://".$serverName.$arSite["DIR"],
				"PASSWORD" => $arFields["PASSWORD"],
				"USER_TEXT" => $messageText
			));
		}
		else
		{
			if(IsModuleInstalled("bitrix24"))
			{
				$event->SendImmediate("BITRIX24_USER_INVITATION", $arParams["SITE_ID"], array(
					"EMAIL_FROM" => $USER->GetEmail(),
					"USER_ID_FROM" => $USER->GetID(),
					"EMAIL_TO" => $arUser["EMAIL"],
					"LINK" => self::getInviteLink($arUser, $siteIdToSend),
					"USER_TEXT" => $messageText
				));
			}
			else
			{
				$event->SendImmediate("INTRANET_USER_INVITATION", $arParams["SITE_ID"], array(
					"EMAIL_TO" => $arUser["EMAIL"],
					"USER_ID_FROM" => $USER->GetID(),
					"LINK" => self::getInviteLink($arUser, $siteIdToSend),
					"USER_TEXT" => $messageText
				));
			}
		}
	}

	public static function getUserSiteId($arParams = array())
	{
		$bExtranet = (
			!isset($arParams["UF_DEPARTMENT"])
			|| intval($arParams["UF_DEPARTMENT"]) <= 0
		);

		if (
			$bExtranet
			&& CModule::IncludeModule("extranet")
		)
		{
			$siteId = CExtranet::GetExtranetSiteID();
		}
		elseif (IsModuleInstalled("bitrix24"))
		{
			$siteId = (
				isset($arParams["SITE_ID"])
				&& !empty($arParams["SITE_ID"])
					? $arParams["SITE_ID"]
					: SITE_ID
			);
		}
		else
		{
			CModule::IncludeModule('socialnetwork');
			$arSite = CSocNetLogComponent::GetSiteByDepartmentId(intval($arParams["UF_DEPARTMENT"]));
			$siteId = $arSite["LID"];
		}

		return $siteId;
	}

	public static function getInviteLink($arUser, $siteId)
	{
		$rsSites = CSite::GetByID($siteId);
		$arSite = $rsSites->Fetch();
		$serverName = (
			strlen($arSite["SERVER_NAME"]) > 0
				? $arSite["SERVER_NAME"]
				: (
			defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
				? SITE_SERVER_NAME
				: COption::GetOptionString("main", "server_name", "")
			)
		);

		return CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$arUser["ID"]."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName);
	}

	public static function getInviteMessageText()
	{
		return (
			($userMessageText = \Bitrix\Main\Config\Option::get(ModuleManager::isModuleInstalled("bitrix24") ? "bitrix24" : "intranet", "invite_message_text"))
			&& (
				!Loader::includeModule('bitrix24')
				|| (
					CBitrix24::IsLicensePaid()
					&& !CBitrix24::IsDemoLicense()
				)
				|| CBitrix24::IsNfrLicense()
			)
				? $userMessageText
				: GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1")
		);
	}

	public static function logAction($arUserId, $module, $action, $label)
	{
		if (function_exists('AddEventToStatFile'))
		{
			if (!is_array($arUserId))
			{
				$arUserId = array($arUserId);
			}

			foreach ($arUserId as $userId)
			{
				if ($userId > 0)
				{
					AddEventToStatFile($module, $action, $label, $userId);
				}
			}
		}
	}
}
