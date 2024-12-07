<?php

namespace Bitrix\Crm\Integration\HumanResources;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\HumanResources;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Loader;

Loader::requireModule('humanresources');

class DepartmentQueries
{
	use Singleton;

	private HumanResources\Service\Container $hrServiceLocator;

	public function __construct()
	{
		$this->hrServiceLocator = HumanResources\Service\Container::instance();
	}

	public function queryUserIdsByDepartments(array $departmentAccessCodes, bool $excludeHead = false): array
	{
		$departmentAccessCodes = array_map(
			static fn($code) => is_numeric($code) ? 'D' . $code : $code,
			$departmentAccessCodes
		);

		$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;

		$nodes = $this->hrServiceLocator::getNodeRepository()->findAllByAccessCodes($departmentAccessCodes);
		$userIds = [];
		$headIds = [];
		foreach ($nodes as $node)
		{
			$allEmp = $this->hrServiceLocator::getNodeMemberService()->getAllEmployees($node->id, false, false);
			foreach ($allEmp->getIterator() as $emp)
			{
				if ($excludeHead && in_array($headRole, $emp->roles, true))
				{
					$headIds[] = $node->entityId;

					continue;
				}

				$userIds[] = $emp->entityId;
			}

			$userIds = array_diff($userIds, $headIds);
		}

		return array_values(array_unique($userIds));
	}

	/**
	 * Returns all access codes cast to int owned by department as children.
	 * @param int $departmentId
	 * @return int[]
	 */
	public function getSubDepartmentsAccessCodesIds(int $departmentId): array
	{
		$department = $this->hrServiceLocator::getNodeRepository()->getByAccessCode('D' . $departmentId);

		if (empty($department))
		{
			return [];
		}

		$children = $this->hrServiceLocator::getNodeRepository()->getChildOf(
			 $department,
			 HumanResources\Enum\DepthLevel::FULL
		 );

		$result = [];

		/** @var HumanResources\Item\Node $dep */
		foreach ($children->getIterator() as $dep)
		{
			$ac = $dep->accessCode;
			if (!str_starts_with($ac, 'D'))
			{
				continue;
			}

			$depId = (int)substr($ac, 1);

			if ($depId <= 0 || $depId == $departmentId)
			{
				continue;
			}

			$result[] = $depId;
		}

		return $result;
	}
}
