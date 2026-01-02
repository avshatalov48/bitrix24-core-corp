<?php

namespace Bitrix\HumanResources\Controller\Structure;

use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Internals\Attribute;
use Bitrix\HumanResources\Command\Structure\Node\NodeOrderCommand;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Service\NodeService;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Exception\CommandException;
use Bitrix\HumanResources\Exception\CommandValidateException;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Internals\Attribute\Access\LogicOr;
use Bitrix\HumanResources\Internals\Attribute\StructureActionAccess;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Repository\Access\PermissionRestrictedNodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\HumanResources\Type\IntegerCollection;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;

final class Node extends Controller
{
	private const ERROR_TEAMS_DISABLED = 'ERROR_TEAMS_DISABLED';
	private readonly NodeService $nodeService;
	private readonly NodeRepository $nodeRepository;

	public function __construct(Request $request = null)
	{
		$this->nodeService = Container::getNodeService();
		$this->nodeRepository = new PermissionRestrictedNodeRepository();

		parent::__construct($request);
	}

	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
			itemType: AccessibleItemType::NODE,
			itemParentIdRequestKey: 'parentId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_CREATE,
			itemType: AccessibleItemType::NODE,
			itemParentIdRequestKey: 'parentId',
		),
	)]
	public function addAction(
		string $name,
		int $parentId,
		Item\Structure $structure,
		NodeEntityType $entityType = NodeEntityType::DEPARTMENT,
		?string $description = null,
		?string $colorName = null,
	): array
	{
		if (!$parentId)
		{
			$this->addError(new Error('Cannot create a node without specifying the parentId'));

			return [];
		}

		// temp check for adding a team
		if ($entityType === NodeEntityType::TEAM && !Feature::instance()->isCrossFunctionalTeamsAvailable())
		{
			$this->addError(new Error(
					Loc::getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_TEAMS_DISABLED_ERROR_MSGVER_1'),
					self::ERROR_TEAMS_DISABLED,
				)
			);

			return [];
		}

		$node = new Item\Node(
			name: $name,
			type: $entityType,
			structureId: $structure->id,
			parentId: $parentId,
			description: $description,
			colorName: $colorName,
		);

		try
		{
			$this->nodeService->insertNode($node);

			return [
				$node,
			];
		}
		catch (CreationFailedException $e)
		{
			$this->addErrors($e->getErrors()->toArray());
		}
		catch (ArgumentException|SystemException $e)
		{
		}

		return [];
	}

	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_DELETE,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_DELETE,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function deleteAction(Item\Node $node): array
	{
		try
		{
			$this->nodeService->removeNode($node);
		}
		catch (DeleteFailedException|WrongStructureItemException $e)
		{
			$this->addErrors($e->getErrors()->toArray());
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error('Failed to delete node'));
		}

		return [];
	}

	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_EDIT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
			itemParentIdRequestKey: 'parentId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_EDIT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
			itemParentIdRequestKey: 'parentId',
		),
	)]
	public function updateAction(
		Item\Node $node,
		?string $name = null,
		?int $parentId = null,
		?string $description = null,
		?int $sort = null,
		?string $colorName = null,
	): array
	{
		if ($name)
		{
			$node->name = $name;
		}

		if ($parentId !== null && $parentId >= 0)
		{
			$node->parentId = $parentId;
		}

		if ($description !== null)
		{
			$node->description = $description;
		}

		if ($sort)
		{
			$node->sort = $sort;
		}

		if ($colorName)
		{
			$node->colorName = $colorName;
		}

		try
		{
			$this->nodeService->updateNode($node);
		}
		catch (\Exception $e)
		{
			$this->addError(new Error($e->getMessage()));
		}

		return [
			$node,
		];
	}

	public function currentAction(): array
	{
		$currentUserId = CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return [];
		}

		$this->nodeRepository->setSelectableNodeEntityTypes([
			NodeEntityType::DEPARTMENT,
			NodeEntityType::TEAM,
		]);
		$nodeCollection = $this->nodeRepository->findAllByUserId($currentUserId);
		$this->nodeRepository->setSelectableNodeEntityTypes([
			NodeEntityType::DEPARTMENT,
		]);

		return array_column($nodeCollection->getItemMap(), 'id');
	}

	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_VIEW,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function getAction(Item\Node $node): array
	{
		return StructureHelper::getNodeInfo($node);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS
	)]
	public function getByIdsAction(array $nodeIds, Item\Structure $structure): array
	{
		$result = [];
		$nodeIds = array_filter(array_map('intval', $nodeIds));
		if (empty($nodeIds))
		{
			return $result;
		}

		$nodeCollection =
			(new NodeDataBuilder())
				->addFilter(
					new NodeFilter(
						idFilter: new IdFilter(new IntegerCollection(...$nodeIds)),
						entityTypeFilter: NodeTypeFilter::fromNodeTypes([NodeEntityType::DEPARTMENT, NodeEntityType::TEAM]),
						structureId: $structure->id,
						active: true,
						accessFilter: new NodeAccessFilter(StructureAction::ViewAction),
					),
				)
				->getAll()
		;

		foreach ($nodeCollection as $node)
		{
			$result[$node->id] = StructureHelper::getNodeInfo(node: $node, withHeads: true);
		}

		return $result;
	}

	/**
	 * @param array<int> $nodeIds
	 * @param Item\Structure $structure
	 *
	 * * @return array<int, array{
	 *     id: int,
	 *     name: string,
	 *     avatar: string,
	 *     url: string,
	 *     workPosition: ?string,
	 *     gender: string,
	 *     isInvited: bool,
	 *     role: string,
	 * }>
	 */
	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS
	)]
	public function getHeadsByIdsAction(array $nodeIds, Item\Structure $structure): array
	{
		$result = [];
		$nodeIds = array_filter(array_map('intval', $nodeIds));
		if (empty($nodeIds))
		{
			return $result;
		}

		$nodeCollection =
			(new NodeDataBuilder())
				->addFilter(
					new NodeFilter(
						idFilter: new IdFilter(new IntegerCollection(...$nodeIds)),
						entityTypeFilter: NodeTypeFilter::fromNodeTypes([NodeEntityType::DEPARTMENT, NodeEntityType::TEAM]),
						structureId: $structure->id,
						active: true,
						accessFilter: new NodeAccessFilter(StructureAction::ViewAction),
					)
				)
				->getAll()
		;

		foreach ($nodeCollection as $node)
		{
			$result[$node->id] = StructureHelper::getNodeHeads($node);
		}

		return $result;
	}

	#[LogicOr(
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_EDIT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
			itemParentIdRequestKey: 'parentId',
		),
		new StructureActionAccess(
			permission: StructureActionDictionary::ACTION_TEAM_EDIT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
			itemParentIdRequestKey: 'parentId',
		),
	)]
	public function changeOrderAction(Item\Node $node, int $direction, int $count): array
	{
		try
		{
			$orderResult = (new NodeOrderCommand(
				$node,
				$direction,
				$count,
			))->run();

			if (!$orderResult->isSuccess())
			{
				$this->addErrors($orderResult->getErrors());

				return [];
			}
		}
		catch (CommandException|CommandValidateException)
		{
			$this->addError((new Error('Failed to change order')));
		}

		return [];
	}
}