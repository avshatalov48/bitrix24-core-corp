<?php

namespace Bitrix\Intranet\Invitation;

use Bitrix\Bitrix24\Feature;
use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\InvitationCollection;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Intranet\Service\InviteMessageFactory;
use Bitrix\Intranet\Service\InviteService;
use Bitrix\Intranet\Service\RegistrationService;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\Entity\Invitation as InvitationEntity;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Intranet\User;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork;
use Bitrix\Intranet\Invitation;

class Register
{
	public static function checkPhone(&$item)
	{
		$phoneCountry = $item["PHONE_COUNTRY"] ?? "";
		$phoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($item["PHONE"], $phoneCountry);

		if ($phoneNumber->isValid())
		{
			$item["PHONE_NUMBER"] = $phoneNumber->format(\Bitrix\Main\PhoneNumber\Format::E164);
			return true;
		}

		return false;
	}

	public static function getMaxEmailCount()
	{
		if (Loader::includeModule('bitrix24'))
		{
			$licensePrefix = \CBitrix24::getLicensePrefix();
			$licenseType = \CBitrix24::getLicenseType();

			if (
				$licenseType === 'project'
				&& in_array($licensePrefix, ['cn', 'en', 'vn', 'jp'])
			)
			{
				return 10;
			}
		}

		return 100;
	}

	public static function checkItems($items): Result
	{
		$emailItems = [];
		$phoneItems = [];
		$errorEmailItems = [];
		$errorPhoneItems = [];
		$phoneCnt = 0;
		$emailCnt = 0;
		$dailyPhoneLimit = (
			Loader::includeModule('bitrix24')
			&& \CBitrix24::getLicenseType() === 'project'
				? 10
				: 0 // unlimited
		);

		$dailyEmailLimit = 0;

		if (
			Loader::includeModule('bitrix24')
			&& ((int)Feature::getVariable('intranet_emails_invitation_limit') > 0)
		)
		{
			$dailyEmailLimit = (int)Feature::getVariable('intranet_emails_invitation_limit');
		}

		$date = new DateTime();
		$date->add('-1 days');

		$pastPhoneInvitationNumber = 0;
		if ($dailyPhoneLimit > 0)
		{
			$pastPhoneInvitationNumber = InvitationTable::getList([
				'select' => [ 'ID' ],
				'filter' => [
					'=INVITATION_TYPE' => \Bitrix\Intranet\Invitation::TYPE_PHONE,
					'>DATE_CREATE' => $date,
				],
				'count_total' => true,
			])->getCount();
		}

		$pastEmailInvitationNumber = 0;
		if ($dailyEmailLimit > 0)
		{
			$pastEmailInvitationNumber = InvitationTable::getList([
				'select' => [ 'ID' ],
				'filter' => [
					'=INVITATION_TYPE' => \Bitrix\Intranet\Invitation::TYPE_EMAIL,
					'>DATE_CREATE' => $date,
				],
				'count_total' => true,
			])->getCount();
		}

		$dailyPhoneLimitExceeded = false;
		$dailyEmailLimitExceeded = false;
		foreach ($items as $item)
		{
			if (isset($item["PHONE"]))
			{
				$item["PHONE"] = trim($item["PHONE"]);
				if (self::checkPhone($item))
				{
					if (
						$dailyPhoneLimit <= 0
						|| ($pastPhoneInvitationNumber + $phoneCnt) < $dailyPhoneLimit
					)
					{
						$phoneItems[] = $item;
						$phoneCnt++;
					}
					else
					{
						$dailyPhoneLimitExceeded = true;
					}
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
					if (
						$dailyEmailLimit <= 0
						|| ($pastEmailInvitationNumber + $emailCnt) < $dailyEmailLimit
					)
					{
						$emailItems[] = $item;
						$emailCnt++;
					}
					else
					{
						$dailyEmailLimitExceeded = true;
					}
				}
				else
				{
					$errorEmailItems[] = $item["EMAIL"];
				}
			}
		}

		$result = new Result();
		if ($dailyPhoneLimitExceeded)
		{
			$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_DAILY_PHONE_LIMIT_EXCEEDED")));
		}

		if ($dailyEmailLimitExceeded)
		{
			$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_DAILY_EMAIL_LIMIT_EXCEEDED")));
		}

		if ($phoneCnt >= 5)
		{
			$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_PHONE_LIMIT_EXCEEDED")));
		}

		if ($emailCnt > self::getMaxEmailCount())
		{
			$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_EMAIL_LIMIT_EXCEEDED")));
		}

		if (!empty($errorEmailItems))
		{
			$result->addError(
				new Error(
					Loc::getMessage("INTRANET_INVITATION_EMAIL_ERROR")." ".implode(", ", $errorEmailItems)
				)
			);
		}

		if (!empty($errorPhoneItems))
		{
			$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_PHONE_ERROR")." ".implode(", ", $errorPhoneItems)));
		}

		return $result->setData([
			"PHONE_ITEMS" => $phoneItems,
			"EMAIL_ITEMS" => $emailItems
		]);
	}

