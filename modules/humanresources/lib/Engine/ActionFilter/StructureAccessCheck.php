<?php

namespace Bitrix\HumanResources\Engine\ActionFilter;

use Bitrix\HumanResources\Attribute\Access\LogicAnd;
use Bitrix\HumanResources\Attribute\Access\LogicOr;
use Bitrix\HumanResources\Attribute\ActionAccess;
use Bitrix\HumanResources\Attribute\StructureActionAccess;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\Main;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Localization\Loc;

final class StructureAccessCheck extends Main\Engine\ActionFilter\Base
{
	public const RULE_OR = 'OR';
	public const RULE_AND = 'AND';

	public const PREFILTER_KEY = 'ACCESS_CHECK';
	private const ERROR_ACCESS_DENIED = 'STRUCTURE_ACCESS_DENIED';

	private StructureAccessController $accessController;
	/** @var array<string, StructureRuleItem> */
	private array $rules = [];

	/** @var list<LogicRule> */
	private array $logicRules = [];

	public function __construct()
	{
		parent::__construct();
		$this->accessController = new StructureAccessController(Main\Engine\CurrentUser::get()->getId());
	}

	public function addRule(
		string $accessPermission,
		?AccessibleItemType $itemType = null,
		?string $itemIdRequestKey = null,
		?string $itemParentIdRequestKey = null,
	): self
	{
		$this->rules[$accessPermission] =
			new StructureRuleItem($accessPermission, $itemType, $itemIdRequestKey, $itemParentIdRequestKey);

		return $this;
	}

	public function addRuleFromAttribute(StructureActionAccess|LogicOr|LogicAnd $attribute): self
	{
		if ($attribute instanceof StructureActionAccess)
		{
			return $this->addRule(
				$attribute->permission,
				$attribute->itemType,
				$attribute->itemIdRequestKey,
				$attribute->itemParentIdRequestKey,
			);
		}

		return $this->addLogicRuleFromAttribute($attribute);
	}

	public function onBeforeAction(\Bitrix\Main\Event $event): ?Main\EventResult
	{
		if (!$this->checkRules())
		{
			return $this->getAccessErrorResult();
		}

		return null;
	}

	private function getAccessErrorResult(): Main\EventResult
	{
		$this->addError(
			new Main\Error(
				Loc::getMessage('HR_ACTION_ACCESS_DENIED'),
				self::ERROR_ACCESS_DENIED,
			),
		);

		return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
	}

	private function addLogicRuleFromAttribute(LogicAnd|LogicOr $logicAttribute): self
	{
		$rules = array_map(
			fn(StructureActionAccess $condition) => new StructureRuleItem(
				$condition->permission,
				$condition->itemType,
				$condition->itemIdRequestKey,
				$condition->itemParentIdRequestKey,
			),
			$logicAttribute->conditions,
		);

		$this->logicRules[] = new LogicRule(
			$logicAttribute instanceof LogicOr ? self::RULE_OR : self::RULE_AND,
			...$rules,
		);

		return $this;
	}

	private function checkRules(): bool
	{
		foreach ($this->rules as $rule)
		{
			if (!$this->checkRule($rule))
			{
				return false;
			}
		}

		foreach ($this->logicRules as $logicRule)
		{
			if (!$this->checkLogicRule($logicRule))
			{
				return false;
			}
		}

		return true;
	}

	private function checkLogicRule(LogicRule $rule): bool
	{
		if ($rule->logicOperator === self::RULE_OR)
		{
			foreach ($rule->rules as $rule)
			{
				if ($this->checkRule($rule))
				{
					return true;
				}
			}

			return false;
		}

		foreach ($rule->rules as $rule)
		{
			if (!$this->checkRule($rule))
			{
				return false;
			}
		}

		return true;
	}

	private function checkRule(StructureRuleItem $rule)
	{
		$acton = $rule->accessPermission;

		$item = null;
		if ($rule->itemType)
		{
			$item =
				$this->createAccessibleItemByType(
					$rule->itemType,
					$rule->itemIdRequestKey,
					$rule->itemParentIdRequestKey,
				);
		}

		if (!$this->accessController->check($acton, $item))
		{
			return false;
		}

		return true;
	}

	private function createAccessibleItemByType(
		AccessibleItemType $type,
		?string $requestIdKey = null,
		?string $requestParentIdKey = null,
	): ?AccessibleItem
	{
		if ($type === AccessibleItemType::NODE)
		{
			return $this->createAccessibleItem($requestIdKey, $requestParentIdKey);
		}

		if ($type === AccessibleItemType::NODE_MEMBER)
		{
			$memberId = $this->getValueByRequestId($requestIdKey);
			if (is_numeric($memberId))
			{
				$nodeMember = Container::getNodeMemberRepository()->findById($memberId);
				if ($nodeMember)
				{
					return NodeModel::createFromId($nodeMember->nodeId);
				}
			}
		}

		return null;
	}

	private function createAccessibleItem(
		?string $requestIdKey = null,
		?string $requestParentIdKey = null,
	): AccessibleItem
	{
		if (!$requestIdKey && !$requestParentIdKey)
		{
			return NodeModel::createFromId(null);
		}

		$id = null;
		if ($requestIdKey)
		{
			$id = $this->getValueByRequestId($requestIdKey);
		}

		if (!is_numeric($id))
		{
			$item = NodeModel::createFromId(null);
		}
		else
		{
			$item = NodeModel::createFromId((int)$id);
		}

		if (!$requestParentIdKey)
		{
			return $item;
		}

		$parentId = $this->getValueByRequestId($requestParentIdKey);
		if (is_numeric($parentId))
		{
			$item->setTargetNodeId((int)$parentId);
		}

		return $item;
	}

	private function getValueByRequestId(string $requestKey): array|null|string
	{
		return $this->getAction()->getController()->getRequest()->getValues()[$requestKey] ?? null;
	}
}