<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;

class TypeAccessController extends BaseAccessController
{
	public static function can($userId, string $action, $itemId = null, $params = null): bool
	{
		return true;
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		return TypeModel::createFromId($itemId);
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}
}