<?php
namespace Bitrix\Crm\Integration\Sign\Access\Service;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Permission\PermissionDictionary;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\AccessRights\DataProvider;
use CCrmOwnerType;
use CCrmPerms;
use CCrmRole;

abstract class RolePermissionService
{
	public const ROLE_GROUP_CODE = 'SIGN_SMART_DOCUMENT';

	/**
	 * @var RoleRelationService
	 */
	private RoleRelationService $roleRelationService;
	protected static array $settings;
	protected static array $roles;
	private Container $container;
	
	/**
	 * @param array $permissionSettings permission settings array
	 */
	public function saveRolePermissions(array &$permissionSettings): void
	{
		foreach ($permissionSettings as &$setting)
		{
			$roleId = (int) ($setting['id'] ?? 0);
			$roleTitle = (string) ($setting['title'] ?? '');
			$setting['accessRights'] ??= [];
			if($roleId > 0 && !$this->roleRelationService->validateRoleId($roleId))
			{
				continue;
			}

			$smartDocumentFactory = $this->container->getFactory(CCrmOwnerType::SmartDocument);
			$smartB2eDocumentFactory = $this->container->getFactory(CCrmOwnerType::SmartB2eDocument);

			$categoryFactory = $this->container->getFactory(CCrmOwnerType::Contact);

			if (!$smartDocumentFactory || !$smartB2eDocumentFactory || !$categoryFactory)
			{
				continue;
			}
			
			$contactCategoryId = $categoryFactory
				->getCategoryByCode(SmartDocument::CONTACT_CATEGORY_CODE)
				->getId()
			;

			$smartDocumentCategory = $smartDocumentFactory->getDefaultCategory();
			$smartB2eDocumentCategories = $smartB2eDocumentFactory->getCategories();

			if (!$smartDocumentCategory || empty($smartB2eDocumentCategories))
			{
				continue;
			}

			$smartDocumentCategoryId = $smartDocumentCategory->getId();

			$contactEntityName = CCrmOwnerType::ContactName;
			$smartDocumentEntityName = CCrmOwnerType::SmartDocumentName;
			$smartB2eDocumentEntityName = CCrmOwnerType::SmartB2eDocumentName;

			$preparedValues = [];
			foreach([$contactEntityName, $smartDocumentEntityName, $smartB2eDocumentEntityName] as $entity)
			{
				$preparedValues[$entity] = $this->fillPermissionSet($setting, $entity);
			}

			$contactPermissionEntity = $this->getPermissionEntity(CCrmOwnerType::Contact, $contactCategoryId);
			$rolePerms[$contactPermissionEntity] = $preparedValues[$contactEntityName];

			$smartDocumentPermissionEntity =
				$this->getPermissionEntity(CCrmOwnerType::SmartDocument, $smartDocumentCategoryId);
			$rolePerms[$smartDocumentPermissionEntity] = $preparedValues[$smartDocumentEntityName];

			foreach($smartB2eDocumentCategories as $category)
			{
				$smartB2eDocumentCategoryId = $category->getId();
				if ($smartB2eDocumentCategoryId === null)
				{
					continue;
				}

				$smartB2eDocumentPermissionEntity =
					$this->getPermissionEntity(CCrmOwnerType::SmartB2eDocument, $smartB2eDocumentCategoryId);
				$rolePerms[$smartB2eDocumentPermissionEntity] = $preparedValues[$smartB2eDocumentEntityName];
			}
			$fields = [
				'RELATION' => $rolePerms,
				'NAME' => $roleTitle,
				'IS_SYSTEM' => 'Y',
				'GROUP_CODE' => self::ROLE_GROUP_CODE,
			];

			RolePermissionLogContext::getInstance()->set([
				'scenario' => 'save sign permissions'
			]);

			if (!$roleId)
			{
				$roleId = (new CCrmRole())->Add($fields);
			}
			else
			{
				(new CCrmRole())->Update($roleId, $fields);
			}
			$setting['id'] = $roleId;

			RolePermissionLogContext::getInstance()->clear();
		}
	}

	protected function validatePermission($permissionSet, $action, $permission): bool
	{
		return isset($permissionSet[$action]) && isset($permission['value']) && in_array($permission['value'], [
				CCrmPerms::PERM_ALL,
				CCrmPerms::PERM_DEPARTMENT,
				CCrmPerms::PERM_NONE,
				CCrmPerms::PERM_SUBDEPARTMENT,
				CCrmPerms::PERM_OPEN,
				CCrmPerms::PERM_SELF,
				0
			]);
	}

	protected function getPermissionEntity(int $ownerType, int $categoryId): string
	{
		return (new PermissionEntityTypeHelper($ownerType))
			->getPermissionEntityTypeForCategory($categoryId);

	}
	/**
	 * @param int $roleId role identification number
	 */
	public function deleteRole(int $roleId): Result
	{
		if (!$this->roleRelationService->validateRoleId($roleId))
		{
			return (new Result())
				->addError(new Error('RULE NOT VALID'));
		}
		
		(new CCrmRole)->Delete($roleId);
		return new Result();
	}

