<?php

namespace Bitrix\Crm\Integration\Intranet\CustomSection;

use Bitrix\Crm\Integration;
use Bitrix\Crm\Traits;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\Entity\ReferenceField;

final class CustomSectionQueries
{
	use Traits\Singleton;

	public function findByEntityTypeIds(array $entityTypeIds): array
	{
		if (!Integration\IntranetManager::isCustomSectionsAvailable())
		{
			return [];
		}

		$customSections = [];
		$settingsCodes = array_map(
			fn ($typeId) => Integration\IntranetManager::preparePageSettingsForItemsList($typeId),
			$entityTypeIds
		);

		$sections = CustomSectionPageTable::query()
			->setSelect(['ID', 'SETTINGS', 'CUSTOM_SECTION_ID'])
			->addSelect('cs.TITLE', 'SECTION_TITLE')
			->registerRuntimeField(
				'',
				new ReferenceField('cs',
					CustomSectionTable::getEntity(),
					['=ref.ID' => 'this.CUSTOM_SECTION_ID']
				)
			)
			->where('MODULE_ID', 'crm')
			->whereIn('SETTINGS', $settingsCodes)
			->fetchAll();

		foreach ($sections as $row)
		{
			$typeId = Integration\IntranetManager::getEntityTypeIdByPageSettings($row['SETTINGS']);
			$row['TITLE'] = $row['S_TITLE'] ?? null;
			$customSections[$typeId] = $row;
		}

		return $customSections;
	}

	public function findAllRelatedByCrmType(): array
	{
		return CustomSectionPageTable::query()
			->setSelect(['ID', 'SETTINGS', 'CUSTOM_SECTION_ID'])
			->addSelect('cs.TITLE', 'SECTION_TITLE')
			->registerRuntimeField(
				'',
				new ReferenceField('cs',
					CustomSectionTable::getEntity(),
					['=ref.ID' => 'this.CUSTOM_SECTION_ID']
				)
			)
			->where('MODULE_ID', 'crm')
			->whereLike('SETTINGS', '%_list')
			->fetchAll();
	}

	public function findSettingsById(int $customSectionId): array
	{
		return CustomSectionPageTable::query()
			->setSelect(['SETTINGS'])
			->registerRuntimeField(
				'',
				new ReferenceField('cs',
					CustomSectionTable::getEntity(),
					['=ref.ID' => 'this.CUSTOM_SECTION_ID']
				)
			)
			->where('cs.ID', $customSectionId)
			->fetchAll();
	}

}
