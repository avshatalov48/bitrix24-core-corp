<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UserTable;
use CIBlockSection;

class DepartmentQueries
{
	use Singleton;

	public function queryUserIdsByDepartments(array $departmentIds): array
	{
		$dbResult = UserTable::getList([
			'filter' => [
				'@UF_DEPARTMENT' => $departmentIds,
			],
			'select' => [
				'ID',
			],
		]);

		$userIds = [];
		while ($userFields = $dbResult->fetch())
		{
			$userIds[] = (int)$userFields['ID'];
		}

		return $userIds;
	}

	/**
	 * Fetch department managers ids
	 */
	public function queryCIBlockSectionByIds(array $departmentIds): array
	{
		$departments = CIBlockSection::GetList(
			[],
			[
				'IBLOCK_ID' => Option::get('intranet', 'iblock_structure', 0),
				'ID' => $departmentIds,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			[
				'ID',
				'UF_HEAD', // department manager id
			]
		);

		$result = [];
		while ($departmentFields = $departments->fetch())
		{
			if ($departmentFields['UF_HEAD'])
			{
				$result[] = (int)$departmentFields['UF_HEAD'];
			}
		}

		return $result;
	}

	public function getUserAttributes(int $userId): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		$attributesProvider = $userPermissions->getAttributesProvider();

		return $attributesProvider->getUserAttributes();
	}
}