<?php

namespace Bitrix\Intranet\Infrastructure\Controller;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Exception\InvitationFailedException;
use Bitrix\Intranet\Infrastructure\Controller\AutoWire\DepartmentParameterTrait;
use Bitrix\Intranet\Public\Facade\Invitation\IntranetInvitationFacade;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Public\Type\EmailInvitation;
use Bitrix\Intranet\Public\Type\PhoneInvitation;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\ModuleManager;

class Invitation extends \Bitrix\Main\Engine\Controller
{
	use DepartmentParameterTrait;

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new \Bitrix\Intranet\ActionFilter\UserType(['employee', 'extranet']);
		$preFilters[] = new ActionFilter\InviteIntranetAccessControl();

		return $preFilters;
	}

	public function configureActions(): array
	{
		return [
			...parent::configureActions(),
			'inviteUsers' => [
				'+prefilters' => [
					new \Bitrix\Intranet\ActionFilter\InviteLimitControl(),
					new \Bitrix\Intranet\Infrastructure\Controller\ActionFilter\ActiveUserInvitation(new UserRepository()),
					new \Bitrix\Intranet\Infrastructure\Controller\ActionFilter\UserInvitedExtranet(new UserRepository()),
				],
			],
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			$this->createDepartmentCollectionParameter(),
			new ExactParameter(
				InvitationCollection::class,
				'invitations',
				function($className, array $invitations) {
					$collection = new InvitationCollection();
					$isPhoneInvitationAvailable = ModuleManager::isModuleInstalled('bitrix24');

					foreach ($invitations as $invitation)
					{
						$email = $invitation['email'] ?? null;

						if ($email)
						{
							$emailInvitation = new EmailInvitation(
								$email,
								$invitation['name'] ?? null,
								$invitation['lastName'] ?? null,

							);
							$collection->add($emailInvitation);

							continue;
						}

						$phoneNumber = $invitation['phoneNumber'] ?? $invitation['phone'] ?? null;

						if ($phoneNumber && $isPhoneInvitationAvailable)
						{
							$phoneInvitation = new PhoneInvitation(
								$phoneNumber,
								$invitation['name'] ?? null,
								$invitation['lastName'] ?? null,
								$invitation['phoneCountry'] ?? null,
							);
							$collection->add($phoneInvitation);
						}
					}

					return $collection;
				}
			),
		];
	}

	public function inviteUsersAction(
		InvitationCollection $invitations,
		?DepartmentCollection $departmentCollection,
	): ?array
	{
		$invitedUsers = $this->inviteUsers($invitations, $departmentCollection);

		if (is_array($invitedUsers))
		{
			$this->setDefaultUserGroups($invitedUsers);
		}

		return $invitedUsers;
	}

	private function inviteUsers(
		InvitationCollection $emailInvitations,
		?DepartmentCollection $departmentCollection,
	): ?array
	{
		try
		{
			$invitationFacade = new IntranetInvitationFacade($departmentCollection);
			$userCollection = $invitationFacade->inviteByCollection($emailInvitations);

			$response = [];
			foreach ($userCollection as $user)
			{
				$response[] = [
					'id' => $user->getId(),
					'login' => $user->getLogin(),
					'email' => $user->getEmail(),
					'authPhoneNumber' => $user->getAuthPhoneNumber(),
					'name' => $user->getName(),
					'lastName' => $user->getLastName(),
					'fullName' => $user->getFormattedName(),
					'invitationStatus' => $user->getInviteStatus()->value,
				];
			}

			return $response;
		}
		catch (InvitationFailedException $exception)
		{
			$this->addErrors($exception->getErrors()->toArray());

			return null;
		}
	}

	private function setDefaultUserGroups(array $users): void
	{
		$groupsIds = \CIntranetInviteDialog::getUserGroups(SITE_ID);

		foreach ($users as $user)
		{
			if (isset($user['id']))
			{
				\CUser::SetUserGroup($user['id'], $groupsIds);
			}
		}
	}
}
