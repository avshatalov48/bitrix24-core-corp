<?php
namespace Bitrix\Crm\Integration\Sign;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Integration\Sign\Access\Service\RolePermissionService;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SiteTable;
use Bitrix\Main\SystemException;
use CBitrix24;
use CCrmOwnerType;
use CCrmPerms;
use CCrmRole;

class Access
{
	private const DEFAULT_ROLE_NAME = 'Sign';
	public static function install(
		string $name = self::DEFAULT_ROLE_NAME,
		array $permissionSet = [],
		string $code = '',
		?int $relationGroupId = null
	): string
	{
		if ($name === self::DEFAULT_ROLE_NAME)
		{
			return '';
		}

		$permissionSet[CCrmOwnerType::ContactName] ??= CCrmRole::getDefaultPermissionSet();
		$permissionSet[CCrmOwnerType::SmartDocumentName] ??= CCrmRole::getDefaultPermissionSet();
		$permissionSet[CCrmOwnerType::SmartB2eDocumentName] ??= CCrmRole::getDefaultPermissionSet();

		$filter = [
			'=GROUP_CODE' => RolePermissionService::ROLE_GROUP_CODE,
			];

		if ($code)
		{
			$filter['=CODE'] = $code;
		}
		else
		{
			$filter['=NAME'] = $name;
		}
		$existedRole = CCrmRole::GetList(
			['ID' => 'DESC', ],
			$filter
		);

		if ($existedRole->SelectedRowsCount() > 0)
		{
			return '';
		}

		$contactFactory = Container::getInstance()
			->getFactory(CCrmOwnerType::Contact);
		$rolePerms = [];
		
		if (!$contactFactory
			|| !($contactCategory = $contactFactory->getCategoryByCode(SmartDocument::CONTACT_CATEGORY_CODE)))
		{
			return '\Bitrix\Crm\Integration\Sign\Access::installDefaultRoles();';
		}

		$rolePerms[(new PermissionEntityTypeHelper(CCrmOwnerType::Contact))
			->getPermissionEntityTypeForCategory(
				$contactCategory->getId()
			)] = $permissionSet[CCrmOwnerType::ContactName];

		$smartDocumentFactory = Container::getInstance()
			->getFactory(CCrmOwnerType::SmartDocument);
		
		if (!$smartDocumentFactory || !($smartDocumentCategory = $smartDocumentFactory->getDefaultCategory()))
		{
			return '\Bitrix\Crm\Integration\Sign\Access::installDefaultRoles();';
		}

		$rolePerms[(new PermissionEntityTypeHelper(CCrmOwnerType::SmartDocument))
			->getPermissionEntityTypeForCategory(
				$smartDocumentCategory->getId()
			)] = $permissionSet[CCrmOwnerType::SmartDocumentName];

		$smartB2eDocumentFactory = Container::getInstance()
			->getFactory(CCrmOwnerType::SmartB2eDocument)
		;
		$smartB2eDocumentCategory = $smartB2eDocumentFactory?->getDefaultCategory();

		if ($smartB2eDocumentCategory === null)
		{
			return '\Bitrix\Crm\Integration\Sign\Access::installDefaultRoles();';
		}

		$rolePerms[(new PermissionEntityTypeHelper(CCrmOwnerType::SmartB2eDocument))
			->getPermissionEntityTypeForCategory(
				$smartB2eDocumentCategory->getId()
			)] = $permissionSet[CCrmOwnerType::SmartB2eDocumentName];

		$fields = [
			'RELATION' => $rolePerms,
			'NAME' => $name,
			'IS_SYSTEM' => 'Y',
			'GROUP_CODE' => RolePermissionService::ROLE_GROUP_CODE,
			'CODE' => $code,
		];

		$role = (new CCrmRole())->Add($fields);

		if ($role && $relationGroupId)
		{
			$obRes = (new CCrmRole())->GetRelation();
			$relation = [];
			while ($currentRelation = $obRes->Fetch())
			{
				$relation[$currentRelation['RELATION']][] = $currentRelation['ROLE_ID'];
			}
			$relation['G' . $relationGroupId][] = $role;
			(new CCrmRole())->SetRelation($relation);
		}

		return '';
	}
	
