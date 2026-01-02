<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionHelper;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\IntegerCollection;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\Main\Access\Rule\AbstractRule;

final class TeamEditRule extends AbstractRule
{
	public const PERMISSION_ID_KEY = 'PERMISSION_ID';

	public function execute(\Bitrix\Main\Access\AccessibleItem $item = null, $params = null): bool
	{
		if (
			!($item instanceof NodeModel)
			|| !$item->getId()
			|| !$item->getNode()
		)
		{
			return false;
		}

		$node = $item->getNode();
		if ($node->type !== NodeEntityType::TEAM)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		$permissionId = (string)$params[self::PERMISSION_ID_KEY];
		if (!PermissionDictionary::isTeamDependentVariablesPermission($permissionId))
		{
			return false;
		}

		$userId = $this->user->getUserId();
		$permissionCollection = PermissionHelper::getPermissionValue($permissionId, $userId);
		$teamPermissionMapper = TeamPermissionMapper::createFromCollection($permissionCollection);
		if (
			$teamPermissionMapper->getTeamPermissionValue() === PermissionVariablesDictionary::VARIABLE_NONE
			&& $teamPermissionMapper->getDepartmentPermissionValue() === PermissionVariablesDictionary::VARIABLE_NONE
		)
		{
			return false;
		}

		if ($teamPermissionMapper->getTeamPermissionValue() === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return true;
		}

		$itemId = $item->getId();
		$parentId = $item->getParentId();
		$targetId = $item->getTargetId();

		$nodeIds = [$itemId];
		$entityTypes = [NodeEntityType::TEAM];
		$necessaryCount = 1;

		/**
		 * if a team's parent changes,
		 * then necessary to check access to the node, the current parent and the target
		 */
		if ($parentId && $targetId && $parentId !== $targetId)
		{
			array_push($nodeIds, $parentId, $targetId);
			$entityTypes[] = NodeEntityType::DEPARTMENT;
			$necessaryCount = 3;
		}

		$teamAccessItemCollection =
			(new NodeDataBuilder())->addFilter(
				new NodeFilter(
					idFilter: IdFilter::fromIds($nodeIds),
					entityTypeFilter: NodeTypeFilter::fromNodeTypes($entityTypes),
					active: true,
					accessFilter: new NodeAccessFilter(StructureAction::UpdateAction, $userId),
				),
			)
			->getAll()
		;

		return $teamAccessItemCollection->count() === $necessaryCount;
	}
}
