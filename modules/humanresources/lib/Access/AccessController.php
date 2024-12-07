<?php

namespace Bitrix\HumanResources\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\HumanResources\Access\Model\UserModel;

class AccessController extends BaseAccessController
{
	public function __construct(int $userId)
	{
		parent::__construct($userId);
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		return null;
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}
}