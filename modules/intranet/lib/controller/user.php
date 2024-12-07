<?php
namespace Bitrix\Intranet\Controller;

class User extends \Bitrix\Main\Engine\Controller
{
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
}

