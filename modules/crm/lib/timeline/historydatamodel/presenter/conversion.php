<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Localization\Loc;

class Conversion extends Presenter
{
	/** @var \CCrmOwnerType */
	protected $crmOwnerType = \CCrmOwnerType::class;

	protected function getHistoryTitle(string $fieldName = null): string
	{
		return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_CONVERSION_TITLE');
	}

	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		$entities = (array)($settings['ENTITIES'] ?? null);

		$resultingEntitiesInfo = [];
		foreach ($entities as $entity)
		{
			$entityTypeId = (int)($entity['ENTITY_TYPE_ID'] ?? null);
			$entityId = (int)($entity['ENTITY_ID'] ?? null);

			if (($entityId > 0) && $this->crmOwnerType::IsDefined($entityTypeId))
			{
				$resultingEntitiesInfo[] = $this->getEntityInfo($entityTypeId, $entityId);
			}
		}

		$data['ENTITIES'] = $resultingEntitiesInfo;

		return $data;
	}

	protected function getEntityInfo(int $entityTypeId, int $entityId): array
	{
		$targetEntityImplementation =
			Container::getInstance()->getTimelineHistoryDataModelMaker()->getEntityImplementation($entityTypeId);

		return $targetEntityImplementation->getEntityInfo($entityId);
	}
}
