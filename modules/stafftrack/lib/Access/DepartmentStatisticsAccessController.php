<?php

namespace Bitrix\StaffTrack\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\StaffTrack\Access\Model\UserModel;

class DepartmentStatisticsAccessController extends BaseAccessController
{
	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		return null;
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}
}