<?php

namespace Bitrix\Crm\AutomatedSolution\Support;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage;
use Bitrix\Main\Loader;

/**
 * @internal Not covered by backwards compatibility
 */
final class IntranetQueries
{
	private function __construct()
	{
	}

	public static function getPageByEntityTypeId(int $entityTypeId): ?EO_CustomSectionPage
	{
		if (!IntranetManager::isCustomSectionsAvailable())
		{
			return null;
		}

		return CustomSectionPageTable::query()
			->setSelect(['*'])
			->where('MODULE_ID', AutomatedSolutionManager::MODULE_ID)
			->where('SETTINGS', IntranetManager::preparePageSettingsForItemsList($entityTypeId))
			->fetchObject()
		;
	}
}
