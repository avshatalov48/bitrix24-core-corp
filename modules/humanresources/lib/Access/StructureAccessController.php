<?php

namespace Bitrix\HumanResources\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\Event\EventDictionary;
use Bitrix\Main\Access\Exception\UnknownActionException;
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

	/**
	 * @throws UnknownActionException
	 */
	public function check(string $action, AccessibleItem $item = null, $params = null): bool
	{
		$params[StructureBaseRule::PERMISSION_ID_KEY] = StructureActionDictionary::getActionPermissionMap()[$action] ?? null;

		static $ruleHandler = [];

		if (!isset($ruleHandler[$action]))
		{
			$ruleHandler[$action] = $this->ruleFactory->createFromAction($action, $this);
		}

		$rule = $ruleHandler[$action] ?? null;

		if (!$rule)
		{
			throw new UnknownActionException($action);
		}

		$event = $this->sendEvent(EventDictionary::EVENT_ON_BEFORE_CHECK, $action, $item, $params);
		$isAccess = $event->isAccess();

		if (!is_null($isAccess))
		{
			return $isAccess;
		}

		$isAccess = $rule->execute($item, $params);

		$event = $this->sendEvent(EventDictionary::EVENT_ON_AFTER_CHECK, $action, $item, $params, $isAccess);

		return $event->isAccess() ?? $isAccess;
	}
}