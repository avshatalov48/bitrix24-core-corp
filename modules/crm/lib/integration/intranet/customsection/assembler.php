<?php

namespace Bitrix\Crm\Integration\Intranet\CustomSection;

use Bitrix\Crm\Integration\Intranet\CustomSection;

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
	 * @return CustomSection\Page
	 */
	public static function constructCustomSectionPage(array $row): CustomSection\Page
	{
		$page = new CustomSection\Page();

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
}
