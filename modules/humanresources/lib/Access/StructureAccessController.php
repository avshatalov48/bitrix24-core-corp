<?php

namespace Bitrix\HumanResources\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\HumanResources\Access\Model\UserModel;
use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Rule\Factory\RuleFactory;
use Bitrix\HumanResources\Access\Rule\StructureBaseRule;

final class StructureAccessController extends BaseAccessController
{
	public function __construct(int $userId)
	{
		parent::__construct($userId);
		$this->ruleFactory = new RuleFactory();
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		return NodeModel::createFromId($itemId);
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return UserModel::createFromId($userId);
	}

	public function check(string $action, AccessibleItem $item = null, $params = null): bool
	{
		$params[StructureBaseRule::PERMISSION_ID_KEY] = StructureActionDictionary::getActionPermissionMap()[$action] ?? null;

		return parent::check($action, $item, $params);
	}
}