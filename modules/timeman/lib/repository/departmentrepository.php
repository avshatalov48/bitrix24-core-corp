<?php
namespace Bitrix\Timeman\Repository;

use Bitrix\Timeman\Helper\ConfigurationHelper;
use CIBlockSection;

class DepartmentRepository
{
	public function findDepartmentsChain($depId)
	{
		if (!\Bitrix\Main\Loader::includeModule('iblock'))
		{
			return [];
		};

		$parents = [];
		$sectionChain = CIBlockSection::getNavChain(ConfigurationHelper::getInstance()->getIblockStructureId(), $depId);
		while ($parent = $sectionChain->fetch())
		{
			$parents[] = [
				'ID' => $parent['ID'],
				'NAME' => $parent['NAME'],
				'DEPTH_LEVEL' => $parent['DEPTH_LEVEL'],
			];
		}
		\Bitrix\Main\Type\Collection::sortByColumn(
			$parents,
			['DEPTH_LEVEL' => SORT_ASC]
		);
		return array_values($parents);
	}

	public function getDirectParentIdsByUserId($userId)
	{
		$userDepIds = [];
		$userId = (int)$userId;
		$structure = \CIntranetUtils::getStructure();
		foreach ($structure['DATA'] as $depId => $data)
		{
			if (in_array($userId, array_map('intval', (array)$data['EMPLOYEES']), true))
			{
				$userDepIds[(int)$depId] = true;
			}
		}
		return array_unique(array_keys($userDepIds));
	}

	public function getSubDepartmentsIds($depId)
	{
		return (array)\CIntranetUtils::getSubDepartments($depId);
	}

	public function getAllChildDepartmentsIds($depId)
	{
		$departments = (array)\CIntranetUtils::getSubDepartments($depId);
		foreach ($departments as $childId)
		{
			$departments = array_merge($departments, $this->getAllChildDepartmentsIds($childId));
		}
		return array_map('intval', $departments);
	}

	public function getDirectParentIdsByDepartmentId($departmentId)
	{
		$depTreeFlat = $this->getDepartmentTreeFlat();

		$res = [];
		foreach ($depTreeFlat as $parentDepId => $depIds)
		{
			foreach ($depIds as $nestedDepId)
			{
				if ((int)$nestedDepId === (int)$departmentId)
				{
					$res[] = (int)$parentDepId;
				}
			}
		}
		return array_unique($res);
	}

	private function getDepartmentTreeFlat()
	{
		static $depTreeFlat;
		if (!$depTreeFlat)
		{
			$depTreeFlat = \CIntranetUtils::getDeparmentsTree(null, false);
		}
		return $depTreeFlat;
	}

	public function getAllParentDepartmentsIds($depId)
	{
		$depTreeFlat = $this->getDepartmentTreeFlat();

		$res = [];
		foreach ($depTreeFlat as $parentDepId => $depIds)
		{
			foreach ($depIds as $nestedDepId)
			{
				if ($nestedDepId == $depId)
				{
					$res = array_merge($this->getAllParentDepartmentsIds($parentDepId), [$parentDepId]);
				}
			}
		}
		return $res;
	}

	public function getAllUserDepartmentIds($userId)
	{
		$userDepartmentsIds = $this->getDirectParentIdsByUserId($userId);
		foreach ($userDepartmentsIds as $userDep)
		{
			$userDepartmentsIds = array_merge($this->getAllParentDepartmentsIds($userDep), $userDepartmentsIds);
		}
		return array_unique($userDepartmentsIds);
	}

	public function getDepartmentManagerId($depId)
	{
		return (int)\CIntranetUtils::getDepartmentManagerId($depId);
	}

	public function getUsersOfDepartment($depId)
	{
		$structure = \CIntranetUtils::getStructure();
		$employees = array_map('intval', (array)$structure['DATA'][$depId]['EMPLOYEES']);
		return empty($employees) ? [] : $employees;
	}

	public function getBaseDepartmentId()
	{
		if (!empty(\CIntranetUtils::GetStructure()['TREE'][0]))
		{
			$depId = reset(\CIntranetUtils::GetStructure()['TREE'][0]);
			if ($depId > 0)
			{
				return (int)$depId;
			}
		}
		return null;
	}

	public function buildUserDepartmentsPriorityTrees($userId)
	{
		$result = [];
		$userDepartmentsIds = $this->getDirectParentIdsByUserId($userId); // might be more than one
		foreach ($userDepartmentsIds as $departmentId)
		{
			$parentDepartmentsIds = $this->getAllParentDepartmentsIds($departmentId);

			$result[] = array_merge(
				['U' . $userId],
				['DR' . $departmentId],
				array_map(function ($id) {
					return 'DR' . $id;
				}, array_reverse($parentDepartmentsIds))
			);
		}
		return $result;
	}

	/** always one chain, department can not have multiple parents
	 * @param $depId
	 * @return array
	 */
	public function buildDepartmentsPriorityTree($depId)
	{
		$allParentDepartmentIds = $this->getAllParentDepartmentsIds($depId);

		return array_merge(
			['DR' . $depId],
			array_map(function ($id) {
				return 'DR' . $id;
			}, array_reverse($allParentDepartmentIds))
		);
	}

	public function getDepartmentsTree()
	{
		return (array)\CIntranetUtils::getStructure()['TREE'];
	}

	public function getAllData()
	{
		return (array)\CIntranetUtils::getStructure()['DATA'];
	}
}
