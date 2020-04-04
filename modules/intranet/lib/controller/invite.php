<?
namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Invite extends \Bitrix\Main\Engine\Controller
{
	public function registerAction(array $fields)
	{
		global $USER;

		if (
			(
				Loader::includeModule('bitrix24')
				&& !\CBitrix24::isInvitingUsersAllowed()
			)
			|| !$USER->CanDoOperation('edit_all_users')
		)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'), 'INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'));
			return null;
		}

		$errorList = [];
		$userIdList = \CIntranetInviteDialog::registerNewUser(\CSite::getDefSite(), $fields, $errorList);

		return [
			'userIdList' => $userIdList,
			'errors' => $errorList
		];
	}

	public function reinviteAction(array $params = [])
	{
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$extranet = (!empty($params['extranet']) && $params['extranet'] == 'Y');

		if ($userId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_INVITE_NO_USER_ID'), 'INTRANET_CONTROLLER_INVITE_NO_USER_ID'));
			return null;
		}
		if (!$extranet)
		{
			$result = \CIntranetInviteDialog::reinviteUser(SITE_ID, $userId);
		}
		else
		{
			$result = \CIntranetInviteDialog::reinviteExtranetUser(SITE_ID, $userId);
		}

		return [
			'result' => $result
		];
	}
}

