<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\Security\Role\RolePreset;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Web\Json;

/**
 * Class CategoryRepository
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class CategoryRepository
{
	private const COMPANY_CODE = 'CATALOG_CONTRACTOR_COMPANY';
	private const CONTACT_CODE = 'CATALOG_CONTRACTOR_CONTACT';

	/**
	 * @param int $entityTypeId
	 * @param int $categoryId
	 * @return bool
	 */
	public static function isContractorCategory(int $entityTypeId, int $categoryId): bool
	{
		$category = self::getByEntityTypeId($entityTypeId);

		return $category && $category->getId() === $categoryId;
	}

	/**
	 * @param int $entityTypeId
	 * @return Category|null
	 */
	public static function getOrCreateByEntityTypeId(int $entityTypeId): ?Category
	{
		$result = self::getByEntityTypeId($entityTypeId);

		if (!$result)
		{
			return self::createByEntityTypeId($entityTypeId);
		}

		return $result;
	}

	/**
	 * @param int $entityTypeId
	 * @return Category|null
	 */
	public static function getByEntityTypeId(int $entityTypeId): ?Category
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		$code = self::getCodeByEntityTypeId($entityTypeId);
		if (!$code)
		{
			return null;
		}

		$factory->clearCategoriesCache();
		$category = $factory->getCategoryByCode($code);
		if (
			!$category
			|| !$category->getIsSystem()
		)
		{
			return null;
		}

		return $category;
	}

	/**
	 * @param int $entityTypeId
	 * @return int|null
	 */
	public static function getIdByEntityTypeId(int $entityTypeId): ?int
	{
		$category = self::getByEntityTypeId($entityTypeId);

		return $category ? $category->getId() : null;
	}

	/**
	 * @param int $entityTypeId
	 * @return Category|null
	 */
	public static function createByEntityTypeId(int $entityTypeId): ?Category
	{
		$code = self::getCodeByEntityTypeId($entityTypeId);
		if (!$code)
		{
			return null;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$update = [
			'IS_SYSTEM' => 'Y',
			'ENTITY_TYPE_ID' => $entityTypeId,
			'SORT' => 500,
			'SETTINGS' => Json::encode([
				'disabledFieldNames' => self::getDisabledFieldsByEntityTypeId($entityTypeId),
				'isTrackingEnabled' => false,
				'uiSettings' => self::getUISettingsByEntityTypeId($entityTypeId),
			]),
		];
		$insert = $update;
		$insert['CODE'] = $code;
		$insert['NAME'] = '';
		$merge = $helper->prepareMerge(ItemCategoryTable::getTableName(), ['CODE'], $insert, $update);
		if ($merge[0])
		{
			$connection->query($merge[0]);
			ItemCategoryTable::cleanCache();
		}

		$result = self::getByEntityTypeId($entityTypeId);
		if ($result)
		{
			self::setPermissions($entityTypeId, $result->getId());
		}

		return $result;
	}

	/**
	 * @param int $entityTypeId
	 * @param int $categoryId
	 */
	private static function setPermissions(int $entityTypeId, int $categoryId): void
	{
		$rolesList = \CCrmRole::getList();

		$systemRolesIds = \Bitrix\Crm\Security\Role\RolePermission::getSystemRolesIds();
		while ($role = $rolesList->fetch())
		{
			if (in_array($role['ID'], $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}
			if ($role['GROUP_CODE'])
			{
				continue;
			}

			$rolePerms = \CCrmRole::getRolePermissionsAndSettings($role['ID']);

			$entityName = \CCrmOwnerType::resolveName($entityTypeId);

			$rolePerms[
				sprintf(
					'%s_C%d',
					$entityName,
					$categoryId
				)
			] = RolePreset::getDefaultPermissionSetForEntityByCode(
				$role['CODE'],
				new CategoryIdentifier($entityTypeId, $categoryId)
			);

			$fields = ['RELATION' => $rolePerms];
			(new \CCrmRole())->update($role['ID'], $fields);
		}
	}

	/**
	 * @param int $entityTypeId
	 * @return string|null
	 */
	private static function getCodeByEntityTypeId(int $entityTypeId): ?string
	{
		$map = [
			\CCrmOwnerType::Contact => self::CONTACT_CODE,
			\CCrmOwnerType::Company => self::COMPANY_CODE,
		];

		return $map[$entityTypeId] ?? null;
	}

	/**
	 * @param int $entityTypeId
	 * @return string[]
	 */
	private static function getDisabledFieldsByEntityTypeId(int $entityTypeId): array
	{
		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			return array_merge(
				[
					Item::FIELD_NAME_TYPE_ID,
					Item\Company::FIELD_NAME_INDUSTRY,
					Item\Company::FIELD_NAME_REVENUE,
					Item::FIELD_NAME_CURRENCY_ID,
					Item\Company::FIELD_NAME_EMPLOYEES,
				],
				UtmTable::getCodeList(),
			);
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return array_merge(
				[
					Item::FIELD_NAME_TYPE_ID,
					Item::FIELD_NAME_SOURCE_ID,
					Item::FIELD_NAME_SOURCE_DESCRIPTION,
				],
				UtmTable::getCodeList(),
			);
		}

		return [];
	}

	/**
	 * @param int $entityTypeId
	 * @return []
	 */
	private static function getUISettingsByEntityTypeId(int $entityTypeId): array
	{
		$gridDefaultFields = [];
		$filterDefaultFields = [];

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			$gridDefaultFields = [
				'COMPANY_SUMMARY',
				'ACTIVITY_ID',
				'WEB',
				'PHONE',
				'EMAIL',
			];
			$filterDefaultFields = [
				'TITLE',
				'PHONE',
				'EMAIL',
			];
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			$gridDefaultFields = [
				'CONTACT_SUMMARY',
				'ACTIVITY_ID',
				'POST',
				'COMPANY_ID',
				'PHONE',
				'EMAIL',
			];
			$filterDefaultFields = [
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'PHONE',
				'EMAIL',
				'COMPANY_ID',
				'COMPANY_TITLE',
			];
		}

		return [
			'grid' => [
				'defaultFields' => $gridDefaultFields,
			],
			'filter' => [
				'defaultFields' => $filterDefaultFields,
			],
		];
	}
}