	public static function checkExistingUserByPhone(array $phoneItems): array
	{
		$arPhoneToReinvite = [];
		$arPhoneExist = [];
		$arPhoneToRegister = [];

		$bExtranetInstalled = (
			ModuleManager::isModuleInstalled("extranet")
			&& Option::get("extranet", "extranet_site") !== ''
		);

		if (Loader::includeModule('socialnetwork'))
		{
			$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(
				array_diff(\Bitrix\Main\UserTable::getExternalUserTypes(), [ 'email', 'shop' ])
			);
		}

		$phoneList = array_column($phoneItems, 'PHONE_NUMBER');
		$filter = [];
		if (!empty($phoneList))
		{
			$filter["=USER.LOGIN"] = $phoneList;
		}
		if (!empty($externalAuthIdList))
		{
			$filter['!=USER.EXTERNAL_AUTH_ID'] = $externalAuthIdList;
		}

		$rsUser = \Bitrix\Main\UserPhoneAuthTable::getList([
			'filter' => $filter,
			'select' => [
				"USER_ID",
				"PHONE_NUMBER",
				"USER_CONFIRM_CODE" => "USER.CONFIRM_CODE",
				"USER_EXTERNAL_AUTH_ID" => "USER.EXTERNAL_AUTH_ID",
				"USER_UF_DEPARTMENT" => "USER.UF_DEPARTMENT"
			]
		]);

		$authUserList = [];
		while ($arUser = $rsUser->fetch())
		{
			$authUserList[$arUser["PHONE_NUMBER"]][] = $arUser;
		}

		foreach ($phoneItems as $item)
		{
			$bFound = false;
			foreach (($authUserList[$item["PHONE_NUMBER"]] ?? []) as $arUser)
			{
				$arUser = [
					'ID' => $arUser["USER_ID"],
					'CONFIRM_CODE' => $arUser["USER_CONFIRM_CODE"],
					'EXTERNAL_AUTH_ID' => $arUser["USER_ID"],
					'UF_DEPARTMENT' => $arUser["USER_UF_DEPARTMENT"],
					'PHONE_NUMBER' => $item["PHONE_NUMBER"],
				];

				$bFound = true;

				if (
					(string)$arUser["CONFIRM_CODE"] !== ''
					&& (
						!$bExtranetInstalled
						|| ( // both intranet
							isset($item["UF_DEPARTMENT"], $arUser["UF_DEPARTMENT"])
							&& !empty($item["UF_DEPARTMENT"])
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
							(!isset($item["UF_DEPARTMENT"]) || empty($item["UF_DEPARTMENT"]))
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
					$arPhoneToReinvite[] = \Bitrix\Intranet\Entity\User::initByArray($arUser);
				}
				else
				{
					$arPhoneExist[] = $item["PHONE_NUMBER"];
				}
			}

			if (!$bFound)
			{
				$item["LOGIN"] = $item["PHONE_NUMBER"];
				$arPhoneToRegister[] = \Bitrix\Intranet\Entity\User::initByArray($item);
			}
		}

		return [
			"PHONE_TO_REINVITE" => $arPhoneToReinvite,
			"PHONE_EXIST" => $arPhoneExist,
			"PHONE_TO_REGISTER" => $arPhoneToRegister
		];
	}

	public static function checkExistingUserByEmail(array $emailItems): array
	{
		$arUserForTransfer = [];
		$arEmailToReinvite = [];
		$arEmailExist = [];
		$arEmailToRegister = [];

		$bExtranetInstalled = (
			ModuleManager::isModuleInstalled("extranet")
			&& Option::get("extranet", "extranet_site") !== ''
		);

		if (Loader::includeModule('socialnetwork'))
		{
			$externalAuthIdList = Socialnetwork\ComponentHelper::checkPredefinedAuthIdList(
				array_diff(\Bitrix\Main\UserTable::getExternalUserTypes(), [ 'email', 'shop' ])
			);
		}

		$emailtList = array_column($emailItems, 'EMAIL');
		$filter = [];
		if (!empty($emailtList))
		{
			$filter["=LOGIN"] = $emailtList;
		}
		if (!empty($externalAuthIdList))
		{
			$filter['!=EXTERNAL_AUTH_ID'] = $externalAuthIdList;
		}

		$rsUser = UserTable::getList([
			'filter' => $filter,
			'select' => ["ID", "CONFIRM_CODE", "EXTERNAL_AUTH_ID", "UF_DEPARTMENT", 'EMAIL']
		]);
		$userList = [];
		while ($arUser = $rsUser->fetch())
		{
			$userList[$arUser['EMAIL']][] = \Bitrix\Intranet\Entity\User::initByArray($arUser);
		}

		foreach ($emailItems as $item)
		{
			$bFound = false;

			foreach (($userList[$item["EMAIL"]] ?? []) as $arUser)
			{
				/**
				 * @var \Bitrix\Intranet\Entity\User $arUser
				 */
				$bFound = true;

				if (
					$arUser->getExternalAuthId() === 'email'
					|| $arUser->getExternalAuthId() === 'shop'
				)
				{
					if (isset($item["UF_DEPARTMENT"]))
					{
						$arUser->setDepartmetnsIds(!is_array($item["UF_DEPARTMENT"]) ? [$item["UF_DEPARTMENT"]] : $item["UF_DEPARTMENT"]);
					}
					$arUserForTransfer[] = $arUser;
				}
				elseif (
					$arUser->getConfirmCode() !== ''
					&& (
						!$bExtranetInstalled
						|| ( // both intranet
							(isset($item["UF_DEPARTMENT"])
							&& !empty($item["UF_DEPARTMENT"]))
							&& $arUser->isIntranet()
						)
						||
						(	// both extranet
							(!isset($item["UF_DEPARTMENT"]) || empty($item["UF_DEPARTMENT"]))
							&& $arUser->isExtranet()
						)
					)
				)
				{
					$arEmailToReinvite[] = $arUser;
				}
				else
				{
					$arEmailExist[] = $item["EMAIL"];
				}
			}

			if (!$bFound)
			{
				$item['LOGIN'] = $item["EMAIL"];
				$arEmailToRegister[] = \Bitrix\Intranet\Entity\User::initByArray($item);
			}
		}

		return [
			"TRANSFER_USER" => $arUserForTransfer,
			"EMAIL_TO_REINVITE" => $arEmailToReinvite,
			"EMAIL_EXIST" => $arEmailExist,
			"EMAIL_TO_REGISTER" => $arEmailToRegister,
		];
	}

	public static function transferUser($usersForTransfer, &$errors)
	{
		global $APPLICATION, $USER;

		$transferedUserIds = [];
		$messageFactory = new InviteMessageFactory(Loc::getMessage("INTRANET_INVITATION_INVITE_MESSAGE_TEXT"));
		foreach ($usersForTransfer as $user)
		{
			if ($user->getExternalAuthId() === "shop" && Loader::includeModule("crm"))
			{
				$groupIds = array_merge($user->getGroupIds(), [\Bitrix\Crm\Order\BuyerGroup::getSystemGroupId()]);
				$user->setGroupIds($groupIds);
			}

			$user->setConfirmCode(\Bitrix\Main\Security\Random::getString(8));
			$transferedUserId = \CIntranetInviteDialog::TransferEmailUser($user->getId(), array(
				"CONFIRM_CODE" => $user->getConfirmCode(),
				"GROUP_ID" => $user->getGroupIds(), //$userGroups,
				"UF_DEPARTMENT" => $user->getDepartmetnsIds(),
				"SITE_ID" => SITE_ID,
			), false);

			if (!$transferedUserId)
			{
				if($e = $APPLICATION->GetException())
				{
					$errors[] = $e->GetString();
				}
				return false;
			}

			$transferedUserIds[] = $transferedUserId;
		}

		if (!empty($transferedUserIds))
		{
			foreach($transferedUserIds as $transferedUserId)
			{
				$res = InvitationTable::getList([
					'filter' => [
						'USER_ID' => $transferedUserId
					],
					'select' => [ 'ID' ]
				]);
				while ($invitationFields = $res->fetch())
				{
					InvitationTable::update($invitationFields['ID'], [
						'TYPE' => Invitation::TYPE_EMAIL,
						'ORIGINATOR_ID' => $USER->getId(),
						'DATE_CREATE' => new DateTime()
					]);
				}
			}

			Invitation::add([
				'USER_ID' => $transferedUserIds,
				'TYPE' => Invitation::TYPE_EMAIL
			]);
		}

		return $transferedUserIds;
	}

	public static function prepareUserData($userData): \Bitrix\Intranet\Entity\User
	{
		$isExtranet = !isset($userData["UF_DEPARTMENT"]) || empty($user["UF_DEPARTMENT"]);
		$siteIdByDepartmentId = \CIntranetInviteDialog::getUserSiteId(array(
			"UF_DEPARTMENT" => isset($userData["UF_DEPARTMENT"]) && is_array($userData["UF_DEPARTMENT"])
				? $userData["UF_DEPARTMENT"][0] : "",
			"SITE_ID" => SITE_ID
		));
		$arGroups = \CIntranetInviteDialog::getUserGroups($siteIdByDepartmentId, $isExtranet);

		$userData["CONFIRM_CODE"] = Random::getString(8, true);
		$userData["GROUP_ID"] = $arGroups;

		$entityUser = \Bitrix\Intranet\Entity\User::initByArray($userData);

		return $entityUser;
	}

	public static function inviteNewUsers($SITE_ID, $fields, $type): Result
	{
		$result = new Result();
		if (!is_array($fields) || empty($fields))
		{
			$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_EMAIL_ERROR")));

			return $result;
		}

		$res = self::checkItems($fields["ITEMS"]);
		if (!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());

			return $result;
		}

		$emailItems = $res->getData()['EMAIL_ITEMS'];
		$phoneItems = $res->getData()["PHONE_ITEMS"];

		$resPhone =	self::checkExistingUserByPhone($phoneItems);
		$resEmail = self::checkExistingUserByEmail($emailItems);

		if (
			empty($resPhone["PHONE_TO_REGISTER"])
			&& empty($resPhone["PHONE_TO_REINVITE"])
			&& empty($resEmail["EMAIL_TO_REGISTER"])
			&& empty($resEmail["EMAIL_TO_REINVITE"])
			&& empty($resEmail["TRANSFER_USER"])
		)
		{
			if (!empty($resEmail["EMAIL_EXIST"]))
			{
				$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_USER_EXIST_ERROR", [
					"#EMAIL_LIST#" => implode(', ', $resEmail["EMAIL_EXIST"]),
				])));
			}
			if (!empty($resPhone["PHONE_EXIST"]))
			{
				$result->addError(new Error(Loc::getMessage("INTRANET_INVITATION_USER_PHONE_EXIST_ERROR", [
					"#PHONE_LIST#" => implode(', ', $resPhone["PHONE_EXIST"]),
				])));
			}

			return $result;
		}

		$messageFactory = new InviteMessageFactory(
			Loc::getMessage("INTRANET_INVITATION_INVITE_MESSAGE_TEXT"),
			$fields['COLLAB_GROUP'] ?? null
		);
		EventManager::getInstance()->addEventHandler(
			'intranet',
			'onAfterUserRegistration',
			function (Event $event) use ($messageFactory) {
				$invitation = $event->getParameter('invitation');
				$user = $event->getParameter('user');
				if (InvitationType::EMAIL === $invitation->getType())
				{
					$messageFactory->create($user)->sendImmediately();
				}
			}
		);
		//reinvite users by email
		$reinvitedUserIds = [];
		foreach ($resEmail["EMAIL_TO_REINVITE"] as $user)
		{
			$messageFactory->create($user)->sendImmediately();
			$reinvitedUserIds[] = (int)$user->getId();
		}

		foreach ($resPhone['PHONE_TO_REINVITE'] as $userData)
		{
			$userId = $userData->getId();

			\CIntranetInviteDialog::reinviteUserByPhone($userId, array('checkB24' => true));
			$reinvitedUserIds[] = $userId;
		}

		$transferedUserIds = [];
		if (!empty($resEmail["TRANSFER_USER"]))
		{
			$errors = [];
			EventManager::getInstance()->addEventHandler(
				'intranet',
				'OnAfterTransferEMailUser',
				function ($arFields) use ($messageFactory) {
					$user = \Bitrix\Intranet\Entity\User::initByArray($arFields);
					$messageFactory->create($user)->sendImmediately();
				}
			);
			$transferedUserIds = self::transferUser($resEmail["TRANSFER_USER"], $errors);
			if (!empty($errors))
			{
				$result->addError(new Error($errors[0]));
			}
		}

		$registrationService = new RegistrationService();
		$phoneUserIds = [];
		if (!empty($resPhone["PHONE_TO_REGISTER"]))
		{
			try
			{
				$inviteCollection = $registrationService->registerUsers($resPhone["PHONE_TO_REGISTER"], InvitationType::PHONE, $type);
				$phoneUserIds = $inviteCollection->getUserIds();
			}
			catch (CreationFailedException $e)
			{
				$result->addErrors($e->getErrors()->toArray());
			}
		}

		$emailUserIds = [];
		if (!empty($resEmail["EMAIL_TO_REGISTER"]))
		{
			try
			{
				$inviteCollection = $registrationService->registerUsers($resEmail["EMAIL_TO_REGISTER"], InvitationType::EMAIL, $type);
				$emailUserIds = $inviteCollection->getUserIds();
			}
			catch (CreationFailedException $e)
			{
				$result->addErrors($e->getErrors()->toArray());
			}
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		if (isset($fields['COLLAB_GROUP']) && Loader::includeModule('bitrix24'))
		{
			$profileService = ProfileService::getInstance();

			// only new users
			foreach ($emailUserIds as $userId)
			{
				$profileService->markUserAsCollaber($userId);
			}
			foreach ($phoneUserIds as $userId)
			{
				$profileService->markUserAsCollaber($userId);
			}
		}

		return $result->setData(array_merge($phoneUserIds, $emailUserIds, $reinvitedUserIds, $transferedUserIds));
	}
}
