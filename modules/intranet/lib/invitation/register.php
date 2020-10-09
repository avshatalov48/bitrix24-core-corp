<?
namespace Bitrix\Intranet\Invitation;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialservices\Network;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork;
use Bitrix\Intranet\Invitation;

class Register
{
	public static function checkPhone(&$item)
	{
		$phoneCountry = isset($item["PHONE_COUNTRY"]) ? $item["PHONE_COUNTRY"] : "";
		$phoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($item["PHONE"], $phoneCountry);

		if ($phoneNumber->isValid())
		{
			$item["PHONE_NUMBER"] = $phoneNumber->format(\Bitrix\Main\PhoneNumber\Format::E164);
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function checkItems($items, &$errors)
	{
		$emailItems = $phoneItems = [];
		$errorEmailItems = $errorPhoneItems = [];
		$phoneCnt = $emailCnt = 0;

		foreach ($items as $item)
		{
			if (isset($item["PHONE"]))
			{
				$item["PHONE"] = trim($item["PHONE"]);
				if (self::checkPhone($item))
				{
					$phoneItems[] = $item;
					$phoneCnt++;
				}
				else
				{
					$errorPhoneItems[] = $item["PHONE"];
				}
			}
			elseif (isset($item["EMAIL"]))
			{
				$item["EMAIL"] = trim($item["EMAIL"]);
				if (check_email($item["EMAIL"]))
				{
					$emailItems[] = $item;
					$emailCnt++;
				}
				else
				{
					$errorEmailItems[] = $item["EMAIL"];
				}
			}
		}

		if ($phoneCnt >= 5)
		{
			$errors[] = Loc::getMessage("INTRANET_INVITATION_PHONE_LIMIT_EXCEEDED");
		}

		if ($emailCnt >= 100)
		{
			$errors[] = Loc::getMessage("INTRANET_INVITATION_EMAIL_LIMIT_EXCEEDED");
		}

		if (!empty($errorEmailItems))
		{
			$errors[] = Loc::getMessage("INTRANET_INVITATION_EMAIL_ERROR")." ".implode(", ", $errorEmailItems);
		}

		if (!empty($errorPhoneItems))
		{
			$errors[] = Loc::getMessage("INTRANET_INVITATION_PHONE_ERROR")." ".implode(", ", $errorPhoneItems);
		}

		return [
			"PHONE_ITEMS" => $phoneItems,
			"EMAIL_ITEMS" => $emailItems
		];
	}

	public static function checkExistingUserByPhone($phoneItems)
	{
		$arPhoneToReinvite = [];
		$arPhoneExist = [];
		$arPhoneToRegister = [];

		$bExtranetInstalled = (IsModuleInstalled("extranet")
			&& \COption::GetOptionString("extranet", "extranet_site") <> '');

		foreach ($phoneItems as $item)
		{
			$filter = array(
				"=PHONE_NUMBER" => $item["PHONE_NUMBER"]
			);

			if (Loader::includeModule('socialnetwork'))
			{
				$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array('bot', 'imconnector', 'replica', 'sale', 'saleanonymous'));
				if (!empty($externalAuthIdList))
				{
					$filter['!=USER.EXTERNAL_AUTH_ID'] = $externalAuthIdList;
				}
			}

			$rsUser = \Bitrix\Main\UserPhoneAuthTable::getList(array(
			   'filter' => $filter,
			   'select' => array(
				   "USER_ID",
				   "USER_CONFIRM_CODE" => "USER.CONFIRM_CODE",
				   "USER_EXTERNAL_AUTH_ID" => "USER.EXTERNAL_AUTH_ID",
				   "USER_UF_DEPARTMENT" => "USER.UF_DEPARTMENT"
			   )
		   ));

			$bFound = false;
			while ($arUser = $rsUser->Fetch())
			{
				$arUser = array(
					'ID'               => $arUser["USER_ID"],
					'CONFIRM_CODE'     => $arUser["USER_CONFIRM_CODE"],
					'EXTERNAL_AUTH_ID' => $arUser["USER_ID"],
					'UF_DEPARTMENT'    => $arUser["USER_UF_DEPARTMENT"],
				);

				$bFound = true;

				if (
					$arUser["CONFIRM_CODE"] != ""
					&& (
						!$bExtranetInstalled
						|| ( // both intranet
							isset($item["UF_DEPARTMENT"]) && !empty($item["UF_DEPARTMENT"])
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
							(!isset($item["UF_DEPARTMENT"]) || empty($item["UF_DEPARTMENT"]))
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
					$arPhoneToReinvite[] = array(
						"PHONE_NUMBER" => $item["PHONE_NUMBER"],
						"REINVITE" => true,
						"ID" => $arUser["ID"],
						"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
						"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
					);
				}
				else
				{
					$arPhoneExist[] = $item["PHONE_NUMBER"];
				}
			}

			if (!$bFound)
			{
				$item["REINVITE"] = false;
				$arPhoneToRegister[] = $item;
			}
		}

		return [
			"PHONE_TO_REINVITE" => $arPhoneToReinvite,
			"PHONE_EXIST" => $arPhoneExist,
			"PHONE_TO_REGISTER" => $arPhoneToRegister
		];
	}

	public static function checkExistingUserByEmail($emailItems)
	{
		$arUserForTransfer = [];
		$arEmailToReinvite = [];
		$arEmailExist = [];
		$arEmailToRegister = [];

		$bExtranetInstalled = (IsModuleInstalled("extranet")
			&& \COption::GetOptionString("extranet", "extranet_site") <> '');

		foreach ($emailItems as $item)
		{
			$filter = array(
				"=EMAIL" => $item["EMAIL"]
			);

			if (Loader::includeModule('socialnetwork'))
			{
				$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array('bot', 'imconnector', 'replica', 'sale', 'saleanonymous'));
				if (!empty($externalAuthIdList))
				{
					$filter['!=EXTERNAL_AUTH_ID'] = $externalAuthIdList;
				}
			}

			$rsUser = UserTable::getList([
				'filter' => $filter,
				'select' => array("ID", "CONFIRM_CODE", "EXTERNAL_AUTH_ID", "UF_DEPARTMENT")
			]);

			$bFound = false;
			while ($arUser = $rsUser->Fetch())
			{
				$bFound = true;

				if ($arUser["EXTERNAL_AUTH_ID"] == 'email' || $arUser["EXTERNAL_AUTH_ID"] == 'shop')
				{
					if (isset($item["UF_DEPARTMENT"]))
					{
						$arUser["UF_DEPARTMENT"] = $item["UF_DEPARTMENT"];
					}
					$arUserForTransfer[] = $arUser;
				}
				elseif (
					$arUser["CONFIRM_CODE"] != ""
					&& (
						!$bExtranetInstalled
						|| ( // both intranet
							isset($item["UF_DEPARTMENT"]) && !empty($item["UF_DEPARTMENT"])
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
							(!isset($item["UF_DEPARTMENT"]) || empty($item["UF_DEPARTMENT"]))
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
						"EMAIL" => $item["EMAIL"],
						"REINVITE" => true,
						"ID" => $arUser["ID"],
						"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
						"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
					);
				}
				else
				{
					$arEmailExist[] = $item["EMAIL"];
				}
			}

			if (!$bFound)
			{
				$item["REINVITE"] = false;
				$arEmailToRegister[] = $item;
			}
		}

		return [
			"TRANSFER_USER" => $arUserForTransfer,
			"EMAIL_TO_REINVITE" => $arEmailToReinvite,
			"EMAIL_EXIST" => $arEmailExist,
			"EMAIL_TO_REGISTER" => $arEmailToRegister
		];
	}

	public static function transferUser($usersForTransfer, &$errors)
	{
		global $APPLICATION;

		$transferedUserIds = [];

		foreach ($usersForTransfer as $user)
		{
			$bExtranetUser = !isset($user["UF_DEPARTMENT"]) || empty($user["UF_DEPARTMENT"]);
			$siteIdByDepartmentId = self::getUserSiteId(array(
				"UF_DEPARTMENT" => isset($user["UF_DEPARTMENT"]) && is_array($user["UF_DEPARTMENT"])
					? $user["UF_DEPARTMENT"][0] : "",
				"SITE_ID" => SITE_ID
			));

			$userGroups = \CIntranetInviteDialog::getUserGroups($siteIdByDepartmentId, $bExtranetUser);
			if ($user["EXTERNAL_AUTH_ID"] === "shop" && Loader::includeModule("crm"))
			{
				$userGroups[] = \Bitrix\Crm\Order\BuyerGroup::getSystemGroupId();
			}

			$transferedUserId = \CIntranetInviteDialog::TransferEmailUser($user["ID"], array(
				"GROUP_ID" => $userGroups,
				"UF_DEPARTMENT" => $user["UF_DEPARTMENT"],
				"SITE_ID" => SITE_ID
			));

			if (!$transferedUserId)
			{
				if($e = $APPLICATION->GetException())
				{
					$arError[] = $e->GetString();
				}
				return false;
			}
			else
			{
				$transferedUserIds[] = $transferedUserId;
			}
		}

		return $transferedUserIds;
	}

	public static function registerUsersByPhone($items, &$errors)
	{
		$invitedUserIdList = [];
		foreach ($items as $userData)
		{
			$bExtranet = isset($userData["UF_DEPARTMENT"]) ? false : true;
			$siteIdByDepartmentId = \CIntranetInviteDialog::getUserSiteId(array(
				"UF_DEPARTMENT" => isset($userData["UF_DEPARTMENT"]) && is_array($userData["UF_DEPARTMENT"])
					  ? $userData["UF_DEPARTMENT"][0] : "",
				"SITE_ID" => SITE_ID
			));
			$arGroups = \CIntranetInviteDialog::getUserGroups($siteIdByDepartmentId, $bExtranet);

			$userData['LOGIN'] = $userData['PHONE_NUMBER'];
			$userData["CONFIRM_CODE"] = randString(8);
			$userData["GROUP_ID"] = $arGroups;

			$ID = \CIntranetInviteDialog::RegisterUser($userData, SITE_ID);

			if (is_array($ID))
			{
				$errors = array_merge($errors, $ID);

				return false;
			}
			else
			{
				$arCreatedUserId[] = $ID;
				$invitedUserIdList[] = $ID;
				$userData['ID'] = $ID;

				//TODO: invite user self::InviteUserByPhone($userData);
			}
		}

		if (!empty($invitedUserIdList))
		{
			Invitation::add([
								'USER_ID' => $invitedUserIdList,
								'TYPE' => Invitation::TYPE_PHONE
							]);
		}

		return $invitedUserIdList;
	}

	public static function registerUsersByEmail($items, &$errors)
	{
		$invitedUserIdList = [];
		foreach ($items as $userData)
		{
			$isExtranet = isset($userData["UF_DEPARTMENT"]) ? false : true;
			$siteIdByDepartmentId = \CIntranetInviteDialog::getUserSiteId(array(
				"UF_DEPARTMENT" => isset($userData["UF_DEPARTMENT"]) && is_array($userData["UF_DEPARTMENT"])
					? $userData["UF_DEPARTMENT"][0] : "",
				"SITE_ID" => SITE_ID
			));
			$arGroups = \CIntranetInviteDialog::getUserGroups($siteIdByDepartmentId, $isExtranet);

			$userData["CONFIRM_CODE"] = randString(8);
			$userData["GROUP_ID"] = $arGroups;
			$ID = \CIntranetInviteDialog::RegisterUser($userData, SITE_ID);

			if (is_array($ID))
			{
				$errors = array_merge($errors, $ID);

				return false;
			}
			else
			{
				$arCreatedUserId[] = $ID;
				$invitedUserIdList[] = $ID;
				$userData['ID'] = $ID;

				\CIntranetInviteDialog::InviteUser($userData, Loc::getMessage("INTRANET_INVITATION_INVITE_MESSAGE_TEXT"), array('checkB24' => false));
			}
		}

		if (!empty($invitedUserIdList))
		{
			Invitation::add([
								'USER_ID' => $invitedUserIdList,
								'TYPE' => Invitation::TYPE_EMAIL
							]);
		}

		return $invitedUserIdList;
	}

	public static function inviteNewUsers($SITE_ID, $fields, &$errors = [])
	{
		if (!is_array($fields) || empty($fields))
		{
			return false;
		}

		$res = self::checkItems($fields["ITEMS"], $errors);

		if (!empty($errors))
		{
			return false;
		}

		$emailItems = $res["EMAIL_ITEMS"];
		$phoneItems = $res["PHONE_ITEMS"];

		$resPhone =	self::checkExistingUserByPhone($phoneItems);
		$resEmail = self::checkExistingUserByEmail($emailItems);

		if(
			empty($resPhone["PHONE_TO_REGISTER"])
			&& empty($resPhone["PHONE_TO_REINVITE"])
			&& empty($resEmail["EMAIL_TO_REGISTER"])
			&& empty($resEmail["EMAIL_TO_REINVITE"])
			&& empty($resEmail["TRANSFER_USER"])
		)
		{
			if (!empty($resEmail["EMAIL_EXIST"]))
			{
				$errors[] = Loc::getMessage("INTRANET_INVITATION_USER_EXIST_ERROR",
											["#EMAIL_LIST#" => implode(', ', $resEmail["EMAIL_EXIST"])]);

			}
			if (!empty($resPhone["PHONE_EXIST"]))
			{
				$errors[] = Loc::getMessage("INTRANET_INVITATION_USER_PHONE_EXIST_ERROR",
											["#PHONE_LIST#" => implode(', ', $resPhone["PHONE_EXIST"])]);

			}

			return false;
		}

		$messageText = Loc::getMessage("INTRANET_INVITATION_INVITE_MESSAGE_TEXT");

		//reinvite users by email
		foreach($resEmail["EMAIL_TO_REINVITE"] as $userData)
		{
			\CIntranetInviteDialog::InviteUser($userData, $messageText, array('checkB24' => false));
		}
		// TODO: reinvite: self::InviteUserByPhone($userData)

		$transferedUserIds = [];
		if (!empty($resEmail["TRANSFER_USER"]))
		{
			$transferedUserIds = self::transferUser($resEmail["TRANSFER_USER"], $errors);
		}

		$phoneUserIds = self::registerUsersByPhone($resPhone["PHONE_TO_REGISTER"], $errors);
		$emailUserIds = self::registerUsersByEmail($resEmail["EMAIL_TO_REGISTER"], $errors);

		if (!empty($errors))
		{
			return false;
		}

		return array_merge($phoneUserIds, $emailUserIds, $transferedUserIds);
	}
}