<?php

namespace Bitrix\Intranet\Integration\HumanResources;

use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Collection\NodeBranchCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Main;

final class Department
{
	private bool $available;

	public function __construct()
	{
		$this->available = Main\Loader::includeModule('humanresources');
	}

	public function getByIds(array $ids): DepartmentCollection
	{
		$departments = new DepartmentCollection();

		if ($this->available)
		{
			$this->fillFromHrRepo(
				$departments,
				HumanResources\Service\Container::getNodeRepository()
					->findAllByIds($ids)
			);
		}

		return $departments;
	}

	public function getRootDepartment(): \Bitrix\Intranet\Entity\Department
	{
		$companyStructureId = Container::getStructureRepository()->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID)->id;
		$node = Container::getNodeRepository()->getRootNodeByStructureId($companyStructureId);

		return $this->createDepartmentFromNode($node);
	}

	private function createDepartmentFromNode(Node $node): \Bitrix\Intranet\Entity\Department
	{
		return new \Bitrix\Intranet\Entity\Department(
			name: $node->name,
			id: $node->id,
			parentId: $node->parentId,
			createdBy: $node->createdBy,
			createdAt: $node->createdAt,
			updatedAt: $node->updatedAt,
			xmlId: $node->xmlId,
			sort: $node->sort,
			isActive: $node->active,
			isGlobalActive: $node->globalActive,
			depth: $node->depth,
			accessCode: $node->accessCode,
		);
	}

	private function fillFromHrRepo(DepartmentCollection $collection, NodeCollection $nodeCollection): void
	{
		foreach ($nodeCollection as $node)
		{
			$collection->add($this->createDepartmentFromNode($node));
		}
	}

	public function getUserDepartmentBranchCollection(int $userId, int $depth): ?NodeBranchCollection
	{
		if (!$this->available)
		{
			return null;
		}

		// Can be removed later
		if (!method_exists(Container::class, 'getNodeBranchService'))
		{
			return null;
		}

		return Container::getNodeBranchService()->getCollectionByUserIdAndEntityType($userId, $depth);
	}

	/**
	 * @param array $departmentIds
	 * @return array<int, int>
	 */
	public function getEmployeeCountByDepartmentIds(array $departmentIds): array
	{
		if (!$this->available || empty($departmentIds))
		{
			return [];
		}

		$structure = StructureHelper::getDefaultStructure();
		if (!$structure)
		{
			return [];
		}

		$allEmployeeCountByNodeId = Container::getNodeMemberRepository()->countAllByStructureAndGroupByNode($structure);

		return array_intersect_key($allEmployeeCountByNodeId, array_flip($departmentIds));
	}

	/**
	 * @return array<int, array{
	 * 		id: int | null,
	 * 		name: string,
	 * 		avatar: string,
	 * 		workPosition: string,
	 * }>
	 */
	public function getHeadDictionaryByNodeCollection(NodeCollection $departmentCollection): array
	{
		if (!$this->available)
		{
			return [];
		}

		$result = [];
		foreach ($departmentCollection as $department) {
			$nodeInfo = \Bitrix\HumanResources\Util\StructureHelper::getNodeInfo($department, withHeads: true);

			$result[$department->id] = [];
			foreach (($nodeInfo['heads'] ?? []) as $head) {
				if (($head['role'] ?? null) !== 'MEMBER_HEAD')
				{
					continue;
				}

				$result[$department->id][] = [
					'id' => $head['id'] ?? null,
					'name' => $head['name'] ?? '',
					'avatar' => $head['avatar'] ?? '',
					'workPosition' => $head['workPosition'] ?? '',
				];
			}
		}

		return $result;
	}
}
