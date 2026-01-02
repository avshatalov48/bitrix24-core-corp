<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\Entity\ContractorConfig;
use Bitrix\Crm\Security\Role\Manage\Permissions\WriteConfig;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\EO_RoleRelation;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Crm\Security\Role\RolePreset;
use Bitrix\Crm\Security\Role\RoleRelationHelper;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use CCrmOwnerType;
use CCrmRole;

/**
 * Class CategoryRepository
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class CategoryRepository
{
	public const CATALOG_CONTRACTOR_COMPANY = 'CATALOG_CONTRACTOR_COMPANY';
	public const CATALOG_CONTRACTOR_CONTACT = 'CATALOG_CONTRACTOR_CONTACT';

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

			$factory = Container::getInstance()->getFactory($entityTypeId);
			$factory?->clearCategoriesCache();
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
		$permissionHelper = new PermissionEntityTypeHelper($entityTypeId);

		$contractorGroupCode = GroupCodeGenerator::getContractorGroupCode();
		$contractorRolesIds = RolePermission::getRoleIdsByGroupCode($contractorGroupCode);
		if (empty($contractorRolesIds))
		{
			self::createDefaultAdminInventoryRole();
		}

		$rolesList = CCrmRole::getList(
			arFilter: [
				'IS_SYSTEM' => 'N',
				'GROUP_CODE' => $contractorGroupCode,
			],
		);

		while ($role = $rolesList->fetch())
		{
			$rolePerms = CCrmRole::getRolePermissionsAndSettings($role['ID']);
			$permissionEntity = $permissionHelper->getPermissionEntityTypeForCategory($categoryId);

			$rolePerms[$permissionEntity] = RolePreset::getDefaultPermissionSetForEntityByCode(
				$role['CODE'],
				new CategoryIdentifier($entityTypeId, $categoryId)
			);

			$fields = ['PERMISSIONS' => $rolePerms];
			(new CCrmRole())->update($role['ID'], $fields);
		}
	}

	private static function createDefaultAdminInventoryRole(): void
	{
		$role = (new EO_Role())
			->setName(Loc::getMessage('CRM_INTEGRATION_CATALOG_CONTRACTOR_DEFAULT_ADMIN_ROLE_NAME'))
			->setIsSystem('N')
			->setGroupCode(GroupCodeGenerator::getContractorGroupCode());

		// todo: maybe set code === 'ADMIN'?
		$contractorAdminPermission = (new EO_RolePermission())
			->setEntity(ContractorConfig::CODE)
			->setPermType((new WriteConfig())->code())
			->setAttr(UserPermissions::PERMISSION_ALL)
			->setSettings(null);

		$role->addToPermissions($contractorAdminPermission);

		$allUsersGroupCode = (new RoleRelationHelper())->getAllUsersGroupRelationAccessCode();
		if ($allUsersGroupCode !== null)
		{
			$allUsersRelation = (new EO_RoleRelation())
				->setRelation($allUsersGroupCode);

			$role->addToRelations($allUsersRelation);
		}

		$role->save();
	}

	/**
	 * @param int $entityTypeId
	 * @return string|null
	 */
	private static function getCodeByEntityTypeId(int $entityTypeId): ?string
	{
		return self::getCodeMap()[$entityTypeId] ?? null;
	}

	private static function getCodeMap(): array
	{
		return [
			CCrmOwnerType::Contact => self::CATALOG_CONTRACTOR_CONTACT,
			CCrmOwnerType::Company => self::CATALOG_CONTRACTOR_COMPANY,
		];
	}

	/**
	 * @param int $entityTypeId
	 * @return string[]
	 */
	private static function getDisabledFieldsByEntityTypeId(int $entityTypeId): array
	{
		if ($entityTypeId === CCrmOwnerType::Company)
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

		if ($entityTypeId === CCrmOwnerType::Contact)
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

		if ($entityTypeId === CCrmOwnerType::Company)
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

		if ($entityTypeId === CCrmOwnerType::Contact)
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

	public static function isAtLeastOneContractorExists(): bool
	{
		foreach (self::getCodeMap() as $entityTypeId => $code)
		{
			if (self::getByEntityTypeId($entityTypeId) !== null)
			{
				return true;
			}
		}

		return false;
	}
}
