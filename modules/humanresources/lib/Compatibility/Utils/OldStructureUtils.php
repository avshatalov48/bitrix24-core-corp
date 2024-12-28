<?php

namespace Bitrix\HumanResources\Compatibility\Utils;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

final class OldStructureUtils
{
	/**
	 * @param $params array{NAME?: string, SORT?: int, PARENT?: int, UF_HEAD?: int, ACTIVE?: int}
	 * @return false|int|mixed
	 * @throws Main\LoaderException
	 * @throws UpdateFailedException
	 */
	public static function addDepartment(array $params): mixed
	{
		if (!Loader::includeModule('iblock'))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Module iblock is not installed'),
			);
		}

		$params = array_change_key_case($params, CASE_UPPER);
		$department = [
			'IBLOCK_ID' => self::getOldDepartmentIblockId(),
			'NAME' => $params['NAME'] ?? null,
			'ACTIVE' => $params['ACTIVE'] ?? 'Y',
			'SORT' => $params['SORT'] ?? null,
			'IBLOCK_SECTION_ID' => $params['PARENT'] ?? null,
			'UF_HEAD' => $params['UF_HEAD'] ?? null,
		];

		$iBlockSection = new \CIBlockSection();
		$section = $iBlockSection->Add($department);
		if($section > 0)
		{
			return $section;
		}

		throw (new UpdateFailedException())->addError(
			new Main\Error($iBlockSection->LAST_ERROR),
		);
	}

	/**
	 * @param array $params
	 *
	 * @return void
	 * @throws \Bitrix\HumanResources\Exception\UpdateFailedException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function updateDepartment(array $params): void
	{
		if (!Loader::includeModule('iblock'))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Module iblock is not installed'),
			);
		}

		$params = array_change_key_case($params, CASE_UPPER);
		$department = self::getOldDepartmentById((int)$params['ID']);
		if (!$department)
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Department not found'),
			);
		}

		$updatedFields = [];
		if (array_key_exists('NAME', $params))
		{
			$updatedFields['NAME'] = $params['NAME'];
		}

		if (array_key_exists('SORT', $params))
		{
			$updatedFields['SORT'] = $params['SORT'];
		}

		if (array_key_exists('PARENT', $params))
		{
			$updatedFields['IBLOCK_SECTION_ID'] = $params['PARENT'];
		}

		if (array_key_exists('UF_HEAD', $params))
		{
			$updatedFields['UF_HEAD'] = $params['UF_HEAD'];
		}

		if (array_key_exists('ACTIVE', $params))
		{
			$updatedFields['ACTIVE'] = $params['ACTIVE'];
		}

		if (empty($updatedFields))
		{
			return;
		}

		$iBlockSection = new \CIBlockSection();

		if(!$iBlockSection->Update($department['ID'], $updatedFields))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error($iBlockSection->LAST_ERROR),
			);
		}
	}

	/**
	 * @param array $params
	 *
	 * @return void
	 * @throws \Bitrix\HumanResources\Exception\UpdateFailedException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function deleteDepartment(array $params): void
	{
		if (!Loader::includeModule('iblock'))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Module iblock is not installed'),
			);
		}

		$params = array_change_key_case($params, CASE_UPPER);
		$department = self::getOldDepartmentById((int)$params['ID']);
		if (!$department)
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Department not found'),
			);
		}

		$iBlockSection = new \CIBlockSection();
		if(!$iBlockSection->Delete($department['ID']))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error($iBlockSection->LAST_ERROR),
			);
		}
	}

	/**
	 * @param int $id
	 * @return null|array{ID: int, NAME: string}
	 */
	public static function getOldDepartmentById(int $id): ?array
	{
		if (!Loader::includeModule('iblock'))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Module iblock is not installed'),
			);
		}

		if ($id <= 0)
		{
			return null;
		}

		$result = \CIBlockSection::GetList(
			arFilter: [
				'ID' => $id,
				'IBLOCK_ID' => self::getOldDepartmentIblockId(),
				'CHECK_PERMISSIONS' => 'N',
			],
			arSelect: ['ID', 'NAME', 'UF_HEAD'],
		);

		$department = $result->Fetch();
		if ($department)
		{
			return $department;
		}

		return null;
	}

	/**
	 * @param string $name
	 * @return array{ID: int, NAME: string, SORT: int, IBLOCK_SECTION_ID: int, UF_HEAD: int} | null
	 * @throws Main\LoaderException
	 * @throws UpdateFailedException
	 */
	public static function getDepartmentByName(string $name): ?array
	{
		if (!Loader::includeModule('iblock'))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Module iblock is not installed'),
			);
		}

		$result = \CIBlockSection::GetList(
			arFilter: [
				'NAME' => $name,
				'IBLOCK_ID' => self::getOldDepartmentIblockId(),
				'CHECK_PERMISSIONS' => 'N',
			],
			arSelect: ['ID', 'NAME', 'SORT', 'IBLOCK_SECTION_ID', 'UF_HEAD'],
		);

		while($department = $result->Fetch())
		{
			return is_array($department) ? $department : null;
		}

		return null;
	}

	/**
	 * @param list<int> $ids
	 *
	 * @return array|null
	 * @throws LoaderException
	 * @throws UpdateFailedException
	 */
	public static function getListByIds(array $ids): ?array
	{
		if (empty($ids))
		{
			return null;
		}

		if (!Loader::includeModule('iblock'))
		{
			throw (new UpdateFailedException())->addError(
				new Main\Error('Module iblock is not installed'),
			);
		}

		$result = \CIBlockSection::GetList(
			arFilter: [
				'ID' => $ids,
				'IBLOCK_ID' => self::getOldDepartmentIblockId(),
				'CHECK_PERMISSIONS' => 'N',
			],
			arSelect: ['ID', 'NAME', 'SORT', 'IBLOCK_SECTION_ID', 'UF_HEAD'],
		);

		$departments = [];
		while($department = $result->Fetch())
		{
			$departments[$department['ID']] = $department;
		}

		return $departments;
	}

	private static function getOldDepartmentIblockId(): int
	{
		return \COption::GetOptionInt('intranet', 'iblock_structure', 0);
	}
}
