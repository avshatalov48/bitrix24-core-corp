<?php

namespace Bitrix\HumanResources\Controller\Structure;

use Bitrix\HumanResources\Access\Rule\StructureBaseRule;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
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

	public function __construct(Request $request = null)
	{
		$this->nodeService = Container::getNodeService();
		parent::__construct($request);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
		itemType: AccessibleItemType::NODE,
		itemParentIdRequestKey: 'parentId',
	)]
	public function addAction(
		string $nodeName,
		int $parentId,
		Item\Structure $structure,
		NodeEntityType $entityType = NodeEntityType::DEPARTMENT,
	): array
	{
		$node = new Item\Node(
			name: $nodeName,
			type: $entityType,
			structureId: $structure->id,
			parentId: $parentId,
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
		?string $nodeName = null,
		?int $parentId = null,
	): array
	{
		if ($nodeName)
		{
			$node->name = $nodeName;
		}

		if ($parentId !== null && $parentId >= 0)
		{
			$node->parentId = $parentId;
		}

		try
		{
			$this->nodeService->updateNode($node);
		}
		catch (\Exception $e)
		{
			$this->addError(new Error(Loc::getMessage('HUMAN_RESOURCES_NODE_UPDATE_FAILED')));
		}

		return [
			$node,
		];
	}
}