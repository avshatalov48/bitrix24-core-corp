<?php
namespace Bitrix\Intranet\Controller;

use Bitrix\Bitrix24\Portal\Notification\EmailConfirmationPopup;
use Bitrix\Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type;
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
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Socialservices\Network;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\Entity;
use Bitrix\Intranet\Dto;
use Bitrix\Intranet\Service\UseCase;
use Bitrix\Intranet;
use Bitrix\Main;

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
							isset($user['email']) ? new Entity\Type\Email($user['email']) : null
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
		];
	}

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new Intranet\ActionFilter\UserType(['employee', 'extranet']);
		$preFilters[] = new Intranet\ActionFilter\InviteIntranetAccessControl();

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
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'getLinkByCollabId' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteToCollabAccessControl(),
					new Intranet\ActionFilter\InviteLimitControl(),
				],
				'-prefilters' => [
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'regenerateLinkByCollabId' => [
				'+prefilters' => [
					new Intranet\ActionFilter\InviteToCollabAccessControl(),
				],
				'-prefilters' => [
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'getEmailsInviteStatus' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				],
				'-prefilters' => [
					Intranet\ActionFilter\UserType::class,
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
				],
			],
			'getPhoneNumbersInviteStatus' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				],
				'-prefilters' => [
					Intranet\ActionFilter\UserType::class,
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
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
		$result = $useCase->execute(collabId: $collabId, userInvitationDtoCollection: $users);

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
			$userId,
			isset($params['extranet']) ? $params['extranet'] === 'Y' : null,
		);
	}

	private function reInviteInternal(int $userId, ?bool $extranet = null): ?array
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

		$extranet ??=
			Loader::includeModule('extranet')
			&& !\CExtranet::isIntranetUser(SITE_ID, $userId)
		;
		if (!$extranet)
		{
			if ($userFields['EMAIL'])
			{
				$result = \CIntranetInviteDialog::reinviteUser(SITE_ID, $userId);
			}
			else
			{
				$result = \CIntranetInviteDialog::reinviteUserByPhone($userId);
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
			&& Loader::includeModule("socialservices")
		)
		{
			Network::setRegisterSettings($data);
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
			ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('socialservices')
		)
		{
			$registerSettings = \Bitrix\Socialservices\Network::getRegisterSettings();
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

	public function getLinkByCollabIdAction(int $collabId): Main\Engine\Response\AjaxJson
	{
		$linkGenerator = InviteLinkGenerator::createByCollabId($collabId);

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
}

