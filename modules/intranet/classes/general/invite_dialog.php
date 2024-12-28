<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Security\Random;
use Bitrix\Socialnetwork;
use Bitrix\Main\UserTable;
use Bitrix\Intranet\Invitation;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Router;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Config\Option;
use Bitrix\Bitrix24\Integration;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

class CIntranetInviteDialog
{
	public static $bSendPassword = false;
	public const PHONE_LIMIT_ERROR = 'INTRANET_INVITE_DIALOG_PHONE_LIMIT_ERROR';
	public const EMAIL_LIMIT_ERROR = 'INTRANET_INVITE_DIALOG_EMAIL_LIMIT_ERROR';
	public const PHONE_INCORRECT_ERROR = 'INTRANET_INVITE_DIALOG_PHONE_INCORRECT_ERROR';
	public const EMAIL_INCORRECT_ERROR = 'INTRANET_INVITE_DIALOG_EMAIL_INCORRECT_ERROR';
	public const EMAIL_EMPTY_ERROR = 'INTRANET_INVITE_DIALOG_EMAIL_EMPTY_ERROR';
	public const PHONE_EMPTY_ERROR = 'INTRANET_INVITE_DIALOG_PHONE_EMPTY_ERROR';
	public const EMAIL_EXIST_ERROR = 'INTRANET_INVITE_DIALOG_EMAIL_EXIST_ERROR';
	public const PHONE_EXIST_ERROR = 'INTRANET_INVITE_DIALOG_PHONE_EXIST_ERROR';

	private static array $userGroupsCache = [];

	public static function ShowInviteDialogLink($params = array())
	{
		$data = [
			'c' => 'bitrix:intranet.invitation',
			'mode' => Router::COMPONENT_MODE_AJAX,
		];

		$subSection = null;
		if (isset($params['analyticsLabel']))
		{
			$subSection = $params['analyticsLabel']['analyticsLabel[source]'];
		}

		if (!is_null($subSection))
		{
			$analyticsLabels = [
				'analyticsLabel[tool]' => 'Invitation',
				'analyticsLabel[category]' => 'invitation',
				'analyticsLabel[event]' => 'drawer_open',
				'analyticsLabel[c_section]' => $subSection
			];
			$params['analyticsLabel'] = array_merge($params['analyticsLabel'], $analyticsLabels);
		}

		if (isset($params['analyticsLabel']))
		{
			$data = array_merge($data, $params['analyticsLabel']);
		}

		$invitationLink = UrlManager::getInstance()->create('getSliderContent', $data);

		return "BX.SidePanel.Instance.open('".$invitationLink."', {cacheable: false, allowChangeHistory: false, width: 1100})";
	}

	public static function setSendPassword($value)
	{
		self::$bSendPassword = $value;
	}

	public static function getSendPassword()
	{
		return self::$bSendPassword;
	}

