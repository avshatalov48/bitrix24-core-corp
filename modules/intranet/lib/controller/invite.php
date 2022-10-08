<?
namespace Bitrix\Intranet\Controller;

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
		$preFilters[] = new Intranet\ActionFilter\InviteAccessControl();

		return $preFilters;
	}

	public function registerAction(array $fields)
	{
		$errorList = [];
		$userIdList = \CIntranetInviteDialog::registerNewUser(\CSite::getDefSite(), $fields, $errorList);

		if (!empty($errorList))
		{
			$errorText = implode(
				"\n",
				array_filter(
					$errorList,
					function ($value)
					{
						return !empty($value);
					}
				)
			);
			$this->addError(new Error($errorText, 'INTRANET_CONTROLLER_INVITE_REGISTER_ERROR'));
		}
		else
		{
			\CIntranetInviteDialog::logAction(
				$userIdList,
				(
					isset($fields['DEPARTMENT_ID'])
					&& (int)$fields['DEPARTMENT_ID'] > 0
						? 'intranet'
						: 'extranet'
				),
				'invite_user',
				(
					!empty($fields['PHONE'])
						? 'sms_dialog'
						: 'invite_dialog'
				),
				(
					!empty($fields['CONTEXT'])
					&& $fields['CONTEXT'] === 'mobile'
						? 'mobile'
						: 'web'
				)
			);
		}

		return [
			'userIdList' => $userIdList,
			'errors' => []
		];
	}

	public function reinviteAction(array $params = [])
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		if ($userId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_USER_ID'), 'INTRANET_CONTROLLER_INVITE_NO_USER_ID'));
			return null;
		}

		$res = UserTable::getList([
			'filter' => [
				'=ID' => $userId
			],
			'select' => [
				'EMAIL', 'CONFIRM_CODE'
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

		if (empty($userFields['EMAIL']))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_FAILED'), 'INTRANET_CONTROLLER_INVITE_FAILED'));
			return null;
		}

		$extranet = (
			isset($params['extranet'])
				? (!empty($params['extranet']) && $params['extranet'] == 'Y')
				: (
					Loader::includeModule('extranet')
					&& !\CExtranet::isIntranetUser(SITE_ID, $userId)
				)
		);

		if (!$extranet)
		{
			$result = \CIntranetInviteDialog::reinviteUser(SITE_ID, $userId);
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
		return [
			'registerUrl' => Invitation::getRegisterUrl(),
			'adminConfirm' => Invitation::getRegisterAdminConfirm(),
			'disableAdminConfirm' => !Invitation::canListDelete(),
			'sharingMessage' => Invitation::getRegisterSharingMessage(),
			'rootStructureSectionId' => Invitation::getRootStructureSectionId(),
		];
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

}

