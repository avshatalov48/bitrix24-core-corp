<?php

namespace Bitrix\IntranetMobile\Provider;

use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Main\Loader;

class DepartmentProvider
{
	private NodeRepository $nodeRepository;
	private NodeMemberService $nodeMemberService;
	private NodeMemberRepository $nodeMemberRepository;

	/**
	 * @param NodeRepository|null $nodeRepository
	 * @param NodeMemberService|null $nodeMemberService
	 * @param NodeMemberRepository|null $nodeMemberRepository
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct(
		?NodeRepository $nodeRepository = null,
		?NodeMemberService $nodeMemberService = null,
		?NodeMemberRepository $nodeMemberRepository = null,
	) {
		if (!Loader::includeModule('humanresources')) {
			throw new \RuntimeException('Module humanresources is not installed.');
		}

		$this->nodeRepository = $nodeRepository ?? \Bitrix\HumanResources\Service\Container::getNodeRepository(true);
		$this->nodeMemberService = $nodeMemberService ?? \Bitrix\HumanResources\Service\Container::getNodeMemberService();
		$this->nodeMemberRepository = $nodeMemberRepository ?? \Bitrix\HumanResources\Service\Container::getNodeMemberRepository();
	}

	/**
	 * @param int $departmentId
	 * @return array
	 */
	public function getParents(int $departmentId): array
	{
		$node = $this->nodeRepository->getById($departmentId);

		if (!$node)
		{
			return [];
		}

		$rootDepartment = \Bitrix\HumanResources\Util\StructureHelper::getRootStructureDepartment();
		$parentDepartments = $this->nodeRepository->getParentOf($node, 2);

		$departments = $parentDepartments->getValues();
		$departmentIds = $parentDepartments->getKeys();

		if ($rootDepartment && !in_array($rootDepartment->id, $departmentIds, true))
		{
			$departments[] = $rootDepartment;
			$departmentIds[] = $rootDepartment->id;
		}

		$heads = $this->getHeadsForDepartments($departmentIds);
		$employeeCounts = $this->getEmployeeCountsForDepartments($departmentIds);

		return [
			'departments' => array_reverse($departments),
			'heads' => $heads,
			'employeeCounts' => $employeeCounts,
		];
	}

	/**
	 * @param array $departmentIds
	 * @return array
	 */
	public function getHeadsForDepartments(array $departmentIds): array
	{
		$heads = [];
		$allHeadIds = [];

		foreach ($departmentIds as $departmentId)
		{
			$headsOfDepartment = $this->nodeMemberService->getDefaultHeadRoleEmployees($departmentId);
			$headIds = array_map(static fn($head) => $head->entityId, $headsOfDepartment->getValues());
			$allHeadIds[] = $headIds;
			$heads[$departmentId] = $headIds;
		}

		$allHeadIds = array_merge(...$allHeadIds);
		$allHeads = UserRepository::getByIds($allHeadIds);
		$allHeadsById = [];
		foreach ($allHeads as $head)
		{
			$allHeadsById[$head->id] = $head;
		}

		foreach ($heads as $departmentId => $headIds)
		{
			$heads[$departmentId] = array_values(array_map(fn($headId) => $allHeadsById[$headId] ?? null, $headIds));
		}

		return $heads;
	}

	/**
	 * @param array $departmentIds
	 * @return array
	 */
	public function getEmployeeCountsForDepartments(array $departmentIds): array
	{
		$employeeCounts = [];

		foreach ($departmentIds as $departmentId)
		{
			$employeeCounts[$departmentId] = $this->nodeMemberRepository->countAllByByNodeId($departmentId);
		}

		return $employeeCounts;
	}

	public function getUserDepartments(int $userId): array
	{
		$nodes = $this->nodeRepository->findAllByUserId($userId);
		$result = [];
		foreach ($nodes as $node)
		{
			$result[$node->id] = $this->getParents($node->id);
		}

		return $result;
	}
}