	public static function AddNewUser($SITE_ID, $arFields, &$strError, $type = null)
	{
		global $APPLICATION, $USER;

		$ID_ADDED = 0;

		$bitrix24Installed = ModuleManager::isModuleInstalled('bitrix24');

		if (
			isset($arFields["DEPARTMENT_ID"])
			&& !is_array($arFields["DEPARTMENT_ID"])
			&& (int)$arFields["DEPARTMENT_ID"] > 0
		)
		{
			$arFields["DEPARTMENT_ID"] = [ $arFields["DEPARTMENT_ID"] ];
		}

		$siteIdByDepartmentId = self::getUserSiteId(array(
			"UF_DEPARTMENT" => isset($arFields["DEPARTMENT_ID"]) && is_array($arFields["DEPARTMENT_ID"])
				? $arFields["DEPARTMENT_ID"][0] : "",
			"SITE_ID" => $SITE_ID
		));

		$bExtranet = !isset($arFields["DEPARTMENT_ID"]);
		$arGroups = self::getUserGroups($siteIdByDepartmentId, $bExtranet);

		$strEmail = trim($arFields["ADD_EMAIL"]);
		$strName = trim($arFields["ADD_NAME"]);
		$strLastName = trim($arFields["ADD_LAST_NAME"]);
		$strPosition = trim($arFields["ADD_POSITION"] ?? '');

		if ($strEmail !== '')
		{
			$filter = array(
				"=EMAIL"=> $strEmail
			);

			if (Loader::includeModule('socialnetwork'))
			{
				$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array_diff(\Bitrix\Main\UserTable::getExternalUserTypes(), [ 'email', 'shop' ]));
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
				if ($arUser["EXTERNAL_AUTH_ID"] === 'email' || $arUser["EXTERNAL_AUTH_ID"] === 'shop')
				{
					if ($arUser["EXTERNAL_AUTH_ID"] === 'shop' && Loader::includeModule("crm"))
					{
						$arGroups[] = \Bitrix\Crm\Order\BuyerGroup::getSystemGroupId();
					}

					$ID_TRANSFERRED = self::TransferEmailUser($arUser["ID"], array(
						"GROUP_ID" => $arGroups,
						"UF_DEPARTMENT" => $arFields["DEPARTMENT_ID"],
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

					return $ID_TRANSFERRED;
				}

				if (
					$bitrix24Installed
					&& $arUser["EXTERNAL_AUTH_ID"] === 'socservices'
					&& !empty($arFields["DEPARTMENT_ID"])
					&& (
						!isset($arUser["UF_DEPARTMENT"])
						|| (
							is_array($arUser["UF_DEPARTMENT"])
							&& (int)$arUser["UF_DEPARTMENT"][0] <= 0
						)
						|| (
							!is_array($arUser["UF_DEPARTMENT"])
							&& (int)$arUser["UF_DEPARTMENT"] <= 0
						)
					)
					&& !ModuleManager::isModuleInstalled('extranet')
				)
				{
					$ID_TRANSFERRED = self::TransferExtranetUser($arUser["ID"], array(
						"GROUP_ID" => $arGroups,
						"UF_DEPARTMENT" => $arFields["DEPARTMENT_ID"],
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

					return $ID_TRANSFERRED;
				}

				$strError = Loc::getMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", [
					'#EMAIL#' => $strEmail,
				]);
			}
		}

		if ($strEmail === '')
		{
			if (
				!isset($arFields["ADD_MAILBOX_ACTION"])
				|| (string)$arFields['ADD_MAILBOX_USER'] === ''
				|| (string)$arFields['ADD_MAILBOX_DOMAIN'] === ''
				|| !in_array($arFields["ADD_MAILBOX_ACTION"], array("create", "connect"))
			)
			{
				$strError = Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL");
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
			self::setSendPassword($arFields["ADD_SEND_PASSWORD"] === "Y");
			$site = \CSite::GetByID($siteIdByDepartmentId)->Fetch();

			$arUser = [
				'LOGIN' => $strEmail,
				'NAME' => $strName,
				'LAST_NAME' => $strLastName,
				'EMAIL' => $strEmail,
				'PASSWORD' => $strPassword,
				'GROUP_ID' => $arGroups,
				'WORK_POSITION' => $strPosition,
				'LID' => $siteIdByDepartmentId,
				'LANGUAGE_ID' => $site['LANGUAGE_ID'],
				'UF_DEPARTMENT' => $arFields['DEPARTMENT_ID'],
			];

			if (!self::getSendPassword())
			{
				$arUser["CONFIRM_CODE"] = Random::getString(8, true);
			}
			else
			{
				$arUser["B24NETWORK_CHECKWORD"] = Random::getString(16, true);
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
				$userFields = $arUser;
				$userFields['ID'] = $ID_ADDED;
				foreach(GetModuleEvents("intranet", "OnRegisterUser", true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, [ $userFields ]);
				}

				if (self::getSendPassword())
				{
					$db_events = GetModuleEvents("main", "OnUserInitialize", true);
					foreach($db_events as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array($ID_ADDED, $arUser));
					}

					$event = new Event(
						'intranet',
						'onUserAdded',
						[
							'originatorId' => $USER->getId(),
							'userId' => [ $ID_ADDED ],
							'type' => Invitation::TYPE_EMAIL
						]
					);
					$event->send();
				}
				else
				{
					Invitation::add([
						'USER_ID' => $ID_ADDED,
						'TYPE' => Invitation::TYPE_EMAIL,
						'IS_REGISTER' => $type === 'register' ? 'Y' : 'N'
					]);
				}

				if (
					$bExtranet
					&& !$bitrix24Installed
					&& !ModuleManager::isModuleInstalled('extranet')
				)
				{
					$bExtranet = false;
				}

				$messageText = self::getInviteMessageText();

				if (self::getSendPassword())
				{
					$serverName = (
						(string)$site["SERVER_NAME"] !== ''
							? $site["SERVER_NAME"]
							: (
								defined("SITE_SERVER_NAME") && SITE_SERVER_NAME !== ''
									? SITE_SERVER_NAME
									: Option::get('main', 'server_name')
							)
					);

					if ($bitrix24Installed && Loader::includeModule('socialservices'))
					{
						$uri = new Uri(
							(new CBitrix24NetOAuthInterface)->getInviteUrl(
								$ID_ADDED,
								$arUser["B24NETWORK_CHECKWORD"],
							)
						);
						$uri->addParams([
							'accepted' => 'yes'
						]);
						$url = $uri->getLocator();
					}
					else
					{
						$url = (CMain::IsHTTPS() ? "https" : "http") . "://" . $serverName . $site["DIR"];
					}
					$messageId = self::getMessageId($bitrix24Installed ? "BITRIX24_USER_ADD" : "INTRANET_USER_ADD", $siteIdByDepartmentId, LANGUAGE_ID);
					CEvent::SendImmediate(
						$bitrix24Installed ? "BITRIX24_USER_ADD" : "INTRANET_USER_ADD",
						$siteIdByDepartmentId,
						array(
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => $url,
							"USER_ID_FROM" => $USER->GetID(),
							"PASSWORD" => $strPassword,
							"USER_TEXT" => $messageText
						),
						null,
						$messageId
					);
				}
				else
				{
					$dbUser = CUser::GetByID($ID_ADDED);
					$arUser = $dbUser->Fetch();

					if ($bExtranet)
					{
						CEvent::SendImmediate("EXTRANET_INVITATION", $siteIdByDepartmentId, array(
							"USER_ID" => $arUser["ID"],
							"CHECKWORD" => $arUser["CONFIRM_CODE"],
							"EMAIL" => $arUser["EMAIL"],
							"USER_TEXT" => ''
						));
					}
					elseif ($bitrix24Installed)
					{
						$messageId = self::getMessageId("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
						CEvent::SendImmediate("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, array(
							"EMAIL_FROM" => $USER->GetEmail(),
							"USER_ID_FROM" => $USER->GetID(),
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
							"USER_TEXT" => $messageText
						), null, $messageId);
					}
					else
					{
						CEvent::SendImmediate("INTRANET_USER_INVITATION", $siteIdByDepartmentId, array(
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

	/**
	 * @throws Exception
	 */
	private static function validateEmailExist(array $arEmailExist = []): void
	{
		if (count($arEmailExist) === 1)
		{
			$errorMessage = Loc::getMessage('BX24_INVITE_DIALOG_USER_EXIST_ERROR1', [
				'#EMAIL#' => $arEmailExist[0]
			]);
			throw new Exception($errorMessage);
		}
		else if (count($arEmailExist) > 1)
		{
			$errorMessage = Loc::getMessage('BX24_INVITE_DIALOG_USER_EXIST_ERROR2', [
				'#EMAIL_LIST#' => implode(', ', $arEmailExist)
			]);
			throw new Exception($errorMessage);
		}
	}

	/**
	 * @throws Exception
	 */
	private static function validatePhoneExist(array $arPhoneExist = []): void
	{
		if (count($arPhoneExist) === 1)
		{
			$errorMessage = Loc::getMessage('BX24_INVITE_DIALOG_USER_PHONE_EXIST_ERROR1', [
				'#PHONE#' => $arPhoneExist[0],
			]);
			throw new Exception($errorMessage);
		}
		else if (count($arPhoneExist) > 1)
		{
			$errorMessage = Loc::getMessage('BX24_INVITE_DIALOG_USER_PHONE_EXIST_ERROR2', [
				'#PHONE_LIST#' => implode(', ', $arPhoneExist),
			]);
			throw new Exception($errorMessage);
		}
	}

	public static function RegisterNewUser($SITE_ID, $arFields, &$arError)
	{
		global $APPLICATION;

		$arCreatedUserId = [];
		$arReinvitedUserId = [];

		$arEmailToRegister = array();
		$arEmailToReinvite = array();
		$arUserForTransferId = $arExtranetUserId = $arShopUserId = array();

		$arEmailExist = array();
		$bExtranetUser = false;
		$bExtranetInstalled = (
			ModuleManager::isModuleInstalled('extranet')
			&& Option::get('extranet', 'extranet_site') !== ''
		);

		if (!empty($arFields['EMAIL']) || !empty($arFields['PHONE']))
		{
			$isPhone = !empty($arFields['PHONE']) && is_array($arFields['PHONE']);

			$phoneCountryList = [];
			$arEmailOriginal = [];

			if ($isPhone)
			{
				$arEmailOriginal = $arFields['PHONE'];
				$phoneCountryList = $arFields['PHONE_COUNTRY'];
			}
			else if (!empty($arFields["EMAIL"]))
			{
				$arEmailOriginal = is_array($arFields["EMAIL"])
					? $arFields["EMAIL"] : preg_split("/[\n\r\t\\,;\\ ]+/", trim($arFields["EMAIL"]));
			}

			$arEmail = $errorEmails = array();
			$emailCnt = 0;

			foreach ($arEmailOriginal as $index => $addr)
			{
				if ($emailCnt >= ($isPhone ? 5 : 100))
				{
					$errorCode = $isPhone
						? self::PHONE_LIMIT_ERROR
						: self::EMAIL_LIMIT_ERROR;
					$errorMessage = $isPhone
						? Loc::getMessage('BX24_INVITE_DIALOG_PHONE_LIMIT_EXCEEDED')
						: Loc::getMessage('BX24_INVITE_DIALOG_EMAIL_LIMIT_EXCEEDED');

					$arError[] = new Error($errorMessage, $errorCode);

					return false;
				}

				if($isPhone)
				{
					if (!is_array($addr))
					{
						$addr = ['PHONE' => $addr];
					}

					if (empty($addr['PHONE']))
					{
						continue;
					}

					if ($addr['PHONE_COUNTRY'])
					{
						$phoneCountry = $addr['PHONE_COUNTRY'];
					}
					else
					{
						$phoneCountry = $phoneCountryList[$index] ?? '';
					}

					$phoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($addr['PHONE'], $phoneCountry);

					if($phoneNumber->isValid())
					{
						$arEmail[$phoneNumber->format(\Bitrix\Main\PhoneNumber\Format::E164)] = $addr;
						$emailCnt++;
					}
					else
					{
						$errorEmails[] = $addr['PHONE'];
					}
				}
				else
				{
					if (!is_array($addr))
					{
						$addr = ['EMAIL' => $addr];
					}

					if (empty($addr['EMAIL']))
					{
						continue;
					}

					if ((string)$addr['EMAIL'] !== '' && check_email($addr['EMAIL']))
					{
						$arEmail[$addr['EMAIL']] = $addr;
						$emailCnt++;
					}
					else
					{
						$errorEmails[] = htmlspecialcharsbx($addr['EMAIL']);
					}
				}
			}

			if (count($arEmailOriginal) > count($arEmail) && !empty($errorEmails))
			{
				$errorCode = $isPhone
					? self::PHONE_INCORRECT_ERROR
					: self::EMAIL_INCORRECT_ERROR;
				$errorMessage = $isPhone
					? Loc::getMessage('BX24_INVITE_DIALOG_PHONE_ERROR')
					: Loc::getMessage('BX24_INVITE_DIALOG_EMAIL_ERROR');

				$arError[] = new Error($errorMessage . ' ' . implode(', ', $errorEmails), $errorCode);
			}

			$externalAuthIdList = [];
			if (Loader::includeModule('socialnetwork'))
			{
				$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(array_diff(\Bitrix\Main\UserTable::getExternalUserTypes(), [ 'email', 'shop' ]));
			}

			foreach($arEmail as $email => $userFields)
			{
				if ($isPhone)
				{
					$filter = [
						'=PHONE_NUMBER' => $email
					];

					if (!empty($externalAuthIdList))
					{
						$filter[] = [
							'LOGIC' => 'OR',
							'!@USER.EXTERNAL_AUTH_ID' => $externalAuthIdList,
							'USER.EXTERNAL_AUTH_ID' => false
						];
					}

					$rsUser = \Bitrix\Main\UserPhoneAuthTable::getList(array(
						'filter' => $filter,
						'select' => ['USER_ID', 'USER_CONFIRM_CODE' => 'USER.CONFIRM_CODE', 'USER_EXTERNAL_AUTH_ID' => 'USER.EXTERNAL_AUTH_ID', 'USER_UF_DEPARTMENT' => 'USER.UF_DEPARTMENT']
					));
				}
				else
				{
					$filter = [
						'=EMAIL' => $email
					];

					if (!empty($externalAuthIdList))
					{
						$filter[] = [
							'LOGIC' => 'OR',
							'!@EXTERNAL_AUTH_ID' => $externalAuthIdList,
							'EXTERNAL_AUTH_ID' => false
						];
					}

					$rsUser = UserTable::getList([
						'filter' => $filter,
						'select' => ['ID', 'CONFIRM_CODE', 'EXTERNAL_AUTH_ID', 'UF_DEPARTMENT']
					]);
				}

				$bFound = false;
				while ($arUser = $rsUser->Fetch())
				{
					if($isPhone)
					{
						$arUser = [
							'ID' => $arUser['USER_ID'],
							'CONFIRM_CODE' => $arUser['USER_CONFIRM_CODE'],
							'EXTERNAL_AUTH_ID' => $arUser['USER_ID'],
							'UF_DEPARTMENT' => $arUser['USER_UF_DEPARTMENT'],
						];
					}

					$bFound = true;

					if ($arUser["EXTERNAL_AUTH_ID"] === 'email' || $arUser["EXTERNAL_AUTH_ID"] === 'shop')
					{
						$arUserForTransferId[] = $arUser["ID"];
						if ($arUser["EXTERNAL_AUTH_ID"] === 'shop')
						{
							$arShopUserId[] = $arUser["ID"];
						}
					}
					elseif (
						(string)$arUser["CONFIRM_CODE"] !== ""
						&& (
							!$bExtranetInstalled
							|| ( // both intranet
								isset($arFields["DEPARTMENT_ID"], $arUser["UF_DEPARTMENT"])
								&& (int)$arFields["DEPARTMENT_ID"] > 0
								&& (
									(
										is_array($arUser["UF_DEPARTMENT"])
										&& (int)$arUser["UF_DEPARTMENT"][0] > 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& (int)$arUser["UF_DEPARTMENT"] > 0
									)
								)
							)
							||
							(	// both extranet
								(
									!isset($arFields["DEPARTMENT_ID"])
									|| (int)$arFields["DEPARTMENT_ID"] <= 0
								)
								&& (
									!isset($arUser["UF_DEPARTMENT"])
									|| (
										is_array($arUser["UF_DEPARTMENT"])
										&& (int)$arUser["UF_DEPARTMENT"][0] <= 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& (int)$arUser["UF_DEPARTMENT"] <= 0
									)
								)
							)
						)
					)
					{
						$arEmailToReinvite[] = array(
							'EMAIL' => $email,
							'REINVITE' => true,
							'ID' => $arUser['ID'],
							'CONFIRM_CODE' => $arUser['CONFIRM_CODE'],
							'UF_DEPARTMENT' => $arUser['UF_DEPARTMENT']
						);

						$arReinvitedUserId[] = $arUser['ID'];
					}
					elseif (
						$arUser["EXTERNAL_AUTH_ID"] === 'socservices'
						&& (
							isset($arFields["DEPARTMENT_ID"])
							&& (int)$arFields["DEPARTMENT_ID"] > 0
						)
						&& (
							!isset($arUser["UF_DEPARTMENT"])
							|| (
								is_array($arUser["UF_DEPARTMENT"])
								&& (int)$arUser["UF_DEPARTMENT"][0] <= 0
							)
							|| (
								!is_array($arUser["UF_DEPARTMENT"])
								&& (int)$arUser["UF_DEPARTMENT"] <= 0
							)
						) // past-extranet to intranet
						&& ModuleManager::isModuleInstalled('bitrix24')
						&& !ModuleManager::isModuleInstalled('extranet')
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
					if (is_string($userFields))
					{
						$email = $userFields;
						$userFields = [];
					}

					$arEmailToRegister[] = [
						'EMAIL' => $email,
						'REINVITE' => false,
						...$userFields,
					];
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
				$messageText = Loc::getMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");
			}

			if(
				empty($arEmailToRegister)
				&& empty($arEmailToReinvite)
				&& empty($arUserForTransferId)
				&& empty($arExtranetUserId)
				&& empty($errorEmails)
			)
			{
				if (empty($arEmailExist))
				{
					$errorMessage = Loc::getMessage('BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL_LIST');
					$errorCode = self::EMAIL_EMPTY_ERROR;

					$arError[] = new Error($errorMessage, $errorCode);

					return false;
				}
			}

			//reinvite users
			foreach($arEmailToReinvite as $userData)
			{
				self::InviteUser($userData, $messageText, array('checkB24' => false));
			}
		}
		else
		{
			if(
				empty($arEmailToRegister)
				&& empty($arEmailToReinvite)
				&& empty($errorEmails)
			)
			{
				if (empty($arEmailExist))
				{
					$errorMessage = Loc::getMessage('BX24_INVITE_DIALOG_ERROR_EMPTY_PHONE_LIST');
					$errorCode = self::PHONE_EMPTY_ERROR;

					$arError[] = new Error($errorMessage, $errorCode);

					return false;
				}
			}

			foreach($arEmailToReinvite as $userData)
			{
				self::reinviteUserByPhone((int)$userData['ID']);
			}
		}

		$siteIdByDepartmentId = $arGroups = false;

		if (
			!empty($arEmailToRegister)
			|| !empty($arUserForTransferId)
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
					//&& intval($arFields["UF_DEPARTMENT"]) > 0
				)
			)
			{
				if (!$bExtranetInstalled)
				{
					$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
						->departmentRepository();
					$rootDepartment = $departmentRepository->getRootDepartment();
					$arFields['UF_DEPARTMENT'] = [$rootDepartment->getId()];
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

		// transfer email or shop users to employees or extranet
		if (!empty($arUserForTransferId))
		{
			$arShopGroups = $arGroups;
			if (Loader::includeModule("crm"))
			{
				$arShopGroups[] = \Bitrix\Crm\Order\BuyerGroup::getSystemGroupId();
			}

			foreach ($arUserForTransferId as $transferUserId)
			{
				$ID_TRANSFERRED = self::TransferEmailUser(
					$transferUserId,
					[
						'GROUP_ID' => (in_array($transferUserId, $arShopUserId)) ? $arShopGroups : $arGroups,
						'UF_DEPARTMENT' => $arFields['UF_DEPARTMENT'],
						'SITE_ID' => $siteIdByDepartmentId
					]
				);

				if (!$ID_TRANSFERRED)
				{
					if ($e = $APPLICATION->GetException())
					{
						$arError[] = new Error($e->GetString(), $e->GetID() ?? 0);//
					}
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
					if ($e = $APPLICATION->GetException())
					{
						$arError[] = new Error($e->GetString(), $e->GetID() ?? 0);
					}
				}
				else
				{
					$arCreatedUserId[] = $ID_TRANSFERRED;
				}
			}
		}

		if ($isPhone)
		{
			try
			{
				self::validatePhoneExist($arEmailExist);
			}
			catch (Exception $exception)
			{
				$arError[] = new Error($exception->getMessage(), self::PHONE_EXIST_ERROR);
			}
		}
		else
		{
			try
			{
				self::validateEmailExist($arEmailExist);
			}
			catch (Exception $exception)
			{
				$arError[] = new Error($exception->getMessage(), self::EMAIL_EXIST_ERROR);
			}
		}

		//register users
		if (!empty($arEmailToRegister))
		{
			$invitedUserIdList = [];
			foreach ($arEmailToRegister as $userData)
			{
				if($isPhone)
				{
					$userData['LOGIN'] = $userData['EMAIL'];
					$userData['PHONE_NUMBER'] = $userData['EMAIL'];

					unset($userData['EMAIL']);
				}

				$userData['CONFIRM_CODE'] = Random::getString(8, true);
				$userData['GROUP_ID'] = $arGroups;
				$userData['UF_DEPARTMENT'] = $arFields['UF_DEPARTMENT'];
				$ID = self::RegisterUser($userData, $siteIdByDepartmentId);

				if(is_array($ID))
				{
					$arError = $ID;
					return false;
				}

				$invitedUserIdList[] = $ID;
				$userData['ID'] = $ID;
				$arCreatedUserId[] = $ID;

				if(!$isPhone)
				{
					self::InviteUser($userData, $messageText, array('checkB24' => false));
				}
				else
				{
					//TODO: invite user self::InviteUserByPhone($userData);
				}
			}

			if (!empty($invitedUserIdList))
			{
				Invitation::add([
					'USER_ID' => $invitedUserIdList,
					'TYPE' => ($isPhone ? Invitation::TYPE_PHONE : Invitation::TYPE_EMAIL)
				]);
			}
		}

		return $arCreatedUserId + $arReinvitedUserId;
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
				$strError = Loc::getMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR1", array("#EMAIL#" => $email));
				return false;
			}

			$userData = array(
				"EMAIL" => $email,
				"REINVITE" => true,
				"ID" => $arUser["ID"],
				"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
				"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
			);

			self::InviteUser($userData, $messageText, array('checkB24' => false));

			return $arUser["ID"];
		}
		else
		{
			$userData = array(
				"EMAIL" => $email,
				"REINVITE" => false
			);

			$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
				->departmentRepository();
			$rootDepartment = $departmentRepository->getRootDepartment();
			$arFields['UF_DEPARTMENT'] = [$rootDepartment->getId()];

			$arGroups = self::getAdminGroups($SITE_ID);
			if (Loader::includeModule('bitrix24'))
			{
				$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
				$arGroups[] = $integratorGroupId;
			}
			//register users
			$userData["CONFIRM_CODE"] = Random::getString(8, true);
			$userData["GROUP_ID"] = $arGroups;
			$userData["UF_DEPARTMENT"] = $arFields["UF_DEPARTMENT"];

			$ID = self::RegisterUser($userData, $SITE_ID);
			if(is_array($ID))
			{
				$strError = $ID[0];
				return false;
			}

			$userData['ID'] = $ID;

			self::InviteUser($userData, $messageText, array('checkB24' => false));

			Invitation::add([
				'USER_ID' => [ $ID ],
				'TYPE' => Invitation::TYPE_EMAIL,
				'IS_INTEGRATOR' => 'Y'
			]);

			return $ID;
		}
	}

	public static function getUserGroups($SITE_ID, $bExtranetUser = false)
	{
		if (
			$bExtranetUser
			&& Loader::includeModule("extranet")
		)
		{
			$extranetGroupID = CExtranet::GetExtranetUserGroupID();
			if ((int)$extranetGroupID > 0)
			{
				return [$extranetGroupID];
			}
		}
		else
		{
			if (isset(static::$userGroupsCache["EMPLOYEES_".$SITE_ID]))
			{
				return static::$userGroupsCache["EMPLOYEES_".$SITE_ID];
			}
			static::$userGroupsCache["EMPLOYEES_".$SITE_ID] = \Bitrix\Main\GroupTable::query()
				->where('STRING_ID', "EMPLOYEES_".$SITE_ID)
				->setSelect(['ID'])
				->fetchCollection()
				->getIdList()
				;

			return static::$userGroupsCache["EMPLOYEES_".$SITE_ID];
		}

		return [];
	}

	public static function getAdminGroups($SITE_ID)
	{
		$arGroups = array(1);
		$rsGroups = CGroup::GetList(
			'',
			'',
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
		if (Loader::includeModule("bitrix24"))
		{
			$UserMaxCount = Application::getInstance()->getLicense()->getMaxUsers();
			$currentUserCount = CBitrix24::getActiveUserCount();
			return $UserMaxCount <= 0 || $cnt <= $UserMaxCount - $currentUserCount;
		}
		return true;
	}

	public static function RegisterUser($userData, $SITE_ID = SITE_ID)
	{
		$bExtranetUser = (!isset($userData['UF_DEPARTMENT']) || empty($userData['UF_DEPARTMENT']));
		$strPassword = self::GeneratePassword($SITE_ID, $bExtranetUser);

		$arUser = [
			'LOGIN' => $userData['LOGIN'] ?? $userData['EMAIL'],
			'EMAIL' => $userData['EMAIL'] ?? null,
			'PASSWORD' => $strPassword,
			'CONFIRM_CODE' => $userData['CONFIRM_CODE'],
			'NAME' => $userData['NAME'] ?? null,
			'LAST_NAME' => $userData['LAST_NAME'] ?? null,
			'GROUP_ID' => $userData['GROUP_ID'],
			'LID' => $SITE_ID,
			'UF_DEPARTMENT' => empty($userData['UF_DEPARTMENT']) ? [] :
				(is_array($userData['UF_DEPARTMENT']) ? $userData['UF_DEPARTMENT'] : [$userData['UF_DEPARTMENT']]),
			'LANGUAGE_ID' => ($site = \CSite::GetArrayByID($SITE_ID)) ? $site['LANGUAGE_ID'] : LANGUAGE_ID,
		];

		if (isset($userData['PHONE_NUMBER']))
		{
			$arUser['PHONE_NUMBER'] = $userData['PHONE_NUMBER'];
			$arUser['PERSONAL_MOBILE'] = $userData['PHONE_NUMBER'];
		}

		if (isset($userData['ACTIVE']))
		{
			$arUser['ACTIVE'] = $userData['ACTIVE'];
		}

		if(isset($userData['XML_ID']))
		{
			$arUser['XML_ID'] = $userData['XML_ID'];
		}

		$obUser = new CUser;
		$res = $obUser->Add($arUser);

		if ($res)
		{
			$userFields = $arUser;
			$userFields['ID'] = $res;
			foreach (GetModuleEvents('intranet', 'OnRegisterUser', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [ $userFields ]);
			}
		}

		return ($res ?: explode('<br>', $obUser->LAST_ERROR));
	}

	private static function cannotSendInvite(): bool
	{
		return
			Loader::includeModule('bitrix24')
			&& !CBitrix24::IsNfrLicense()
			&& (
				!CBitrix24::IsLicensePaid()
				|| CBitrix24::IsDemoLicense()
			)
		;
	}

	public static function reinviteUserByPhone(int $userId, array $params = []): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			if (
				isset($params['checkB24'])
				&& $params['checkB24'] === true
				&& self::cannotSendInvite()
			)
			{
				return false;
			}

			return \Bitrix\Bitrix24\Integration\Network\ProfileService::getInstance()->reInviteUserByPhone($userId)->isSuccess();
		}
		else
		{
			// TODO: from portal sms provider
		}

		return false;
	}

	public static function InviteUserByPhone($arUser, $params = array())
	{
		// TODO: from portal sms provider
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
			if (self::cannotSendInvite())
			{
				$messageText = Loc::getMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");
			}
		}

		$bExtranet = (
			ModuleManager::isModuleInstalled('extranet')
			&& (
				empty($arUser["UF_DEPARTMENT"])
				|| (
					is_array($arUser["UF_DEPARTMENT"])
					&& (int)$arUser["UF_DEPARTMENT"][0] <= 0
				)
				|| (
					!is_array($arUser["UF_DEPARTMENT"])
					&& (int)$arUser["UF_DEPARTMENT"] <= 0
				)
			)
		);
		$isCloud = Loader::includeModule('bitrix24');

		if ($isCloud && isset($arUser['ID']))
		{
			$networkEmail = (new Integration\Network\Invitation())->getEmailByUserId((int)$arUser['ID']);
			$emailTo = $networkEmail ?? $arUser['EMAIL'];
		}
		else
		{
			$emailTo = $arUser['EMAIL'];
		}

		$siteIdByDepartmentId = self::getUserSiteId(array(
			"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"],
			"SITE_ID" => SITE_ID
		));

		if ($bExtranet)
		{
			$messageId = self::getMessageId("EXTRANET_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			CEvent::SendImmediate("EXTRANET_INVITATION", $siteIdByDepartmentId, array(
				"USER_ID" => $arUser["ID"],
				"USER_ID_FROM" => $USER->GetID(),
				"CHECKWORD" => $arUser["CONFIRM_CODE"],
				"EMAIL" => $emailTo,
				"USER_TEXT" => $messageText
			), null, $messageId);
		}
		elseif ($isCloud)
		{
			$messageId = self::getMessageId("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			CEvent::SendImmediate("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, array(
				"EMAIL_FROM" => $USER->GetEmail(),
				"USER_ID_FROM" => $USER->GetID(),
				"EMAIL_TO" => $emailTo,
				"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
				"USER_TEXT" => $messageText,
			), null, $messageId);
		}
		else
		{
			$messageId = self::getMessageId("INTRANET_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			CEvent::SendImmediate("INTRANET_USER_INVITATION", $siteIdByDepartmentId, array(
				"EMAIL_TO" => $emailTo,
				"USER_ID_FROM" => $USER->GetID(),
				"LINK" => self::getInviteLink($arUser, $siteIdByDepartmentId),
				"USER_TEXT" => $messageText,
			), null, $messageId);
		}
	}

	public static function getMessageId($eventName, $siteId, $languageId)
	{
		$arEventMessageFilter = [
			'=ACTIVE' => 'Y',
			'=EVENT_NAME' => $eventName,
			'=EVENT_MESSAGE_SITE.SITE_ID' => $siteId,
		];

		if(LANGUAGE_ID <> '')
		{
			$arEventMessageFilter[] = [
				"LOGIC" => "OR",
				["=LANGUAGE_ID" => $languageId],
				["=LANGUAGE_ID" => null],
			];
		}

		$messageDb = Bitrix\Main\Mail\Internal\EventMessageTable::getList([
			'select' => ['ID'],
			'filter' => $arEventMessageFilter,
			'group' => ['ID'],
			'order' => ['LANGUAGE_ID' => 'desc'],
			'limit' => 1
		]);

		$arMessage = $messageDb->fetch();
		if (is_null($arMessage))
		{
			return null;
		}

		return $arMessage['ID'];

	}

	public static function ReinviteUser($SITE_ID, $USER_ID)
	{
		$USER_ID = (int)$USER_ID;

		$rsUser = CUser::GetList(
			"ID",
			"DESC",
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

		$USER_ID = (int)$USER_ID;

		$rsUser = CUser::GetList(
			"ID",
			"DESC",
			array("ID_EQUAL_EXACT" => $USER_ID)
		);

		if($arUser = $rsUser->Fetch())
		{
			$arFields = Array(
				"USER_ID" => $USER_ID,
				"USER_ID_FROM" => $USER->GetID(),
				"CHECKWORD" => $arUser["CONFIRM_CODE"],
				"EMAIL" => $arUser["EMAIL"],
				"USER_TEXT" => self::getInviteMessageText()
			);
			CEvent::SendImmediate("EXTRANET_INVITATION", $SITE_ID, $arFields);
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
			&& Loader::includeModule("socialnetwork")
		)
		{
			foreach ($arGroupCode as $group_code)
			{
				if(
					$bExtranetUser
					&& is_array($arGroupName)
					&& preg_match('/^(SGN\d+)$/', $group_code, $match)
					&& isset($arGroupName[$match[1]])
					&& (string)$arGroupName[$match[1]] !== ''
					&& Loader::includeModule('extranet')
					&& Loader::includeModule('socialnetwork')
					&& \Bitrix\Socialnetwork\Helper\Workgroup::canCreate([
						'siteId' => CExtranet::GetExtranetSiteID(),
						'checkAdminSession' => false,
					])
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
						&& $arGroup["CLOSED"] !== "Y"
					)
					{
						$arGroupToAdd[] = $group_id;
					}
				}
			}

			if (!$strError)
			{
				foreach ($arGroupToAdd as $group_id)
				{
					foreach ($arUserId as $user_id)
					{
						if (
							!CSocNetUserToGroup::SendRequestToJoinGroup($USER->GetID(), $user_id, $group_id, "", false)
							&& $e = $APPLICATION->GetException()
						)
						{
							$strError .= $e->GetString();
						}
					}
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
			&& trim($arParams['user_fields']['LAST_LOGIN']) === '' // do not check CONFIRM_CODE, please
			&& Loader::includeModule("socialnetwork")
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

					if (Loader::includeModule("im"))
					{
						CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".$arParams['user_fields']['ID']."|". (int)$arRelation["ID"]);
					}
				}
			}
		}
	}

	private static function GeneratePassword($SITE_ID, $bExtranetUser)
	{
		$arGroupID = self::getUserGroups($SITE_ID, $bExtranetUser);

		return \CUser::GeneratePasswordByPolicy($arGroupID);
	}

	public static function TransferEmailUser($userId, $arParams = array(), bool $sendNotification = true)
	{
		global $APPLICATION;

		$userId = (int)$userId;

		if (!($arUser = self::checkUserId($userId)))
		{
			$APPLICATION->ThrowException(Loc::getMessage('BX24_INVITE_DIALOG_USER_ID_NO_EXIST_ERROR'));

			return false;
		}

		$dbUser = CUser::GetList(
			"ID",
			"ASC",
			array(
				"=EMAIL" => $arUser["EMAIL"],
				"EXTERNAL_AUTH_ID" => "",
			),
			array("FIELDS" => array("ID"))
		);
		if ($dbUser->Fetch())
		{
			$APPLICATION->ThrowException(
				Loc::getMessage('BX24_INVITE_DIALOG_USER_EXIST_ERROR1', [
					'#EMAIL#' => $arUser['EMAIL'],
				]),
				self::EMAIL_EXIST_ERROR,
			);
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

		$arFields = array(
			"EXTERNAL_AUTH_ID" => '',
			"GROUP_ID" => $arParams['GROUP_ID'],
			"EMAIL" => $arUser["EMAIL"]
		);

		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			self::$bSendPassword = false;
		}
		else
		{
			self::$bSendPassword = true;
			$arFields['PASSWORD'] = self::GeneratePassword($arParams["SITE_ID"], $bExtranetUser);
		}

		if (isset($arParams["UF_DEPARTMENT"]))
		{
			$arFields["UF_DEPARTMENT"] = !is_array($arParams["UF_DEPARTMENT"]) ? array($arParams["UF_DEPARTMENT"]) : $arParams["UF_DEPARTMENT"];
		}

		if (
			isset($arParams["NAME"])
			&& (string)$arParams["NAME"] !== ''
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
			&& (string)$arParams["LAST_NAME"] !== ''
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
			&& (string)$arParams["POSITION"] !== ''
		)
		{
			$arFields["POSITION"] = $arParams["POSITION"];
		}

		if (
			isset($arParams["CONFIRM_CODE"])
			&& (string)$arParams["CONFIRM_CODE"] !== ''
		)
		{
			$arFields["CONFIRM_CODE"] = $arParams["CONFIRM_CODE"];
		}

		foreach(GetModuleEvents("intranet", "OnTransferEMailUser", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
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

			foreach(GetModuleEvents("intranet", "OnRegisterUser", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [ $arFields ]);
			}

			if ($sendNotification)
			{
				self::sentTransferNotification($arUser, $arFields, $arParams);
			}

			return $userId;
		}

		$APPLICATION->ThrowException(Loc::getMessage('BX24_INVITE_DIALOG_ERROR_USER_TRANSFER'));

		return false;
	}

	public static function TransferExtranetUser($userId, $arParams = array())
	{
		global $APPLICATION;

		$userId = (int)$userId;

		if (!($arUser = self::checkUserId($userId)))
		{
			$APPLICATION->ThrowException(Loc::getMessage('BX24_INVITE_DIALOG_USER_ID_NO_EXIST_ERROR'));

			return false;
		}

		$dbUser = CUser::GetList(
			"ID",
			"ASC",
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
					&& (int)$arUserCheck["UF_DEPARTMENT"][0] > 0
				)
				|| (
					!is_array($arUserCheck["UF_DEPARTMENT"])
					&& (int)$arUserCheck["UF_DEPARTMENT"] > 0
				)
			)
		)
		{
			$APPLICATION->ThrowException(
				Loc::getMessage('BX24_INVITE_DIALOG_USER_EXIST_ERROR1', [
					'#EMAIL#' => $arUser['EMAIL'],
				]),
				self::EMAIL_EXIST_ERROR,
			);

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
			"PASSWORD" => Loc::getMessage('BX24_INVITE_DIALOG_PASSWORD_SAME'),
			"NAME" => $arUser["NAME"],
			"LAST_NAME" => $arUser["LAST_NAME"],
			"EMAIL" => $arUser["EMAIL"],
			"UF_DEPARTMENT" => !is_array($arParams["UF_DEPARTMENT"]) ? array($arParams["UF_DEPARTMENT"]) : $arParams["UF_DEPARTMENT"],
			"ADMIN_NOTES" => str_replace("~deactivated~", "", $arUser["ADMIN_NOTES"]),
			"ACTIVE" => "Y"
		);

		if (
			isset($arParams["POSITION"])
			&& (string)$arParams["POSITION"] !== ''
		)
		{
			$arFields["POSITION"] = $arParams["POSITION"];
		}

		foreach(GetModuleEvents("intranet", "OnTransferExtranetUser", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
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
			foreach (GetModuleEvents("intranet", "OnAfterTransferExtranetUser", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arUser));
			}
			foreach (GetModuleEvents("intranet", "OnRegisterUser", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [ $arFields ]);
			}

			self::sentTransferNotification($arUser, $arFields, $arParams);

			return $userId;
		}

		$APPLICATION->ThrowException(Loc::getMessage('BX24_INVITE_DIALOG_ERROR_EXTRANET_USER_TRANSFER'));

		return false;
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

		if(self::$bSendPassword)
		{
			$rsSites = CSite::GetByID($siteIdToSend);
			$arSite = $rsSites->Fetch();
			$serverName = (
				(string)$arSite["SERVER_NAME"] !== ''
					? $arSite["SERVER_NAME"]
					: (
						defined("SITE_SERVER_NAME") && SITE_SERVER_NAME !== ''
							? SITE_SERVER_NAME
							: Option::get('main', 'server_name')
					)
			);

			CEvent::SendImmediate("INTRANET_USER_ADD", $arParams["SITE_ID"], array(
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => (CMain::IsHTTPS() ? "https" : "http")."://".$serverName.$arSite["DIR"],
				"PASSWORD" => $arFields["PASSWORD"],
				"USER_TEXT" => $messageText
			));
		}
		else
		{
			if (ModuleManager::isModuleInstalled("bitrix24"))
			{
				$messageId = self::getMessageId("BITRIX24_USER_INVITATION", $arParams["SITE_ID"], LANGUAGE_ID);
				CEvent::SendImmediate("BITRIX24_USER_INVITATION", $arParams["SITE_ID"], array(
					"EMAIL_FROM" => $USER->GetEmail(),
					"USER_ID_FROM" => $USER->GetID(),
					"EMAIL_TO" => $arUser["EMAIL"],
					"LINK" => self::getInviteLink($arUser, $siteIdToSend),
					"USER_TEXT" => $messageText
				), null, $messageId);
			}
			else
			{
				CEvent::SendImmediate("INTRANET_USER_INVITATION", $arParams["SITE_ID"], array(
					"EMAIL_TO" => $arUser["EMAIL"],
					"USER_ID_FROM" => $USER->GetID(),
					"LINK" => self::getInviteLink($arUser, $siteIdToSend),
					"USER_TEXT" => $messageText
				));
			}
		}
	}

	public static function GetSiteByDepartmentId($arDepartmentId)
	{
		if (!is_array($arDepartmentId))
		{
			$arDepartmentId = array($arDepartmentId);
		}

		$dbSitesList = CSite::GetList("SORT", "asc", array("ACTIVE" => "Y")); // cache used
		while ($arSite = $dbSitesList->GetNext())
		{
			$siteRootDepartmentId = COption::GetOptionString("main", "wizard_departament", false, $arSite["LID"], true);
			if ($siteRootDepartmentId)
			{
				if (in_array($siteRootDepartmentId, $arDepartmentId))
				{
					return $arSite["LID"];
				}

				$arSubStructure = CIntranetUtils::getSubStructure($siteRootDepartmentId);
				$arSiteDepartmentId = array_keys($arSubStructure["DATA"]);

				foreach ($arDepartmentId as $userDepartmentId)
				{
					if (in_array($userDepartmentId, $arSiteDepartmentId))
					{
						return $arSite["LID"];
					}
				}
			}
		}

		return SITE_ID;
	}

	public static function getUserSiteId($arParams = array())
	{
		$bExtranet = (
			!isset($arParams["UF_DEPARTMENT"])
			|| (int)$arParams["UF_DEPARTMENT"] <= 0
		);

		if (
			$bExtranet
			&& Loader::includeModule("extranet")
		)
		{
			$siteId = CExtranet::GetExtranetSiteID();
		}
		elseif (ModuleManager::isModuleInstalled("bitrix24"))
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
			$siteId = self::GetSiteByDepartmentId($arParams["UF_DEPARTMENT"]);
		}

		return $siteId;
	}

	public static function getInviteLink($arUser, $siteId)
	{
		$rsSites = CSite::GetByID($siteId);
		$arSite = $rsSites->Fetch();
		$serverName = (
			(string)$arSite["SERVER_NAME"] !== ''
				? $arSite["SERVER_NAME"]
				: (
					defined("SITE_SERVER_NAME") && SITE_SERVER_NAME !== ''
						? SITE_SERVER_NAME
						: Option::get('main', 'server_name')
			)
		);

		return CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$arUser["ID"]."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName);
	}

	public static function getInviteMessageText()
	{
		return (
			($userMessageText = Option::get(ModuleManager::isModuleInstalled("bitrix24") ? "bitrix24" : "intranet", "invite_message_text"))
			&& (
				!Loader::includeModule('bitrix24')
				|| (
					CBitrix24::IsLicensePaid()
					&& !CBitrix24::IsDemoLicense()
				)
				|| CBitrix24::IsNfrLicense()
			)
				? $userMessageText
				: Loc::getMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1")
		);
	}

	public static function logAction($arUserId, $module, $action, $label, $context = 'web')
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
					AddEventToStatFile($module, $action, $label, $userId, $context);
				}
			}
		}
	}
}
