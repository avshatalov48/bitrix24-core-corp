<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\InvitationCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Intranet\Invitation\Register;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;

class RegistrationService
{
	public function register(User $user)
	{
		$siteIdByDepartmentId = \CIntranetInviteDialog::getUserSiteId([
			"UF_DEPARTMENT" => $user->getDepartmetnsIds() ? $user->getDepartmetnsIds()[0] : "",
			"SITE_ID" => SITE_ID
		]);
		$groupsIds = \CIntranetInviteDialog::getUserGroups($siteIdByDepartmentId, $user->isExtranet());
		$user->setConfirmCode(Random::getString(8, true));
		$user->setGroupIds($groupsIds);

		//TODO: extract to UserRepository class
		$userData = [
			'LOGIN' => $user->getLogin() ?? $user->getEmail(),
			'EMAIL' => $user->getEmail() ?? null,
			'PASSWORD' => \CUser::GeneratePasswordByPolicy($user->getGroupIds()),
			'CONFIRM_CODE' => $user->getConfirmCode(),
			'NAME' => $user->getName() ?? null,
			'LAST_NAME' => $user->getLastName() ?? null,
			'GROUP_ID' => $user->getGroupIds(),
			'LID' => $siteIdByDepartmentId,
			'UF_DEPARTMENT' => empty($user->getDepartmetnsIds()) ? [] : $user->getDepartmetnsIds(),
			'LANGUAGE_ID' => ($site = \CSite::GetArrayByID($siteIdByDepartmentId)) ? $site['LANGUAGE_ID'] : LANGUAGE_ID,
		];

		if ($user->getPhoneNumber())
		{
			$userData['PHONE_NUMBER'] = $user->getPhoneNumber();
			$userData['PERSONAL_MOBILE'] = $user->getPhoneNumber();
		}

		if ($user->getActive())
		{
			$userData['ACTIVE'] = $user->getPhoneNumber() ? 'Y' : 'N';
		}

		if($user->getXmlId())
		{
			$userData['XML_ID'] = $user->getXmlId();
		}

		$oldUserApi = new \CUser;
		$result = $oldUserApi->Add($userData);
		$errorCollection = new ErrorCollection();
		if ($result === false)
		{
			foreach(explode('<br>', $oldUserApi->LAST_ERROR) as $message)
			{
				$errorCollection->setError(new Error($message));
			}

			throw new CreationFailedException($errorCollection);
		}

		$userFields = $userData;
		$userFields['ID'] = $result;
		$user->setId((int)$result);
		foreach (GetModuleEvents('intranet', 'OnRegisterUser', true) as $event)
		{
			ExecuteModuleEventEx($event, [ $userFields ]);
		}

		return $user;
	}

	public function registerUser(\Bitrix\Intranet\Entity\User $user, InvitationType $type, string $formType)
	{
		$user = $this->register($user);
		$invitationService = new InviteService();
		try
		{
			$invitation = $invitationService->inviteUser($user, $type, $formType);
			(new Event(
				'intranet',
				'onAfterUserRegistration',
				[
					'user' => $user,
					'invitation' => $invitation
				]
			))
				->send()
			;

			return $invitation;
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	public function registerOnce(\Bitrix\Intranet\Entity\User $user, InvitationType $type, string $formType)
	{
		$invitationService = new InviteService();
		$invitation = $this->registerUser($user, $type, $formType);
		if ($invitation)
		{
			$event = new Event(
				'intranet',
				'onUserInvited',
				[
					'originatorId' => $invitation->getOriginatorId(),
					'userId' => [$invitation->getUserId()],
					'type' => $type->value
				]
			);
			$event->send();
		}
	}

	public function registerUsers(array $userCollection, InvitationType $type, string $formType)
	{
		$invitationCollection = new InvitationCollection();

		foreach ($userCollection as $user)
		{
			$invitation = $this->registerUser($user, $type, $formType);
			if ($invitation)
			{
				$invitationCollection->add($invitation);
			}
		}

		$processedUserIdList = $invitationCollection->getUserIds();
		if (!empty($processedUserIdList))
		{
			$event = new Event(
				'intranet',
				'onUserInvited',
				[
					'originatorId' => CurrentUser::get()->getId(),
					'userId' => $processedUserIdList,
					'type' => $type->value
				]
			);
			$event->send();
		}

		return $invitationCollection;
	}
}