	public function __construct()
	{
		$this->roleRelationService = new RoleRelationService();
		$this->container = Container::getInstance();
	}

	/**
	 * Get Crm role list with SIGN_GROUP_CODE
	 * @return array<array{ID: string, NAME: string, IS_SYSTEM: "Y"|"N", CODE: string, GROUP_CODE: string}>
	 */
	public function getRoleList(): array
	{
		if (!empty(static::$roles))
		{
			return static::$roles;
		}

		$roles = [];
		$roleListResult =  CCrmRole::GetList(
			['ID' => 'ASC', ],
			['=GROUP_CODE' => self::ROLE_GROUP_CODE,]
		);

		while ($role = $roleListResult->Fetch())
		{
			$roles[] = $role;
		}
		static::$roles = $roles;

		return $roles;
	}

	/**
	 * Saved permission list
	 * @param array $roleIds
	 * @return array
	 */
	public function getSavedPermissions(array $roleIds = []): array
	{
		$permissions = RolePermission::getAll();
		$result = [];
		foreach ($permissions as $roleId => $permission)
		{
			if (!in_array($roleId, $roleIds))
			{
				continue;
			}
			$result[$roleId] = $permission;
		}
		
		return $result;
	}

	/**
	 * Get user groups
	 * @return array
	 */
	abstract public function getUserGroups(): array;

	/**
	 * returns access rights list
	 * @return array
	 */
	abstract public function getAccessRights(): array;
	
	/**
	 * @param $setting
	 * @param $entity
	 * @return array
	 */
	private function fillPermissionSet(&$setting, $entity): array
	{
		$permissionSet = CCrmRole::getDefaultPermissionSet();

		if ($entity !== 'CONTACT' || empty($setting['accessRights']))
		{
			foreach ($permissionSet as &$perm)
			{
				$perm['-'] = '';
			}
		}

		foreach ($setting['accessRights'] as $key => $permission)
		{
			$permissionCode = $this->getPermissionCode($permission['id']);
			$permissionId = explode('_', $permissionCode);
			$action = array_pop($permissionId);
			array_shift($permissionId);
			array_shift($permissionId);

			if (!$this->validatePermission($permissionSet, $action, $permission))
			{
				$permissionSet[$action] = ['-' => CCrmPerms::PERM_NONE];
				unset($setting['accessRights'][$key]);
				continue;
			}

			if (mb_strpos($entity, implode('_', $permissionId)) === 0)
			{
				$permissionSet[$action] = ['-' => $permission['value'] ?: CCrmPerms::PERM_NONE];
				unset($setting['accessRights'][$key]);
			}
		}
		return $permissionSet;
	}
	
	abstract protected function getAbleOptions(): array;
	
	protected function getRoleAccessRights(int $roleId): array
	{
		$settings = $this->getSettings();

		$accessRights = [];
		if (array_key_exists($roleId, $settings))
		{
			foreach ($settings[$roleId] as $permissionId => $permission)
			{
				$accessRights[] = [
					'id' => $permissionId,
					'value' => $permission['VALUE'],
					'type' => PermissionDictionary::TYPE_VARIABLES,
					'enableSearch' => true,
				];
			}
		}

		return $accessRights;
	}

	protected function getMemberInfo(string $code): array
	{
		$accessCode = new AccessCode($code);
		$member = (new DataProvider())->getEntity($accessCode->getEntityType(), $accessCode->getEntityId());
		return $member->getMetaData();
	}
	
	protected function getRoleMembers(int $roleId): array
	{
		$members = [];

		try
		{
			$relations = $this
				->roleRelationService
				->getRelationList(["filter" => ["=ROLE_ID" => $roleId]]);
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return [];
		}

		foreach ($relations as $row)
		{
			$accessCode = $row['RELATION'];
			$members[$accessCode] = $this->getMemberInfo($accessCode);
		}

		return $members;
	}

	abstract protected function getSettings(): array;
	
	protected function mapCurrentPermissions($permissions): array
	{
		$preparedPermissions = [];
		$localPermissions = $this->getLocalPermissionList();
		foreach ($permissions as $roleId => $rolePermissions)
		{
			foreach ($rolePermissions as $entity => $permission)
			{
				foreach ($localPermissions as $value => $localPermission)
				{
					if (isset($preparedPermissions[$roleId][$localPermission['NAME']]))
					{
						continue;
					}
					$exploded = explode('_', $localPermission['NAME']);
					$action = array_pop($exploded);
					array_shift($exploded);
					array_shift($exploded);
					
					if (
						mb_strpos($entity, implode('_', $exploded)) === 0
						&& isset($permission[$action])
					)
					{
						$preparedPermissions[$roleId][$value] =
							[
								'VALUE' => is_array($permission[$action]['-']) ? $permission[$action]['-']['ATTR'] : $permission[$action]['-'],
							]
						;
					}
				}
			}
		}
		
		return $preparedPermissions;
	}
	
	abstract protected function getLocalPermissionList(): array;
	
	abstract protected function getPermissionCode(string $id): ?string;
	abstract public function getValueForPermission(array $roles, string $permissionId): ?string;
}
