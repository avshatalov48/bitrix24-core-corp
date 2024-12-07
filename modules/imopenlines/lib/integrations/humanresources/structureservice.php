<?php

namespace Bitrix\ImOpenLines\Integrations\HumanResources;

use Bitrix\HumanResources\Contract\Repository\StructureRepository;
use Bitrix\HumanResources\Contract\Service\UserService;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;

class StructureService
{
	protected static $instance;
	protected NodeRepository $nodeRepository;
	protected NodeMemberService $nodeMemberService;
	protected NodeMemberRepository $nodeMemberRepository;

	private array $structureDepartments = [];
	private array $parentNodes = [];

	protected function __construct()
	{
		if ($this->isCompanyStructureConverted())
		{
			$this->nodeRepository = Container::getNodeRepository();
			$this->nodeMemberService = Container::getNodeMemberService();
			$this->nodeMemberRepository = Container::getNodeMemberRepository();
		}
	}

	/**
	 * @return self
	 */
	public static function getInstance(): self
	{
		self::$instance ??= new static();

		return self::$instance;
	}

	public function isCompanyStructureConverted(): bool
	{
		return
			Loader::includeModule('humanresources')
			&& Storage::instance()->isCompanyStructureConverted();
	}

	/**
	 * @return array<array{id: int, name: string, depthLevel: int, headUserId: int, parent: int}>
	 */
	public function getStructure(): array
	{
		if (empty($this->structureDepartments))
		{
			if ($this->isCompanyStructureConverted())
			{
				$structure = Container::getStructureRepository()->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);

				$nodes = $this->nodeRepository->getAllByStructureId($structure->id);
				foreach ($nodes as $node)
				{
					$department = $this->formatNode($node);
					$this->structureDepartments[$department['id']] = $department;
				}
			}
			//todo: Remove this block
			elseif (
				Loader::includeModule('iblock')
				&& Loader::includeModule('intranet')
			)
			{
				$departments = \CIntranetUtils::GetStructure();
				foreach ($departments['DATA'] as $row)
				{
					$this->structureDepartments[$row['ID']] = [
						'id' => (int)$row['ID'],
						'name' => (string)$row['NAME'],
						'depthLevel' => (int)$row['DEPTH_LEVEL'],
						'headUserId' => (int)$row['UF_HEAD'],
						'parent' => (int)$row['IBLOCK_SECTION_ID'],
					];
				}
			}
		}

