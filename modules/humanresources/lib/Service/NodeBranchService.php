<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Item\Collection\NodeBranchCollection;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\NodeBranch;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;

class NodeBranchService
{
	private const MAX_BRANCH_SIZE = 3;
	private NodeRepository $nodeRepository;

	public function __construct(
		?NodeRepository $nodeRepository = null
	)
	{
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository(true);
	}

	public function getCollectionByUserIdAndEntityType(
		int $userId,
		int $depth = self::MAX_BRANCH_SIZE,
		NodeEntityType $entityType = NodeEntityType::DEPARTMENT,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): NodeBranchCollection
	{
		$branchRootTitle = StructureHelper::getRootStructureDepartment()?->name;

		$nodeCollection = $this->nodeRepository->findAllByUserId($userId, $activeFilter);
		$nodeCollection = $nodeCollection->filterByEntityTypes($entityType);

		$nodeBranchCollection = new NodeBranchCollection();
		foreach ($nodeCollection as $node)
		{
			$parentCollection = $this->nodeRepository->getParentOf($node, $depth);

			$nodeCollection = new NodeCollection(...[$node, ...$parentCollection]);

			$nodeBranchCollection->add(
				new NodeBranch(
					nodeCollection: $nodeCollection->orderMapByInclude(),
					rootTitle: $branchRootTitle,
					fromNodeId: $node->id,
				)
			);
		}

		return $nodeBranchCollection;
	}
}