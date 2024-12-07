<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;

final class Client extends Base
{
	public const TYPE_NAME = 'client';

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		$selectedClients = $this->blockData['selectedClients'] ?? [];
		if (empty($selectedClients))
		{
			return;
		}

		$fields = [
			'COMMUNICATIONS' => [],
			'UF_CRM_CAL_EVENT' => [],
		];

		$settings = $fields['SETTINGS'] ?? [];
		$settings['CLIENTS'] = [];

		foreach ($selectedClients as $client)
		{
			$entityId = (int)$client['entityId'];
			$entityTypeId = $client['entityTypeId'];

			$settings['CLIENTS'][] = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
			];

			$fields['COMMUNICATIONS'][] = [
				'ID' => 0,
				'TYPE' => '',
				'VALUE' => '',
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
			];

			$entityTypeName = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
			$fields['UF_CRM_CAL_EVENT'][] = $entityTypeName . '_' . $entityId;
		}

		$fields['SETTINGS'] = $settings;

		$entity->appendAdditionalFields($fields);
	}

	public function fetchSettings(): array
	{
		$items = $this->activityData['settings']['CLIENTS'] ?? null;

		$result = [];

		if (empty($items))
		{
			return $result;
		}

		$hasAvailableClients = false;
		$clients = [];
		foreach ($items as $item)
		{
			$entityTypeId = (int)$item['ENTITY_TYPE_ID'];
			$entityId = (int)$item['ENTITY_ID'];

			$isAvailable = (
				$entityTypeId === \CCrmOwnerType::Contact
				|| $entityTypeId === \CCrmOwnerType::Company
			);

			$clients[] = [
				'entityId' => $entityId,
				'entityTypeId' => $entityTypeId,
				'isAvailable' => $isAvailable,
			];

			if ($isAvailable)
			{
				$hasAvailableClients = true;
			}
		}

		if (!empty($clients))
		{
			$result['clients'] = $clients;
			$result['active'] = $hasAvailableClients;
		}

		return $result;
	}
}
