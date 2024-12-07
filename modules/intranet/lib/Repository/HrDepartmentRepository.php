<?php

namespace Bitrix\Intranet\Repository;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Enum\DepthLevel as NodeDepthLevel;
use Bitrix\Intranet\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Exception\CreationFailedException as HrCreationFailedException;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Intranet\Contract\Repository\DepartmentRepository as DepartmentRepositoryContract;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Enum\DepartmentActiveFilter;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Intranet\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Compatibility\Adapter\StructureBackwardAdapter;

class HrDepartmentRepository implements DepartmentRepositoryContract
{
	/**
	 * @throws LoaderException
	 */
	public function __construct()
	{
		if (!Loader::includeModule('humanresources'))
		{
			throw new \Bitrix\Main\LoaderException('Module "humanresources" not loaded.');
		}
	}

	protected function getCompanyStructure(): ?Structure
	{
		return Container::getStructureRepository()
			->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getById(int $departmentId): ?Department
	{
		$node = Container::getNodeRepository()->getById($departmentId, true);
		if (!$node || $node->type !== NodeEntityType::DEPARTMENT)
		{
			return null;
		}

		return $this->makeDepartmentFromNode($node);
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function getDepartmentHead(int $departmentId): ?User
	{
		$headRole = Container::getRoleRepository()
			->findByXmlId($this->getHeadDepartmentRole())
			?->id
		;

		if ($headRole === null)
		{
			return null;
		}

		$headMembers = Container::getNodeMemberRepository()
			->findAllByRoleIdAndNodeId($headRole, $departmentId);

		$member = $headMembers->getIterator()->current();
		if (!empty($member) && $member->entityType === MemberEntityType::USER && $member->entityId > 0)
		{
			return new User($member->entityId);
		}

		return null;
	}

	/**
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getRootDepartment(): ?Department
	{
		$companyStructureId = $this->getCompanyStructure()->id;
		$node = Container::getNodeRepository()
			->getRootNodeByStructureId($companyStructureId);
		if (!$node)
		{
			return null;
		}

		return $this->makeDepartmentFromNode($node);
	}

	/**
	 * @throws ArgumentException
	 */
	public function getDepartmentsByName(?string $name = null, int $limit = 100): DepartmentCollection
	{
		$companyStructureId = $this->getCompanyStructure()->id;
		$nodes = Container::getNodeRepository()
			->getNodesByName($companyStructureId, $name, $limit);

		return $this->makeDepartmentCollectionFromNodeCollection($nodes);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function findAllByIds(array $ids): DepartmentCollection
	{
		if (empty($ids))
		{
			return new DepartmentCollection();
		}

		$nodes = Container::getNodeRepository()
			->findAllByAccessCodes(array_map(fn($id) => 'D' . $id, $ids));

		return $this->makeDepartmentCollectionFromNodeCollection($nodes);

	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function findAllByXmlId(string $xmlId): DepartmentCollection
	{
		$nodes = Container::getNodeRepository()->findAllByXmlId($xmlId);

		return $this->makeDepartmentCollectionFromNodeCollection($nodes);
	}

	public function getDepartmentByHeadId(
		int $headId,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): DepartmentCollection
	{
		$headRole = Container::getRoleRepository()
			->findByXmlId($this->getHeadDepartmentRole())
			?->id;
		if ($headRole === null)
		{
			return new DepartmentCollection();
		}
		$nodeActiveFilter = $this->convertDepartmentActiveFilter($activeFilter);
		$nodes = Container::getNodeRepository()
			->findAllByUserIdAndRoleId($headId, $headRole, $nodeActiveFilter);

		return $this->makeDepartmentCollectionFromNodeCollection($nodes);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws CreationFailedException
	 * @throws SystemException
	 */
	public function setHead(int $departmentId, int $userId): void
	{
		$member = new NodeMember(
			MemberEntityType::USER,
			$userId,
			$departmentId,
			true,
			role: Container::getRoleHelperService()->getHeadRoleId()
		);

		try
		{
			Container::getNodeMemberRepository()->create($member);
		}
		catch (HrCreationFailedException $e)
		{
			throw new CreationFailedException($e->getErrors());
		}
	}

	public function unsetHead(int $departmentId): void
	{
		$headMember = null;
		$nodeMemberCollection = Container::getNodeMemberRepository()->findAllByNodeId($departmentId);
		$roleId = Container::getRoleHelperService()->getHeadRoleId();
		foreach ($nodeMemberCollection as $nodeMember)
		{
			if (in_array($roleId, $nodeMember->roles))
			{
				$headMember = $nodeMember;
				break;
			}
		}
		if ($headMember instanceof NodeMember)
		{
			Container::getNodeMemberRepository()->remove($headMember);
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws WrongStructureItemException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAllTree(
		Department $rootDepartment = null,
		DepthLevel $depthLevel = DepthLevel::FULL,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): DepartmentCollection
	{
		$nodeRepository = Container::getNodeRepository();
		$departmentCollection = new DepartmentCollection();
		if (!($structure = $this->getCompanyStructure()))
		{
			return $departmentCollection;
		}
		if ($rootDepartment)
		{
			$rootNode = $nodeRepository->getById($rootDepartment->getId());
		}
		else
		{
			$rootNode = $nodeRepository
				->getRootNodeByStructureId($structure->id);
		}
		if (!$rootNode)
		{
			return $departmentCollection;
		}

		$tree = [];
		$parentNodes = [];
		$children = $nodeRepository->getChildOf(
			$rootNode,
			$depthLevel === DepthLevel::FULL ? NodeDepthLevel::FULL : NodeDepthLevel::FIRST
		);

		foreach ($children as $child)
		{
			if (isset($parentNodes[$child->parentId]))
			{
				$parentId = $parentNodes[$child->parentId];
			}
			else
			{
				$parent = $children->getItemById($child->parentId);
				$parentId = $parent !== null
					? $parent->id
					: $nodeRepository->getById($child->parentId)?->id;
			}

			if ($parentId === null)
			{
				continue;
			}

			$parentNodes[$child->parentId] ??= $parentId;

			$tree[$parentId][] = $child;
		}

		foreach ($this->nodeTreeWalker($rootNode, $tree, $activeFilter) as $node)
		{
			$departmentCollection->add($this->makeDepartmentFromNode($node));
		}

		return $departmentCollection;
	}

	private function nodeTreeWalker(
		Node $node,
		array $tree,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): iterable
	{

		if ($activeFilter !== DepartmentActiveFilter::ONLY_ACTIVE || $node->active)
		{
			yield $node;
		}
		$children = $tree[$node->id] ?? [];
		foreach ($children as $child)
		{
			yield from $this->nodeTreeWalker($child, $tree, $activeFilter);
		}
	}

	public function delete(int $departmentId): void
	{
		Container::getNodeRepository()
			->deleteById($departmentId);
	}

	/**
	 * @throws CreationFailedException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function create(Department $department): Department
	{
		$node = $this->makeNodeFromDepartment($department);
		try
		{
			$node = Container::getNodeService()->insertNode($node);
		}
		catch (HrCreationFailedException $e)
		{
			throw new CreationFailedException($e->getErrors());
		}

		return $this->makeDepartmentFromNode($node);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function update(Department $department): Department
	{
		$node = $this->makeNodeFromDepartment($department);
		$node = Container::getNodeService()->updateNode($node);

		return $this->makeDepartmentFromNode($node);
	}

	/**
	 * @throws CreationFailedException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function save(Department $department): Department
	{
		if ($department->getId() > 0)
		{
			return $this->update($department);
		}

		return $this->create($department);
	}

	protected function makeDepartmentFromNode(Node $node): Department
	{
		return new Department(
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


	/**
	 * @throws ArgumentException
	 */
	protected function makeDepartmentCollectionFromNodeCollection(NodeCollection $nodeCollection): DepartmentCollection
	{
		$collection = new DepartmentCollection();
		foreach ($nodeCollection as $node)
		{
			$collection->add($this->makeDepartmentFromNode($node));
		}

		return $collection;
	}

	/**
	 * @return string
	 */
	private function getHeadDepartmentRole(): string
	{
		return NodeMember::DEFAULT_ROLE_XML_ID['HEAD'];
	}

	protected function convertDepartmentActiveFilter(DepartmentActiveFilter $activeFilter): NodeActiveFilter
	{
		switch ($activeFilter)
		{
			case DepartmentActiveFilter::ALL:
				return NodeActiveFilter::ALL;
				break;
			case DepartmentActiveFilter::ONLY_ACTIVE:
				return NodeActiveFilter::ONLY_ACTIVE;
				break;
			case DepartmentActiveFilter::ONLY_GLOBAL_ACTIVE:
				return NodeActiveFilter::ONLY_GLOBAL_ACTIVE;
				break;
		}
				
		throw new ArgumentException("Unknown active filter");
	}

	/**
	 * @param Department $department
	 * @param Structure $structure
	 * @return Node
	 */
	private function makeNodeFromDepartment(Department $department): Node
	{
		$structure = $this->getCompanyStructure();
		if (!$structure?->id)
		{
			throw new SystemException('Company structure record is not found');
		}
		$node = new Node(
			$department->getName(),
			NodeEntityType::DEPARTMENT,
			$structure->id,
			id: $department->getId(),
			parentId: $department->getParentId(),
			active: $department->isActive(),
			globalActive: $department->isGlobalActive(),
			sort: $department->getSort(),
			depth: $department->getDepth(),
			xmlId: $department->getXmlId(),
		);

		return $node;
	}
}