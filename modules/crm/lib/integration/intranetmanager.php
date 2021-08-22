<?php
/**
 * Created by PhpStorm.
 * User: zg
 * Date: 20.06.2015
 * Time: 15:50
 */

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class IntranetManager
{
	/** @var array|null  */
	private static $subordinateUserMap = null;
	/** @var int[] entityTypeId[] */
	protected static $entityTypesInCustomSections;

	/**
	* Check if user is head of any company departmant
	* @param integer $userID User ID
	* @return boolean
	*/
	public static function isSupervisor($userID)
	{
		if(!Loader::includeModule('intranet'))
		{
			return false;
		}

		$dbResult = \CIntranetUtils::GetSubordinateDepartmentsList($userID);
		return is_array($dbResult->Fetch());
	}

	protected static function getSubordinateUserMap($managerID)
	{
		if(!Loader::includeModule('intranet'))
		{
			return array();
		}

		if(self::$subordinateUserMap === null || !isset(self::$subordinateUserMap[$managerID]))
		{
			if(self::$subordinateUserMap === null)
			{
				self::$subordinateUserMap = array();
			}

			if(!isset(self::$subordinateUserMap[$managerID]))
			{
				self::$subordinateUserMap[$managerID] = array();
			}

			$dbResult = \CIntranetUtils::GetSubordinateEmployees($managerID, true, 'N', array('ID'));
			while($ary = $dbResult->fetch())
			{
				self::$subordinateUserMap[$managerID][$ary['ID']] = true;
			}
		}

		return self::$subordinateUserMap[$managerID];
	}

	public static function isSubordinate($employeeID, $managerID)
	{
		if($employeeID === $managerID)
		{
			return false;
		}

		$userMap = self::getSubordinateUserMap($managerID);
		return isset($userMap[$employeeID]);
	}

	/**
	* Check if user is extranet user
	* @param integer $userID User ID
	* @return boolean
	*/
	public static function isExternalUser($userID)
	{
		if(!ModuleManager::isModuleInstalled('extranet'))
		{
			return false;
		}

		$dbResult = \CUser::getList(
			'ID',
			'ASC',
			array('ID_EQUAL_EXACT' => $userID),
			array('FIELDS' => array('ID'), 'SELECT' => array('UF_DEPARTMENT'))
		);

		$user = $dbResult->Fetch();
		return !(is_array($user)
			&& isset($user['UF_DEPARTMENT'])
			&& isset($user['UF_DEPARTMENT'][0])
			&& $user['UF_DEPARTMENT'][0] > 0);
	}

	/**
	 * Return true if intranet custom pages api available.
	 *
	 * @return bool
	 */
	public static function isCustomSectionsAvailable(): bool
	{
		return (
			Loader::includeModule('intranet')
			&& class_exists('Bitrix\\Intranet\\CustomSection\\Manager')
			&& ServiceLocator::getInstance()->has('intranet.customSection.manager')
		);
	}

	/**
	 * Returns array of custom sections that are associated with CRM
	 *
	 * @return CustomSection[]|null
	 */
	public static function getCustomSections(): ?array
	{
		if (!static::isCustomSectionsAvailable())
		{
			return null;
		}

		$sections = static::fetchCustomSections();
		if (empty($sections))
		{
			return $sections;
		}

		static::fillPages($sections);

		return $sections;
	}

	/**
	 * @return CustomSection[]
	 */
	protected static function fetchCustomSections(): array
	{
		$sections = [];
		$list = CustomSectionTable::getList([
			'filter' => [
				'=MODULE_ID' => 'crm',
			],
			'cache' => [
				// cache is valid for one week
				'ttl' => 3600 * 24 * 7,
			],
		]);
		/** @var array $sectionRow */
		while ($sectionRow = $list->fetch())
		{
			$section = CustomSection\Assembler::constructCustomSection($sectionRow);
			$sections[$section->getId()] = $section;
		}

		return $sections;
	}

	/**
	 * @param CustomSection[] $sections
	 */
	protected static function fillPages(array $sections): void
	{
		$list = CustomSectionPageTable::getList([
			'filter' => [
				'=MODULE_ID' => 'crm',
			],
			'cache' => [
				// cache is valid for one week
				'ttl' => 3600 * 24 * 7,
			],
		]);

		/** @var array $pageRow */
		while ($pageRow = $list->fetch())
		{
			$page = CustomSection\Assembler::constructCustomSectionPage($pageRow);
			$section = $sections[$page->getCustomSectionId()] ?? null;

			if ($section)
			{
				$currentPages = $section->getPages();

				$currentPages[$page->getId()] = $page;

				$section->setPages($currentPages);
			}
		}
	}

	/**
	 * Returns page settings for item list of the specified entity type
	 *
	 * @param int $entityTypeId
	 *
	 * @return string
	 */
	public static function preparePageSettingsForItemsList(int $entityTypeId): string
	{
		return $entityTypeId . '_list';
	}

	/**
	 * Extracts entityTypeId from page settings if its possible
	 *
	 * @param string $pageSettings
	 *
	 * @return int|null
	 */
	public static function getEntityTypeIdByPageSettings(string $pageSettings): ?int
	{
		if (preg_match('#^(\d+)_list#', $pageSettings, $matches))
		{
			return (int)$matches[1];
		}

		return null;
	}

	/**
	 * Returns url for a custom section page
	 *
	 * @param string $customSectionCode
	 * @param string $pageCode
	 *
	 * @return Uri|null
	 */
	public static function getUrlForCustomSectionPage(string $customSectionCode, string $pageCode): ?Uri
	{
		if (!static::isCustomSectionsAvailable())
		{
			return null;
		}

		$customSectionManager = ServiceLocator::getInstance()->get('intranet.customSection.manager');

		return $customSectionManager->getUrlForPage($customSectionCode, $pageCode);
	}

	/**
	 * Returns true if the specified entity type is included in a custom section
	 *
	 * @param int $entityTypeId
	 *
	 * @return bool
	 */
	public static function isEntityTypeInCustomSection(int $entityTypeId): bool
	{
		if (!static::isCustomSectionsAvailable())
		{
			return false;
		}

		return in_array($entityTypeId, static::getEntityTypesInCustomSections(), true);
	}

	/**
	 * @return int[]
	 */
	protected static function getEntityTypesInCustomSections(): array
	{
		if (is_array(static::$entityTypesInCustomSections))
		{
			return static::$entityTypesInCustomSections;
		}

		static::$entityTypesInCustomSections = [];
		$customSections = static::getCustomSections();
		foreach ($customSections as $customSection)
		{
			foreach ($customSection->getPages() as $page)
			{
				$entityTypeId = static::getEntityTypeIdByPageSettings((string)$page->getSettings());
				if ($entityTypeId > 0)
				{
					static::$entityTypesInCustomSections[] = $entityTypeId;
				}
			}
		}

		static::$entityTypesInCustomSections = array_unique(static::$entityTypesInCustomSections);

		return static::$entityTypesInCustomSections;
	}

	public static function deleteCustomPagesByEntityTypeId(int $entityTypeId): Result
	{
		$result = new Result();

		if (!static::isCustomSectionsAvailable())
		{
			return $result;
		}

		$sections = static::getCustomSections();
		foreach ($sections as $section)
		{
			$pages = $section->getPages();
			$pagesCount = count($pages);
			foreach ($pages as $page)
			{
				if ($page->getSettings() === static::preparePageSettingsForItemsList($entityTypeId))
				{
					$deletePageResult = CustomSectionPageTable::delete($page->getId());
					if (!$deletePageResult->isSuccess())
					{
						$result->addErrors($deletePageResult->getErrors());
					}
//					elseif ($pagesCount === 1)
//					{
//						$deleteSectionResult = CustomSectionTable::delete($page->getCustomSectionId());
//						if (!$deleteSectionResult->isSuccess())
//						{
//							$result->addErrors($deleteSectionResult->getErrors());
//						}
//					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns first custom section that contains the provided entity type
	 *
	 * @param int $entityTypeId - entity type to find
	 *
	 * @return CustomSection|null
	 */
	public static function getCustomSectionByEntityTypeId(int $entityTypeId): ?CustomSection
	{
		$customSections = static::getCustomSections();
		if (is_null($customSections))
		{
			return null;
		}

		foreach ($customSections as $customSection)
		{
			foreach ($customSection->getPages() as $page)
			{
				$entityTypeIdInPage = static::getEntityTypeIdByPageSettings($page->getSettings());
				if (($entityTypeIdInPage > 0) && ($entityTypeIdInPage === $entityTypeId))
				{
					return $customSection;
				}
			}
		}

		return null;
	}
}