	/**
	 * Installing special roles for module `Sign` from updater
	 * @param bool $removeAllPrevious
	 * @return string
	 */
	public static function installDefaultRoles(bool $removeAllPrevious = false): string
	{
		self::loadLanguageFile();

		$filter = $removeAllPrevious ? [
			'=GROUP_CODE' => RolePermissionService::ROLE_GROUP_CODE,
		] : [
			'=NAME' => self::DEFAULT_ROLE_NAME,
			'=IS_SYSTEM' => 'Y',
		];
		$existedRoles = CCrmRole::GetList(
			['ID' => 'DESC', ],
			$filter
		);

		while($existedRole = $existedRoles->Fetch())
		{
			if (isset($existedRole['ID']))
			{
				(new CCrmRole)->Delete($existedRole['ID']);
			}
		}

		$accessList = [
			CCrmOwnerType::ContactName => [
				'READ' => ['-' => CCrmPerms::PERM_ALL],
				'EXPORT' => ['-' => CCrmPerms::PERM_ALL],
				'IMPORT' => ['-' => CCrmPerms::PERM_ALL],
				'ADD' => ['-' => CCrmPerms::PERM_ALL],
				'WRITE' => ['-' => CCrmPerms::PERM_ALL],
				'DELETE' => ['-' => CCrmPerms::PERM_ALL],
			],
			CCrmOwnerType::SmartDocumentName => [
				'READ' => ['-' => CCrmPerms::PERM_ALL],
				'EXPORT' => ['-' => CCrmPerms::PERM_ALL],
				'IMPORT' => ['-' => CCrmPerms::PERM_ALL],
				'ADD' => ['-' => CCrmPerms::PERM_ALL],
				'WRITE' => ['-' => CCrmPerms::PERM_ALL],
				'DELETE' => ['-' => CCrmPerms::PERM_ALL],
			],
			CCrmOwnerType::SmartB2eDocumentName => [
				'READ' => ['-' => CCrmPerms::PERM_SELF],
				'EXPORT' => ['-' => CCrmPerms::PERM_SELF],
				'IMPORT' => ['-' => CCrmPerms::PERM_SELF],
				'ADD' => ['-' => CCrmPerms::PERM_SELF],
				'WRITE' => ['-' => CCrmPerms::PERM_SELF],
				'DELETE' => ['-' => CCrmPerms::PERM_SELF],
			],
		];

		$result = self::install(
			Loc::getMessage('CRM_SIGN_ROLE_EMPLOYMENT'),
			$accessList,
			RolePermissionService::ROLE_GROUP_CODE . '_EMPLOYMENT',
			self::getNeededGroupId('EMPLOYEES_' . self::getSiteId())
		);

		if (!empty($result))
		{
			return $result;
		}
		$chefAccessList = $accessList;
		$chefAccessList[CCrmOwnerType::SmartB2eDocumentName] = [
			'READ' => ['-' => CCrmPerms::PERM_SUBDEPARTMENT],
			'EXPORT' => ['-' => CCrmPerms::PERM_SUBDEPARTMENT],
			'IMPORT' => ['-' => CCrmPerms::PERM_SUBDEPARTMENT],
			'ADD' => ['-' => CCrmPerms::PERM_SUBDEPARTMENT],
			'WRITE' => ['-' => CCrmPerms::PERM_SUBDEPARTMENT],
			'DELETE' => ['-' => CCrmPerms::PERM_SUBDEPARTMENT],
		];

		return self::install(
			Loc::getMessage('CRM_SIGN_ROLE_CHIEF'),
			$chefAccessList,
			RolePermissionService::ROLE_GROUP_CODE . '_CHIEF',
			self::getNeededGroupId('DIRECTION')
		);
	}
	private static function getNeededGroupId(string $filterValue): ?int
	{
		try
		{
			$employeeGroupId = GroupTable::getList([
				'select' => ['ID'],
				'filter' => ['=STRING_ID' => $filterValue],
			])->fetch();
		}
		catch (ObjectPropertyException|SystemException|ArgumentException $e)
		{
			return null;
		}

		return $employeeGroupId ? (int)$employeeGroupId['ID'] : null;
	}

	private static function getSiteId(): ?string
	{
		try
		{
			/** @todo Use SiteTable::getDefaultSiteId() */
			$site = SiteTable::getList([
				'select' => ['LID', 'LANGUAGE_ID'],
				'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
				'cache' => ['ttl' => 86400],
			])->fetch();
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return null;
		}

		return $site ? $site['LID'] : null;
	}
	
	private static function loadLanguageFile(): void
	{
		try
		{
			if (
				ModuleManager::isModuleInstalled('bitrix24')
				&& Loader::includeModule('bitrix24')
				&& method_exists('CBitrix24', 'getLicensePrefix')
			)
			{
				$defaultLanguage = CBitrix24::getLicensePrefix();
			}
			else
			{
				$defaultLanguage = LanguageTable::getList([
					'select' => ['ID'],
					'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
				])->fetch()['ID'] ?? null;
			}
		}
		catch (ObjectPropertyException|ArgumentException|LoaderException|SystemException $e)
		{
			$defaultLanguage = 'en';
		}

		if (!in_array($defaultLanguage, ['ru', 'en', 'de']))
		{
			$defaultLanguage = 'en';
		}
		
		Loc::loadLanguageFile(__FILE__, $defaultLanguage);
	}
}
