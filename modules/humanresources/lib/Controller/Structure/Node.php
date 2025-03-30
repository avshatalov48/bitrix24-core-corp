<?php

namespace Bitrix\HumanResources\Controller\Structure;

use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Contract\Service\UserService;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\HumanResources\Contract\Repository\RoleRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Contract\Service\NodeService;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;

final class Node extends Controller
{
	private readonly NodeService $nodeService;
	private readonly NodeRepository $nodeRepository;
	private StructureAccessController $accessController;

	public function __construct(Request $request = null)
	{
		$userId = CurrentUser::get()->getId();
		if (!$userId)
		{
			throw new \Bitrix\Main\AccessDeniedException();
		}

		parent::__construct($request);
		$this->nodeService = Container::getNodeService();
		$this->nodeRepository = Container::getNodeRepository(true);
		$this->accessController = StructureAccessController::getInstance($userId);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
		itemType: AccessibleItemType::NODE,
		itemParentIdRequestKey: 'parentId',
	)]
	public function addAction(
		string $name,
		int $parentId,
		Item\Structure $structure,
		NodeEntityType $entityType = NodeEntityType::DEPARTMENT,
		?string $description = null,
	): array
	{
		$node = new Item\Node(
			name: $name,
			type: $entityType,
			structureId: $structure->id,
			parentId: $parentId,
			description: $description,
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

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_DEPARTMENT_DELETE,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
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
			$this->addError(new Error(Loc::getMessage('HUMAN_RESOURCES_NODE_DELETE_FAILED')));
		}

		return [];
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_DEPARTMENT_EDIT,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
		itemParentIdRequestKey: 'parentId',
	)]
	public function updateAction(
		Item\Node $node,
		?string $name = null,
		?int $parentId = null,
		?string $description = null,
		?int $sort = null,
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

		$nodeCollection = $this->nodeService->getNodesByUserId($currentUserId);

		return array_column($nodeCollection->getItemMap(), 'id');
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
	)]
	public function getAction(Item\Node $node): array
	{
		return StructureHelper::getNodeInfo($node);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS
	)]
	public function getByIdsAction(array $nodeIds): array
	{
		$result = [];
		$nodeCollection = $this->nodeRepository->findAllByIds($nodeIds);

		foreach ($nodeCollection as $node)
		{
			$result[$node->id] = StructureHelper::getNodeInfo(node: $node, withHeads: true);
		}

		return $result;
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS
	)]
	public function getHeadsByIdsAction(array $nodeIds): array
	{
		$result = [];
		$nodeCollection = $this->nodeRepository->findAllByIds($nodeIds);

		foreach ($nodeCollection as $node)
		{
			$result[$node->id] = StructureHelper::getNodeHeads($node);
		}

		return $result;
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
	)]
	public function getChildAction(Item\Node $node): array
	{
		$permissionValue = $this->accessController->getUser()->getPermission(
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
		);

		if (!$permissionValue || $permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
		{
			return [];
		}

		$childNodes = $this->nodeRepository->getChildOf($node);
		$result = [];
		foreach ($childNodes as $childNode)
		{
			$result[$childNode->id] = StructureHelper::getNodeInfo($childNode);
		}

		return $result;
	}
}