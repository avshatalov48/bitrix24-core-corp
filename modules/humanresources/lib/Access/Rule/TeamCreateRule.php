<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionHelper;
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
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;

class TeamCreateRule extends AbstractRule
{
	public const PERMISSION_ID_KEY = 'PERMISSION_ID';

	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!($item instanceof NodeModel))
		{
			return false;
		}

		if (!$item->getTargetId())
		{
			return false;
		}

		$nodeRepository = Container::getNodeRepository();
		$targetNode = $nodeRepository->getById($item->getTargetId());
		if (!$targetNode || !$targetNode->id)
		{
			return false;
		}

		$permissionId = (string)$params[self::PERMISSION_ID_KEY];
		if (!PermissionDictionary::isTeamDependentVariablesPermission($permissionId))
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
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

		$teamItem =
			(new NodeDataBuilder())->addFilter(
				new NodeFilter(
					idFilter: new IdFilter(new IntegerCollection($targetNode->id)),
					entityTypeFilter: new NodeTypeFilter(
						new NodeEntityTypeCollection(NodeEntityType::TEAM)
					),
					active: true,
					accessFilter: new NodeAccessFilter(StructureAction::CreateAction, $userId),
				),
			)
			->setLimit(1)
			->get()
		;

		return !is_null($teamItem);
	}
}