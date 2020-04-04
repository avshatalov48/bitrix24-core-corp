<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Sale\Location\LocationTable;

class RequisiteMatcher extends BaseRequisiteMatcher
{
	protected function getEntity()
	{
		return EntityRequisite::getSingleInstance();
	}

	protected function normalizeHashArray(array $entity)
	{
		return [
			'RQ_NAME' => isset($entity['RQ_NAME']) ? (string)$entity['RQ_NAME'] : (string)$entity['NAME'],
			'RQ_PRESET_ID' => isset($entity['RQ_PRESET_ID']) ? (int)$entity['RQ_PRESET_ID'] : (int)$entity['PRESET_ID']
		];
	}

	protected function loadExistingEntities()
	{
		$requisites = [];

		$entityTypeId = $this->getEntityTypeId();
		$entityId = $this->getEntityId();

		$addresses = RequisiteAddress::getByEntities($entityTypeId, [$entityId]);

		$requisiteResult = $this->getEntity()->getList([
			'select' => ['*', 'UF_*'],
			'filter' => [
				'=ENTITY_ID' => $entityId,
				'=ENTITY_TYPE_ID' => $entityTypeId
			]
		]);
		foreach ($requisiteResult->fetchAll() as $requisite)
		{
			if (isset($addresses[$entityId][$requisite['ID']]))
			{
				$requisite[EntityRequisite::ADDRESS] = $addresses[$entityId][$requisite['ID']];
			}

			$requisites[] = $requisite;
		}

		return $requisites;
	}

	protected function getLocationTypeToAddressMap()
	{
		return [
			'1' => 'COUNTRY',
			'3' => 'PROVINCE',
			'4' => 'REGION',
			'5' => 'CITY',
			'7' => 'ADDRESS'
		];
	}

	protected function parseLocationAddress($value)
	{
		$addresses = [];

		if (!empty($value))
		{
			$locationMap = $this->getLocationTypeToAddressMap();
			$locationChain = LocationTable::getPathToNodeByCode(
				$value,
				['select' => ['TYPE_ID', 'LOC_NAME' => 'NAME.NAME']]
			);
			foreach ($locationChain as $location)
			{
				if (!empty($locationMap[$location['TYPE_ID']]))
				{
					$addresses[$locationMap[$location['TYPE_ID']]] = $location['LOC_NAME'];
				}
			}
		}

		return $addresses;
	}

	protected function getEntitiesToMatch()
	{
		$requisites = [];

		$requisiteId = $this->getEntityId();

		foreach ($this->properties as $property)
		{
			$fieldCode = $property['CRM_FIELD_CODE'];
			$settings = $property['SETTINGS'];

			if (!empty($settings['RQ_NAME']))
			{
				$requisiteHash = $this->getEntityHash($settings);

				if (!isset($requisites[$requisiteHash]) || !is_array($requisites[$requisiteHash]))
				{
					$requisites[$requisiteHash] = [
						'NAME' => $settings['RQ_NAME'],
						'PRESET_ID' => $settings['RQ_PRESET_ID'],
						'ENTITY_ID' => $requisiteId,
						'ENTITY_TYPE_ID' => $property['CRM_ENTITY_TYPE']
					];
				}

				if ($fieldCode === EntityRequisite::ADDRESS)
				{
					if ($settings['RQ_ADDR_CODE'] === 'LOCATION')
					{
						if (!is_array($requisites[$requisiteHash][$fieldCode][$settings['RQ_ADDR_TYPE']]))
						{
							$requisites[$requisiteHash][$fieldCode][$settings['RQ_ADDR_TYPE']] = [];
						}

						$requisites[$requisiteHash][$fieldCode][$settings['RQ_ADDR_TYPE']] += $this->parseLocationAddress($property['VALUE']);
					}
					elseif (!empty($property['VALUE']))
					{
						$requisites[$requisiteHash][$fieldCode][$settings['RQ_ADDR_TYPE']][$settings['RQ_ADDR_CODE']] = $property['VALUE'];
					}
				}
				else
				{
					$requisites[$requisiteHash][$fieldCode] = $property['VALUE'];
				}
			}
		}

		return array_values($requisites);
	}
}