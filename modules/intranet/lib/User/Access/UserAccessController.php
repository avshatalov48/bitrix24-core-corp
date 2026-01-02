<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access;

use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\User\Access\Model\UserModel;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\Exception\UnknownActionException;
use Bitrix\Main\Access\User\AccessibleUser;

class UserAccessController extends BaseAccessController
{
	private static ?UserAccessController $currentUserAccessController = null;

	public static function createByDefault(): self
	{
		if (isset(self::$currentUserAccessController))
		{
			return self::$currentUserAccessController;
		}

		$userId = (int)CurrentUser::get()->getId();
		self::$currentUserAccessController = new self($userId);

		return self::$currentUserAccessController;
	}

	/**
	 * @param string|UserActionDictionary $action
	 * @param TargetUserModel|null $item
	 * @param $params
	 * @return bool
	 * @throws UnknownActionException
	 */
	public function check(string|UserActionDictionary $action, AccessibleItem $item = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;

		return parent::check($action, $item, $params);
	}

	protected function loadItem(int $itemId = null): ?TargetUserModel
	{
		return $itemId ? TargetUserModel::createFromId($itemId) : null;
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}
}
