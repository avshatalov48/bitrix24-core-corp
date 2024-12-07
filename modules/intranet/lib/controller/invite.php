<?php
namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Config\Option;
use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserTable;
use Bitrix\Socialservices\Network;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet;
use Bitrix\Main;

class Invite extends Main\Engine\Controller
{
	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new Intranet\ActionFilter\UserType(['employee', 'extranet']);
		$preFilters[] = new Intranet\ActionFilter\InviteIntranetAccessControl();

		return $preFilters;
	}

	public function configureActions(): array
	{
		$configureActions = parent::configureActions();

		$configureActions['register'] = [
			'+prefilters' => [
				new Intranet\ActionFilter\InviteLimitControl()
			]
		];

		return $configureActions;
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
			$data['creatorEmailConfirmed'] = \CBitrix24::isEmailConfirmed();
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
}

