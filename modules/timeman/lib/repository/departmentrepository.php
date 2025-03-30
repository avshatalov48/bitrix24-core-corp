<?php
namespace Bitrix\Timeman\Repository;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Timeman\Helper\ConfigurationHelper;
use CIBlockSection;

class DepartmentRepository
{
	static $depTreeFlat = null;

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
		if (Loader::includeModule('humanresources'))
		{
			$departmentNodes = Container::getNodeService()->getNodesByUserId($userId);

			$departmentIds = [];
			foreach ($departmentNodes->getIterator() as $departmentNode)
			{
				$departmentIds[] = DepartmentBackwardAccessCode::extractIdFromCode($departmentNode->accessCode);
			}

			return $departmentIds;
		}

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
		$departments = (array) \CIntranetUtils::getSubDepartments($depId);
		$departments = array_filter($departments, 'is_numeric');

		$allDepartments = $departments;

		foreach ($departments as $childId)
		{
			$childDepartments = $this->getAllChildDepartmentsIds($childId);
			$allDepartments = array_merge($allDepartments, $childDepartments);
		}

		$allDepartments = array_unique($allDepartments);

		return array_map('intval', $allDepartments);
	}

	public function getDirectParentIdsByDepartmentId($departmentId)
	{
		$this->getDepartmentTreeFlat();
		if (!is_array(self::$depTreeFlat))
		{
			return [];
		}

		$res = [];
		foreach (self::$depTreeFlat as $parentDepId => $depIds)
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

	private function getDepartmentTreeFlat(): void
	{
		if (self::$depTreeFlat === null)
		{
			self::$depTreeFlat = \CIntranetUtils::getDeparmentsTree(null);
		}
	}

	public function getAllParentDepartmentsIds($depId)
	{
		$this->getDepartmentTreeFlat();
		if (!is_array(self::$depTreeFlat))
		{
			return [];
		}

		$res = [];
		foreach (self::$depTreeFlat as $parentDepId => $depIds)
		{
			foreach ($depIds as $nestedDepId)
			{
				if ($nestedDepId == $depId)
				{
					$res = array_merge($this->getAllParentDepartmentsIds($parentDepId), [$parentDepId]);

					break;
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
		if (!is_string($depId) && !is_int($depId))
		{
			return [];
		}

		$structure = \CIntranetUtils::getStructure();
		if (
			!is_array($structure)
			|| !isset($structure['DATA'])
			|| !is_array($structure['DATA'])
			|| !isset($structure['DATA'][$depId]['EMPLOYEES'])
		)
		{
			return [];
		}

		$employeesData = (array) $structure['DATA'][$depId]['EMPLOYEES'];
		$filteredEmployeesData = array_filter($employeesData, 'is_numeric');

		$employees = array_map('intval', $filteredEmployeesData);

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
