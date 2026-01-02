<?php
namespace Bitrix\Intranet\Controller;

use Bitrix\Bitrix24\Integration\Network\RegisterSettingsSynchronizer;
use Bitrix\Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\Intranet\Service\InviteLinkGenerator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\Entity;
use Bitrix\Intranet\Dto;
use Bitrix\Intranet\Service\UseCase;
use Bitrix\Intranet;
use Bitrix\Main;
use CIntranetInviteDialog;

class Invite extends Main\Engine\Controller
{
	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				Dto\Invitation\UserInvitationDtoCollection::class,
				'users',
				function($className, array $users) {
					$collection = new $className;

					foreach ($users as $user)
					{
						$collection->add(new Dto\Invitation\UserInvitationDto(
							$user['name'] ?? null,
							$user['lastName'] ?? null,
							isset($user['phone']) ? new Entity\Type\Phone($user['phone']) : null,
							isset($user['email']) ? new Entity\Type\Email($user['email']) : null,
							null,
							$user['languageId'] ?? null,
						));
					}

					return $collection;
				}
			),
			new ExactParameter(
				Entity\Collection\EmailCollection::class,
				'emails',
				function($className, array $emails) {
					$collection = new $className;

					foreach ($emails as $email)
					{
						$collection->add(new Entity\Type\Email($email));
					}

					return $collection;
				}
			),
			new ExactParameter(
				Entity\Collection\PhoneCollection::class,
				'phones',
				function($className, array $phones) {
					$collection = new $className;

					foreach ($phones as $phone)
					{
						$collection->add(new Entity\Type\Phone($phone));
					}

					return $collection;
				}
			),
			new ExactParameter(
				Intranet\Public\Type\Collection\InvitationCollection::class,
				'emailInvitations',
				function($className, array $emailInvitations) {
					$collection = new Intranet\Public\Type\Collection\InvitationCollection();
					foreach ($emailInvitations as $invitation)
					{
						$email = $invitation['email'] ?? null;
						if (!$email)
						{
							continue;
						}
						$emailInvitation = new Intranet\Public\Type\EmailInvitation(
							$email,
							$invitation['name'] ?? null,
							$invitation['lastName'] ?? null,
						);
						$collection->add($emailInvitation);
					}

					return $collection;
				}
			),
			new ExactParameter(
				Intranet\Public\Type\Collection\InvitationCollection::class,
				'phoneInvitations',
				function($className, array $phoneInvitations) {
					$collection = new Intranet\Public\Type\Collection\InvitationCollection();
					foreach ($phoneInvitations as $invitation)
					{
						$phoneNumber = $invitation['phoneNumber'] ?? null;
						if (!$phoneNumber)
						{
							continue;
						}
						$emailInvitation = new Intranet\Public\Type\PhoneInvitation(
							$invitation['phoneNumber'] ?? null,
							$invitation['name'] ?? null,
							$invitation['lastName'] ?? null,
							$invitation['phoneCountry'] ?? null,
						);
						$collection->add($emailInvitation);
					}

					return $collection;
				}
			),
			new ExactParameter(
				Entity\Collection\DepartmentCollection::class,
				'departmentCollection',
				function($className, ?array $departmentIds = null): ?Entity\Collection\DepartmentCollection {
					if (!$departmentIds)
					{
						$departmentCollection = new Entity\Collection\DepartmentCollection();
						$departmentCollection->add((new Intranet\Integration\HumanResources\Department())->getRootDepartment());

						return $departmentCollection;
					}

					return (new Intranet\Integration\HumanResources\Department())->getByIds($departmentIds);
				}
			),
		];
	}

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new Intranet\ActionFilter\UserType(['employee', 'extranet']);
		$preFilters[] = new Intranet\Infrastructure\Controller\ActionFilter\InviteIntranetAccessControl();

		return $preFilters;
	}

	public function configureActions(): array
	{
		return [
			...parent::configureActions(),
			'register' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteLimitControl(),
				],
			],
			'inviteUsersToCollab' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteToCollabAccessControl(),
					new Intranet\ActionFilter\InviteLimitControl(),
				],
				'-prefilters' => [
					Intranet\Infrastructure\Controller\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'getLinkByCollabId' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteToCollabAccessControl(),
					new Intranet\ActionFilter\InviteLimitControl(),
				],
				'-prefilters' => [
					Intranet\Infrastructure\Controller\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'regenerateLinkByCollabId' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteToCollabAccessControl(),
				],
				'-prefilters' => [
					Intranet\Infrastructure\Controller\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'getEmailsInviteStatus' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				],
				'-prefilters' => [
					Intranet\ActionFilter\UserType::class,
					Intranet\Infrastructure\Controller\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'getPhoneNumbersInviteStatus' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				],
				'-prefilters' => [
					Intranet\ActionFilter\UserType::class,
					Intranet\Infrastructure\Controller\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'inviteUsersByEmail' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteLimitControl(),
					new Intranet\Infrastructure\Controller\ActionFilter\ActiveUserInvitation(new Intranet\Repository\UserRepository()),
					new Intranet\Infrastructure\Controller\ActionFilter\UserInvitedExtranet(new Intranet\Repository\UserRepository()),
				],
			],
			'inviteUsersByPhoneNumber' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteLimitControl(),
					new Intranet\Infrastructure\Controller\ActionFilter\ActiveUserInvitation(new Intranet\Repository\UserRepository()),
					new Intranet\Infrastructure\Controller\ActionFilter\UserInvitedExtranet(new Intranet\Repository\UserRepository()),
				],
			],
		];
	}

	public function registerAction(array $fields)
	{
		$result = \Bitrix\Intranet\Invitation::inviteUsers($fields);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->getData();
	}

	private function inviteUsers(
		Intranet\Public\Type\Collection\InvitationCollection $emailInvitations,
		?Entity\Collection\DepartmentCollection              $departmentCollection,
	): ?array
	{
		try
		{
			$invitationFacade = new Intranet\Public\Facade\Invitation\IntranetInvitationFacade($departmentCollection);
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
		catch (Intranet\Exception\InvitationFailedException $exception)
		{
			$this->addErrors($exception->getErrors()->toArray());

			return null;
		}
	}

	/**
	 * @restMethod intranet.invite.inviteUsersByEmail
	 */
	public function inviteUsersByEmailAction(
		Intranet\Public\Type\Collection\InvitationCollection $emailInvitations,
		?Entity\Collection\DepartmentCollection              $departmentCollection,
	): ?array
	{
		$invitedUsers = $this->inviteUsers($emailInvitations, $departmentCollection);

		if (is_array($invitedUsers))
		{
			$this->setDefaultUserGroups($invitedUsers);
		}

		return $invitedUsers;
	}

	/**
	 * @restMethod intranet.invite.inviteUsersByPhoneNumber
	 */
	public function inviteUsersByPhoneNumberAction(
		Intranet\Public\Type\Collection\InvitationCollection $phoneInvitations,
		?Entity\Collection\DepartmentCollection              $departmentCollection,
	): ?array
	{
		if (!Loader::includeModule('bitrix24'))
		{
			$this->addError(new Error('This method is not available'));
			return null;
		}
		$invitedUsers = $this->inviteUsers($phoneInvitations, $departmentCollection);

		if (is_array($invitedUsers))
		{
			$this->setDefaultUserGroups($invitedUsers);
		}

		return $invitedUsers;
	}

	/**
	 * @restMethod intranet.invite.getLinkByDepartments
	 */
	public function getLinkByDepartmentsAction(
		?Entity\Collection\DepartmentCollection $departmentCollection
	): ?string
	{
		if (!$departmentCollection)
		{
			$departmentCollection = new Entity\Collection\DepartmentCollection();
		}
		$departmentRepository = Intranet\Service\ServiceContainer::getInstance()->hrDepartmentRepository();
		if ($departmentCollection->empty())
		{
			$departmentCollection->add($departmentRepository->getRootDepartment());
		}

		$linkGenerator = InviteLinkGenerator::createByDepartmentsIds(
			$departmentCollection->map(fn(Entity\Department $department) => $department->getId())
		);

		if (!$linkGenerator)
		{
			$this->addError(new Error('Failed to create a link generator'));

			return null;
		}

		return $linkGenerator->getShortCollabLink();
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 */
	public function inviteUsersToCollabAction(
		int $collabId,
		Dto\Invitation\UserInvitationDtoCollection $users,
	): Main\Engine\Response\AjaxJson
	{
		$useCase = new UseCase\Invitation\BulkInviteUsersToCollabAndPortal();
		$result = $useCase->execute(
			collabId: $collabId,
			userInvitationDtoCollection: $users,
		);

		if (!$result->isSuccess())
		{
			return Main\Engine\Response\AjaxJson::createError($result->getErrorCollection());
		}

		return Main\Engine\Response\AjaxJson::createSuccess($result->getData());
	}

	public function getEmailsInviteStatusAction(
		Entity\Collection\EmailCollection $emails
	): Main\Engine\Response\AjaxJson
	{
		$result = Intranet\Service\ServiceContainer::getInstance()
			->inviteStatusService()
			->getInviteStatusesByEmailCollection($emails)
		;

		return Main\Engine\Response\AjaxJson::createSuccess($result);
	}

	public function getPhoneNumbersInviteStatusAction(
		Entity\Collection\PhoneCollection $phones
	): Main\Engine\Response\AjaxJson
	{
		$result = Intranet\Service\ServiceContainer::getInstance()
			->inviteStatusService()
			->getInviteStatusesByPhoneCollection($phones)
		;

		return Main\Engine\Response\AjaxJson::createSuccess($result);
	}

	public function reinviteWithChangeContactAction(int $userId, ?string $newEmail = null, ?string $newPhone = null): ?array
	{
		$result = ProfileService::getInstance()->reInviteUserWithChangeContact($userId, $newEmail, $newPhone);

		if (!$result->isSuccess())
		{
			$errorCode = 'Unknown error';
			$errorMessage = 'Unknown error';

			foreach ($result->getErrors() as $error)
			{
				$messageCode = match($error->getMessage()) {
					'user_is_not_employee' => 'INTRANET_CONTROLLER_INVITE_ERROR_USER_IS_NOT_EMPLOYEE',
					'user_not_found' => 'INTRANET_CONTROLLER_INVITE_ERROR_USER_NOT_FOUND',
					'user_already_confirmed' => 'INTRANET_CONTROLLER_INVITE_ERROR_USER_ALREADY_CONFIRMED',
					'invalid_response' => 'INTRANET_CONTROLLER_INVITE_ERROR_INVALID_RESPONSE',
					'invite_limit' => 'INTRANET_CONTROLLER_INVITE_ERROR_INVITE_LIMIT',
					default => null,
				};

				if (empty($messageCode))
				{
					if (is_string($error->getCode()) && !empty($error->getCode()))
					{
						$errorMessage = $error->getCode();
						$errorCode = $error->getMessage();
					}
					else
					{
						$messageCode = 'INTRANET_CONTROLLER_INVITE_ERROR_UNKNOWN';
					}
				}

				if (isset($messageCode))
				{
					$errorCode = $error->getMessage();
					$errorMessage = Loc::getMessage($messageCode);

					break;
				}
			}

			$this->addError(
				new Error($errorMessage, $errorCode)
			);

			return null;
		}

		if (isset($newPhone))
		{
			return [
				'result' => true
			];
		}
		else
		{
			return $this->reInviteInternal($userId);
		}
	}

	public function reinviteAction(array $params = [])
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		if ($userId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_USER_ID'), 'INTRANET_CONTROLLER_INVITE_NO_USER_ID'));

			return null;
		}

		return $this->reInviteInternal(
			$userId
		);
	}

	private function reInviteInternal(int $userId): ?array
	{
		$res = UserTable::getList([
			'filter' => [
				'=ID' => $userId
			],
			'select' => [
				'EMAIL',
				'CONFIRM_CODE',
				'PHONE' => 'PHONE_AUTH.PHONE_NUMBER',
			]
		]);
		$userFields = $res->fetch();
		if (
			!$userFields
			|| empty($userFields['CONFIRM_CODE'])
		)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_USER_NOT_FOUND'), 'INTRANET_CONTROLLER_INVITE_USER_NOT_FOUND'));
			return null;
		}

		if (empty($userFields['EMAIL']) && empty($userFields['PHONE']))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_FAILED'), 'INTRANET_CONTROLLER_INVITE_FAILED'));
			return null;
		}

		$isEmployee = (new Intranet\Integration\HumanResources\HrUserService)->isEmployee(new Entity\User(id: $userId));
		$extranet = Loader::includeModule('extranet') && !$isEmployee;
		if (!$extranet)
		{
			if ($userFields['EMAIL'])
			{
				$result = \CIntranetInviteDialog::reinviteUser(SITE_ID, $userId);
			}
			else
			{
				$reinviteResult = \CIntranetInviteDialog::reinviteUserByPhone($userId);
				$result = $reinviteResult->isSuccess();
				if (!$result && !empty($reinviteResult->getError()?->getMessage()))
				{
					$this->addError($reinviteResult->getError());
					return null;
				}
			}
		}
		else
		{
			$result = \CIntranetInviteDialog::reinviteExtranetUser(SITE_ID, $userId);
		}

		if (!$result)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_USER_NOT_FOUND'), 'INTRANET_CONTROLLER_INVITE_USER_NOT_FOUND'));
			return null;
		}

		return [
			'result' => $result
		];
	}

	public function deleteInvitationAction(array $params = [])
	{
		global $USER;

		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$currentUserId = $this->getCurrentUser()->getId();

		if (
			$userId <= 0
			|| !Loader::includeModule('socialnetwork')
		)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_USER_ID'), 'INTRANET_CONTROLLER_INVITE_NO_USER_ID'));
			return null;
		}

		if (Invitation::canDelete([
			'CURRENT_USER_ID' => $currentUserId,
			'USER_ID' => $userId
		]))
		{
			$result = $USER->delete($userId);
			if (!$result)
			{
				$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_DELETE_FAILED'), 'INTRANET_CONTROLLER_INVITE_DELETE_FAILED'));
				return null;
			}
		}
		else
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'), 'INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'));
			return null;
		}

		return [
			'result' => $result
		];
	}

	public function getDataAction()
	{
		$data = [
			'registerUrl' => Invitation::getRegisterUrl(),
			'adminConfirm' => Invitation::getRegisterAdminConfirm(),
			'disableAdminConfirm' => !Invitation::canListDelete(),
			'sharingMessage' => Invitation::getRegisterSharingMessage(),
			'rootStructureSectionId' => Invitation::getRootStructureSectionId(),
			'emailRequired' => Option::get('main', 'new_user_email_required', 'N') === 'Y',
			'phoneRequired' => Option::get('main', 'new_user_phone_required', 'N') === 'Y'
		];

		if (Loader::includeModule('bitrix24'))
		{
			$data['creatorEmailConfirmed'] = !\Bitrix\Bitrix24\Service\PortalSettings::getInstance()
				->getEmailConfirmationRequirements()
				->isRequiredByType(Type::INVITE_USERS);
		}
		else
		{
			$data['creatorEmailConfirmed'] = true;
		}

		return $data;
	}

	public function getRegisterUrlAction(array $params = [])
	{
		return [
			'result' => Intranet\Invitation::getRegisterUrl()
		];
	}

	public function setRegisterSettingsAction(array $params = [])
	{
		$result = '';

		$data = [];

		if (
			isset($params['SECRET'])
			&& $params['SECRET'] <> ''
		)
		{
			$data['REGISTER_SECRET'] = $params['SECRET'];
		}
		elseif (
			isset($params['CONFIRM'])
			&& in_array($params['CONFIRM'], [ 'N', 'Y'])
		)
		{
			$data['REGISTER_CONFIRM'] = $params['CONFIRM'];
		}

		if (
			!empty($data)
			&& Loader::includeModule("bitrix24")
		)
		{
			RegisterSettingsSynchronizer::setRegisterSettings($data);
			$result = 'success';
		}

		return [
			'result' => $result
		];
	}

	public function copyRegisterUrlAction()
	{
		$userId = $this->getCurrentUser()->getId();

		if ($userId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_USER_ID'), 'INTRANET_CONTROLLER_INVITE_NO_USER_ID'));
			return null;
		}

		$allowSelfRegister = false;
		if (
			Loader::includeModule('bitrix24')
		)
		{
			$registerSettings = RegisterSettingsSynchronizer::getRegisterSettings();
			if ($registerSettings['REGISTER'] === 'Y')
			{
				$allowSelfRegister = true;
			}
		}

		if (!$allowSelfRegister)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'), 'INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'));
			return null;
		}

		$event = new Event('intranet', 'OnCopyRegisterUrl', [
			'userId' => $userId
		]);
		$event->send();

		return [
			'result' => true
		];
	}

	public function confirmUserRequestAction(int $userId, string $isAccept): bool
	{
		if (!Intranet\CurrentUser::get()->isAdmin())
		{
			return false;
		}

		$result = Invitation::confirmUserRequest($userId, $isAccept === 'Y');
		$this->addErrors($result->getErrors());

		return $result->isSuccess();
	}

	public function getLinkByCollabIdAction(int $collabId, string $userLang = LANGUAGE_ID): Main\Engine\Response\AjaxJson
	{
		$linkGenerator = InviteLinkGenerator::createByCollabId($collabId, $userLang);

		if (!$linkGenerator)
		{
			$this->addError(new Error('Unable to create link generator'));

			return Main\Engine\Response\AjaxJson::createError($this->errorCollection);
		}

		$event = new Event(
			'intranet',
			'onCopyCollabInviteLink',
			[
				'collabId' => $collabId,
				'userId' => Intranet\CurrentUser::get()->getId(),
			]
		);
		Main\EventManager::getInstance()->send($event);

		return Main\Engine\Response\AjaxJson::createSuccess($linkGenerator->getShortCollabLink());
	}

	public function regenerateLinkByCollabIdAction(int $collabId): Main\Engine\Response\AjaxJson
	{
		$codeGenerator = Intranet\Infrastructure\LinkCodeGenerator::createByCollabId($collabId);

		if (!$codeGenerator)
		{
			$this->addError(new Error('Unable to create code generator'));

			return Main\Engine\Response\AjaxJson::createError($this->errorCollection);
		}

		$codeGenerator->generate();

		(new Event(
			'intranet',
			'onRegenerateCollabInviteLink',
			[
				'collabId' => $collabId,
				'userId' => Intranet\CurrentUser::get()->getId(),
			]
		))->send();

		return Main\Engine\Response\AjaxJson::createSuccess();
	}

	public function getInviteDialogLinkAction(string $source): string
	{
		return CIntranetInviteDialog::showInviteDialogLink(
			[
				'analyticsLabel' => [
					'analyticsLabel[source]' => $source,
				]
			]
		);
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