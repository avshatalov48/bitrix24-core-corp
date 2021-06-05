<?php

namespace Bitrix\Intranet\CustomSection\DataStructures;

use Bitrix\Intranet\CustomSection\Entity\EO_CustomSection;
use Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Values;

class Assembler
{
	/**
	 * Assemble a DTO CustomSection object from a DB row
	 *
	 * @param array $row
	 *
	 * @return CustomSection
	 */
	public static function constructCustomSection(array $row): CustomSection
	{
		$customSection = new CustomSection();

		if (isset($row['ID']))
		{
			$customSection->setId((int)$row['ID']);
		}
		if (isset($row['CODE']))
		{
			$customSection->setCode((string)$row['CODE']);
		}
		if (isset($row['TITLE']))
		{
			$customSection->setTitle((string)$row['TITLE']);
		}
		if (isset($row['MODULE_ID']))
		{
			$customSection->setModuleId((string)$row['MODULE_ID']);
		}
		if (isset($row['PAGES']) && is_array($row['PAGES']))
		{
			$pages = [];
			foreach ($row['PAGES'] as $pageRow)
			{
				$pages[] = static::constructCustomSectionPage($pageRow);
			}

			$customSection->setPages($pages);
		}

		return $customSection;
	}

	/**
	 * Assemble a DTO CustomSectionPage object from a DB row
	 *
	 * @param array $row
	 *
	 * @return CustomSectionPage
	 */
	public static function constructCustomSectionPage(array $row): CustomSectionPage
	{
		$page = new CustomSectionPage();

		if (isset($row['ID']))
		{
			$page->setId((int)$row['ID']);
		}
		if (isset($row['CUSTOM_SECTION_ID']))
		{
			$page->setCustomSectionId((int)$row['CUSTOM_SECTION_ID']);
		}
		if (isset($row['CODE']))
		{
			$page->setCode((string)$row['CODE']);
		}
		if (isset($row['TITLE']))
		{
			$page->setTitle((string)$row['TITLE']);
		}
		if (isset($row['MODULE_ID']))
		{
			$page->setModuleId((string)$row['MODULE_ID']);
		}
		if (isset($row['SORT']))
		{
			$page->setSort((int)$row['SORT']);
		}
		if (isset($row['SETTINGS']))
		{
			$page->setSettings((string)$row['SETTINGS']);
		}

		return $page;
	}

	/**
	 * Assemble a DTO CustomSection object from a EntityObject
	 *
	 * @param EO_CustomSection $entityObject
	 *
	 * @return CustomSection
	 */
	public static function constructCustomSectionFromEntityObject(EO_CustomSection $entityObject): CustomSection
	{
		$data = $entityObject->collectValues(Values::ALL, FieldTypeMask::SCALAR);

		if (!is_null($entityObject->getPages()))
		{
			$pages = [];
			foreach ($entityObject->getPages() as $page)
			{
				$pages[] = $page->collectValues();
			}

			$data['PAGES'] = $pages;
		}

		return static::constructCustomSection($data);
	}

	/**
	 * Assemble a DTO CustomSectionPage object from a EntityObject
	 *
	 * @param EO_CustomSectionPage $entityObject
	 *
	 * @return CustomSectionPage
	 */
	public static function constructCustomSectionPageFromEntityObject(
		EO_CustomSectionPage $entityObject
	): CustomSectionPage
	{
		return static::constructCustomSectionPage($entityObject->collectValues(Values::ALL, FieldTypeMask::SCALAR));
	}
}
