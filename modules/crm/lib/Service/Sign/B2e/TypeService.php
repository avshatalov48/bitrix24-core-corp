<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

/**
 * Service for working with b2e documents.
 */
final class TypeService
{
	public const SIGN_B2E_ITEM_CATEGORY_CODE = 'SIGN_B2E_ITEM_CATEGORY';
	public const SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE = 'SIGN_B2E_EMPLOYEE_ITEM_CATEGORY';
	public const CACHE_TTL = 86400;
	private const PERMISSIONS = [
		'READ',
		'WRITE',
		'DELETE',
		'ADD',
		'EXPORT',
		'IMPORT',
	];
	private const PERMISSIONS_WITHOUT_ATTR = [
		'EXPORT',
		'IMPORT',
	];
	private const ID_FIELD = 'ID';
	private const CODE_FIELD = 'CODE';

	private const ROLE_GROUP_CODE = 'SIGN_SMART_DOCUMENT';

	public function isCreated(): bool
	{
		$result = TypeTable::getByEntityTypeId(CCrmOwnerType::SmartB2eDocument)->fetchObject();

		return is_object($result);
	}

	public function getDefaultCategoryId(): int
	{
		return (int)Container::getInstance()
			->getFactory(CCrmOwnerType::SmartB2eDocument)
			?->getDefaultCategory()
			?->getId()
		;
	}

	/**
	 *  @return list<array{
	 *     ID: int,
	 *	   ENTITY_TYPE_ID: int,
	 *     IS_DEFAULT: bool,
	 *     IS_SYSTEM: bool,
	 *     CODE: string,
	 *     CREATED_DATE: DateTime,
	 *     NAME: string,
	 *     SORT: int,
	 *     SETTINGS: string,
	 *  }>
	 */
	public function getCategories(): array
	{
		return ItemCategoryTable::query()
			->setSelect(['*'])
			->where('ENTITY_TYPE_ID', CCrmOwnerType::SmartB2eDocument)
			->setCacheTtl(self::CACHE_TTL)
			->fetchAll()
		;
	}

	/**
	 *  @return array{
	 *     ID: int,
	 *	   ENTITY_TYPE_ID: int,
	 *     IS_DEFAULT: bool,
	 *     IS_SYSTEM: bool,
	 *     CODE: string,
	 *     CREATED_DATE: DateTime,
	 *     NAME: string,
	 *     SORT: int,
	 *     SETTINGS: string,
	 *  } | null
	 */
	public function getCategoryById(int $categoryId): ?array
	{
		return $this->findCategoryBy(self::ID_FIELD, $categoryId);
	}

	/**
	 *  @return array{
	 *     ID: int,
	 *	   ENTITY_TYPE_ID: int,
	 *     IS_DEFAULT: bool,
	 *     IS_SYSTEM: bool,
	 *     CODE: string,
	 *     CREATED_DATE: DateTime,
	 *     NAME: string,
	 *     SORT: int,
	 *     SETTINGS: string,
	 *  } | null
	 */
	public function getCategoryByCode(string $code): ?array
	{
		if (
			!in_array(
				$code,
				[self::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE, self::SIGN_B2E_ITEM_CATEGORY_CODE],
				true,
			)
		)
		{
			return null;
		}

		return $this->findCategoryBy(self::CODE_FIELD, $code);
	}

	private function findCategoryBy(string $column, int|string $value): ?array
	{
		if (!in_array($column, [self::ID_FIELD, self::CODE_FIELD], true))
		{
			return null;
		}

		$categories = $this->getCategories();
		$key = array_search($value, array_column($categories, $column));
		if($key === false)
		{
			return null;
		}

		return $categories[$key] ?? null;
	}

	public function isCategoriesEnabled(): bool
	{
		$type = TypeTable::query()
			->where('ENTITY_TYPE_ID', CCrmOwnerType::SmartB2eDocument)
			->where('IS_CATEGORIES_ENABLED', true)
			->setLimit(1)
			->fetchObject()
		;

		return is_object($type);
	}

	public function enableCategories(): Result
	{
		$type = TypeTable::query()
			->where('ENTITY_TYPE_ID', CCrmOwnerType::SmartB2eDocument)
			->where('IS_CATEGORIES_ENABLED', false)
			->setLimit(1)
			->fetchObject()
		;

		if ($type === null)
		{
			return (new Result())->addError(new Error('Categories already enabled'));
		}

		return TypeTable::update(
			$type->getId(),
			['IS_CATEGORIES_ENABLED' => true],
		);
	}

	public function addCategory(string $name, string $code): AddResult
	{
		return ItemCategoryTable::add([
			'ENTITY_TYPE_ID' => CCrmOwnerType::SmartB2eDocument,
			'IS_DEFAULT' => false,
			'IS_SYSTEM' => false,
			'CODE' => $code,
			'NAME' => $name,
			'SORT' => 500,
		]);
	}

	public function addCategoryDefaultPermissions(int $categoryId): Result
	{
		$result = new Result();
		$roles = RoleTable::query()
			->where('GROUP_CODE', self::ROLE_GROUP_CODE)
			->fetchCollection()
		;

		$permissionEntityTypeHelper = new PermissionEntityTypeHelper(CCrmOwnerType::SmartDocument);
		$smartDocumentPermissionEntity = $permissionEntityTypeHelper->getPermissionEntityTypeForCategory($categoryId);

		foreach ($roles as $role)
		{
			foreach (self::PERMISSIONS as $permission)
			{
				$rolePermission = RolePermissionTable::query()
					->where('ROLE_ID', $role->getId())
					->where('ENTITY', $smartDocumentPermissionEntity)
					->where('PERM_TYPE', $permission)
					->fetchObject()
				;

				if (is_object($rolePermission))
				{
					continue;
				}

				$addResult = RolePermissionTable::add([
					'ROLE_ID' => $role->getId(),
					'ENTITY' => $smartDocumentPermissionEntity,
					'FIELD' => '-',
					'FIELD_VALUE' => null,
					'PERM_TYPE' => $permission,
					'ATTR' => !in_array($permission, self::PERMISSIONS_WITHOUT_ATTR, true) ? 'X' : '',
					'SETTINGS' => null,
				]);

				if (!$addResult->isSuccess())
				{
					return $addResult;
				}
			}
		}

		return $result;
	}

	public function updateDefaultCategory(string $name, string $code): Result
	{
		$result = new Result();

		$defaultCategoryId = $this->getDefaultCategoryId();
		if ($defaultCategoryId === 0)
		{
			return $result->addError(new Error('Default Category not found'));
		}

		$dbConnection = Application::getConnection();
		$helper = $dbConnection->getSqlHelper();
		$nameValue = (string)$helper->forSql($name);
		$codeValue = (string)$helper->forSql($code);
		$sql = sprintf(
			'UPDATE b_crm_item_category SET NAME = \'%s\', CODE = \'%s\' WHERE ID = %d',
			$nameValue,
			$codeValue,
			$defaultCategoryId,
		);

		try
		{
			$dbConnection->queryExecute($sql);
		}
		catch (\Throwable $throwable)
		{
			return $result->addError(new Error($throwable->getMessage()));
		}

		ItemCategoryTable::cleanCache();

		return $result;
	}

	public function isDefaultCategory(int $categoryId): bool
	{
		if ($categoryId < 1)
		{
			return false;
		}

		$category = $this->getCategoryById($categoryId);

		return (string)($category['IS_DEFAULT'] ?? 'N') === 'Y';
	}
}