		return $this->structureDepartments;
	}

	/**
	 * @return void
	 */
	public function resetCache(): void
	{
		$this->structureDepartments = [];
		$this->parentNodes = [];
	}

	/**
	 * @param Node $node
	 * @return array{id: int, name: string, depthLevel: int, headUserId: int, parent: int}
	 */
	protected function formatNode(Node $node): array
	{
		if (isset($this->parentNodes[$node->parentId]))
		{
			$parentId = $this->parentNodes[$node->parentId];
		}
		else
		{
			$parent = $this->nodeRepository->getById($node->parentId);
			$parentId = DepartmentBackwardAccessCode::extractIdFromCode($parent?->accessCode);
		}
		$this->parentNodes[$node->parentId] ??= $parentId;

		$headMembers = $this->nodeMemberService->getDefaultHeadRoleEmployees($node->id);
		$nodeId = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);

		return [
			'id' => $nodeId,
			'name' => $node->name,
			'depthLevel' => $node->depth,
			'headUserId' => $headMembers->getIterator()->current()?->entityId ?? 0,
			'parent' => $parentId,
		];
	}

	/**
	 * @param int $departmentId
	 * @param bool $recursion
	 * @param bool $includeCurrentDepartment
	 * @return array<array{id: int, name: string, depthLevel: int, headUserId: int, parent: int}>
	 */
	public function getChildDepartments(int $departmentId, bool $recursion = false, bool $includeCurrentDepartment = false): array
	{
		$result = [];
		if ($this->isCompanyStructureConverted())
		{
			$startNode = $this->nodeRepository->getByAccessCode(DepartmentBackwardAccessCode::makeById($departmentId));
			if ($startNode)
			{
				$children = $this->nodeRepository->getChildOf($startNode, $recursion ? DepthLevel::FULL : DepthLevel::FIRST);
				foreach ($children as $node)
				{
					if (!$includeCurrentDepartment && $startNode->id == $node->id)
					{
						continue;
					}
					$department = $this->formatNode($node);
					$result[$department['id']] = $department;
				}

				if ($includeCurrentDepartment && !isset($result[$departmentId]))
				{
					$department = $this->formatNode($startNode);
					$result[$department['id']] = $department;
				}
			}
		}
		//todo: Remove this block
		else
		{
			foreach ($this->getStructure() as $department)
			{
				if ($department['parent'] == $departmentId)
				{
					$result[$department['id']] = $department;
				}
			}
			if ($recursion && !empty($result))
			{
				foreach ($result as $department)
				{
					$subordinateDepartments = $this->getChildDepartments($department['id'], true, false);
					if (!empty($subordinateDepartments))
					{
						foreach ($subordinateDepartments as $id => $subordinateDepartment)
						{
							$result[$id] = $subordinateDepartment;
						}
					}
				}
			}
			if ($includeCurrentDepartment)
			{
				$result[$departmentId] = $this->getStructure()[$departmentId];
			}
		}

		return $result;
	}

	/**
	 * @param int $departmentId
	 * @param bool $recursion
	 * @param bool $includeCurrentDepartment
	 * @return array<array{id: int, name: string, depthLevel: int, headUserId: int, parent: int}>
	 */
	public function getParentDepartments(int $departmentId, bool $recursion = false, bool $includeCurrentDepartment = false): array
	{
		$result = [];
		if ($this->isCompanyStructureConverted())
		{
			$startNode = $this->nodeRepository->getByAccessCode(DepartmentBackwardAccessCode::makeById($departmentId));
			if ($startNode)
			{
				$ancestors = $this->nodeRepository->getParentOf($startNode, DepthLevel::FULL);

				foreach ($ancestors as $node)
				{
					if (!$recursion && $startNode->parentId != $node->id)
					{
						continue;
					}
					if (!$includeCurrentDepartment && $startNode->id == $node->id)
					{
						continue;
					}
					$department = $this->formatNode($node);
					$result[$department['id']] = $department;
				}

				if ($includeCurrentDepartment && !isset($result[$departmentId]))
				{
					$department = $this->formatNode($startNode);
					$result[$department['id']] = $department;
				}
			}
		}
		//todo: Remove this block
		else
		{
			$structureDepartments = $this->getStructure();
			$currentDepartment = $structureDepartments[$departmentId];

			foreach ($structureDepartments as $department)
			{
				if ($department['id'] == $currentDepartment['parent'])
				{
					$result[$department['id']] = $department;
				}
			}

			if ($recursion && !empty($result))
			{
				foreach ($result as $department)
				{
					$parentDepartments = $this->getParentDepartments($department['id'], true, false);
					if (!empty($parentDepartments))
					{
						foreach ($parentDepartments as $id => $parentDepartment)
						{
							$result[$id] = $parentDepartment;
						}
					}
				}
			}
			if ($includeCurrentDepartment)
			{
				$result[$departmentId] = $currentDepartment;
			}
		}

		return $result;
	}

	/**
	 * @param int $departmentId
	 * @param bool $excludeHead
	 * @return int[]
	 */
	public function getDepartmentUserIds(int $departmentId, bool $excludeHead = true): array
	{
		$employees = [];
		if ($this->isCompanyStructureConverted())
		{
			$startNode = $this->nodeRepository->getByAccessCode(DepartmentBackwardAccessCode::makeById($departmentId));
			if ($startNode)
			{
				$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;
				$nodes = $this->nodeMemberService->getAllEmployees($startNode->id, true);
				$heads = [];
				foreach ($nodes as $node)
				{
					if ($excludeHead && in_array($headRole, $node->roles))
					{
						$heads[] = $node->entityId;
						continue;
					}
					$employees[] = $node->entityId;
				}

				$employees = array_diff($employees, $heads);
			}
		}
		//todo: Remove this block
		else
		{
			$members = $this->getUsersDepartment($departmentId, ['ID'], $excludeHead);
			while ($member = $members->fetch())
			{
				$employees[] = $member['ID'];
			}
		}

		return $employees;
	}

	/**
	 * todo: Remove this method
	 * @deprecated
	 * @param int $departmentId
	 * @param string[] $select
	 * @param bool $excludeHead
	 */
	private function getUsersDepartment($departmentId, array $select = ['ID'], bool $excludeHead = true)
	{
		$query = UserTable::query();

		$query->setSelect($select);

		$departments = $this->getChildDepartments($departmentId, true, true);
		$subDepartments = [];
		$excludeUsers = [];
		foreach ($departments as $department)
		{
			$subDepartments[] = $department['id'];
			if ($excludeHead && $department['headUserId'] > 0)
			{
				$excludeUsers[] = $department['headUserId'];
			}
		}
		$filter = [
			'=UF_DEPARTMENT' => $subDepartments,
			'=ACTIVE' => 'Y',
			'!=BLOCKED' => 'Y'
		];
		if ($excludeHead && !empty($excludeUsers))
		{
			$filter['!=ID'] = $excludeUsers;
		}
		$query->addFilter(null, $filter);

		$query->setCacheTtl(3600);

		$query->setOrder(['ID' => 'asc']);

		return $query->exec();
	}

	/**
	 * @param int $departmentId
	 * @return int
	 */
	public function getDepartmentHeadId(int $departmentId): int
	{
		if ($this->isCompanyStructureConverted())
		{
			$startNode = $this->nodeRepository->getByAccessCode(DepartmentBackwardAccessCode::makeById($departmentId));
			$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;

			$headMembers = $this->nodeMemberRepository->findAllByRoleIdAndNodeId($headRole, $startNode->id);

			$headId = $headMembers->getIterator()->current()?->entityId ?? 0;
		}
		//todo: Remove this block
		else
		{
			$headId = \CIntranetUtils::GetDepartmentManagerID($departmentId);
		}

		return $headId;
	}
}