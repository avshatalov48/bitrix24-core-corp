<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Main\Access\AccessibleItem;

class TemplateAccessController extends BaseAccessController
	implements AccessErrorable
{
	use AccessErrorTrait;

	public function check(string $action, AccessibleItem $item = null, $params = null): bool
	{
		if (!$item)
		{
			$item = TemplateModel::createNew();
		}

		if ($item->isDeleted())
		{
			return false;
		}

		return parent::check($action, $item, $params);
	}

	protected function loadItem(int $itemId = null): AccessibleItem
	{
		if ($itemId)
		{
			return TemplateModel::createFromId($itemId);
		}

		return TemplateModel::createNew();
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}
}