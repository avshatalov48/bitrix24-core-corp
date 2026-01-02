<?php
namespace Bitrix\Intranet\Controller;

use Bitrix\Intranet\Internals\Trait\UserUpdateError;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Intranet\User\Access\UserAccessController;
use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Error;

class User extends \Bitrix\Main\Engine\Controller
{
	use UserUpdateError;

	public function setAdminRightsAction(array $params)
	{
		$currentUser = $this->getCurrentUser();
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);

		return \Bitrix\Intranet\Util::setAdminRights([
			'userId' => $userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin()
		]);
	}

	public function removeAdminRightsAction(array $params)
	{
		$currentUser = $this->getCurrentUser();
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);

		return \Bitrix\Intranet\Util::removeAdminRights([
			'userId' => $userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin()
		]);
	}

	public function fireAction(int $userId): bool
	{
		$access = UserAccessController::createByDefault();
		$targetUser = TargetUserModel::createFromId($userId);

		if (
			!$access->check(UserActionDictionary::FIRE, $targetUser)
		)
		{
			$this->addError(new Error('no permissions', 403));

			return false;
		}

		$cUser = new \CUser;
		$result = $cUser->Update($userId, ['ACTIVE' => 'N']);

		if ($result)
		{
			$deactivateUser = new \Bitrix\Intranet\User($userId);
			Invitation::fullSyncCounterByUser($deactivateUser->fetchOriginatorUser());
		}
		else
		{
			$this->addErrors(
				$this->getErrorsFromUpdateLastError($cUser->LAST_ERROR),
			);

			return false;
		}

		return true;
	}

	public function restoreAction(int $userId)
	{
		$access = UserAccessController::createByDefault();
		$user = TargetUserModel::createFromId($userId);

		if (
			!$access->check(UserActionDictionary::RESTORE, $user)
		)
		{
			$this->addError(new Error('no permissions', 403));

			return null;
		}

		$cUser = new \CUser;
		$result = $cUser->Update($userId, ['ACTIVE' => 'Y']);

		if ($result)
		{
			$activateUser = new \Bitrix\Intranet\User($userId);
			Invitation::fullSyncCounterByUser($activateUser->fetchOriginatorUser());
		}
		else
		{
			$this->addErrors(
				$this->getErrorsFromUpdateLastError($cUser->LAST_ERROR),
			);

			return false;
		}

		return true;
	}
}
