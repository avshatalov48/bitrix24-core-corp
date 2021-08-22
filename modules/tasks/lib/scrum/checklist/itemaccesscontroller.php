<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\CheckList\CheckListFacade;

class ItemAccessController extends BaseAccessController
{
	public static function can($userId, string $action, $itemId = null, $params = null): bool
	{
		if (ItemChecklistFacade::$currentAccessAction === CheckListFacade::ACTION_REMOVE)
		{
			return true;
		}

		return false;
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		return ItemModel::createFromId($itemId);
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}
}