<?php

namespace Bitrix\Crm\Component\EntityList\ClientDataProvider;

class KanbanDataProvider extends \Bitrix\Crm\Component\EntityList\ClientDataProvider
{
	public function getPopupFields(): array
	{
		if (!$this->hasPermissions())
		{
			return [];
		}

		$headers = array_merge(
			$this->getBaseHeaders(),
			$this->getUfHeaders()
		);
		$result = [];

		foreach ($headers as $header)
		{
			$result[$header['id']] = [
				'ID' => 'field_' . $header['id'],
				'TYPE' => isset($header['type']) ? strtoupper($header['type']) : 'STRING',
				'NAME' => $header['id'],
				'LABEL' => htmlspecialcharsback($header['name']), // will be escaped by frontend
			];
		}

		return $result;
	}

	/**
	 * Add values of client fields to $items result in kanban compatible format
	 *
	 * @param array $items
	 * @param array $fieldsToAdd
	 */
	public function appendResult(array &$items, array $fieldsToAdd): void
	{
		if (empty($this->realSelectFields))
		{
			if ($this->addRawIdToSelect)
			{
				$this->addRawIdToResult($deals);
			}
			return;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);
		$fieldPrefix = strtolower($entityTypeName);
		$clientFieldId = $this->fieldHelper->addPrefixToFieldId(self::ID_FIELD);
		$clientIds = $this->extractClientIds($items, $clientFieldId);
		if (empty($clientIds))
		{
			return;
		}
		$clientsInfo = $this->loadClientsInfo($clientIds);

		$rawIdFieldId = $this->fieldHelper->addPrefixToFieldId(self::RAW_ID_FIELD);

		$itemsWithClientsInfo = $items;
		foreach ($itemsWithClientsInfo as $id => $item)
		{
			$clientId = $item[$clientFieldId];
			if ($this->addRawIdToSelect && $clientId > 0)
			{
				$item[$rawIdFieldId] = $clientId;
			}
			if ($clientId > 0 && isset($clientsInfo[$clientId]))
			{
				$item = array_merge(
					$item,
					$clientsInfo[$clientId]
				);
			}
			$itemsWithClientsInfo[$id] = $item;
		}

		$allFields = $this->getPopupFields();

		foreach ($items as $id => $item)
		{
			$clientId = $item[$clientFieldId];
			if ($clientId > 0 && isset($clientsInfo[$clientId]))
			{
				$title = $this->formatTitle($clientsInfo[$clientId]);
				$items[$id][$fieldPrefix . 'Name'] = htmlspecialcharsbx($title);

				if ($clientsInfo[$clientId][$entityTypeName . '_IS_ACCESSIBLE'])
				{
					$items[$id][$fieldPrefix . 'Tooltip'] = \CCrmViewHelper::PrepareEntityBaloonHtml([
						'ENTITY_TYPE_ID' => $this->clientEntityTypeId,
						'ENTITY_ID' => $item[$clientFieldId],
						'TITLE' => $title,
						'PREFIX' => $entityTypeName . '_' . $item['ID'],
					]);
				}
				else
				{
					$items[$id][$fieldPrefix . 'Tooltip'] = $items[$id][$fieldPrefix . 'Name'];
				}

				$items[$id]['ADDITIONAL_' . $entityTypeName . '_INFO'] = $this->getAdditionalClientInfo($clientsInfo[$clientId]);

				foreach ($fieldsToAdd as $fieldId)
				{
					if (
						isset($allFields[$fieldId])
						&& mb_strpos($fieldId, $this->fieldHelper->getFieldPrefix()) === 0
					)
					{
						$value = $itemsWithClientsInfo[$id][$fieldId] ?? '';
						if ($value !== '' && $value !== [])
						{
							$items[$id][$fieldId] = $value;
						}
					}
				}
			}
			else
			{
				$items[$id][$clientFieldId] = null;
			}
		}
	}

	protected function getAdditionalClientInfo(array $clientInfo): array
	{
		return [];
	}

	protected function getBaseFields(): array
	{
		$fields = [
			\CCrmOwnerType::Contact => [
				'PHOTO' => [
					'sort' => false,
				],
				'HONORIFIC' => [
					'sort' => false,
					'type' => 'list',
				],
				'NAME' => [],
				'LAST_NAME' => [],
				'SECOND_NAME' => [],
				'FULL_NAME' => [],
				'BIRTHDATE' => [
					'first_order' => 'desc',
					'type' => 'date',
				],
				'POST' => [],
				'TYPE_ID' => [
					'type' => 'list',
				],
				'ASSIGNED_BY_ID' => [
					'class' => 'username',
				],
				'COMMENTS' => [
					'sort' => false,
				],
				'SOURCE_ID' => [
					'type' => 'list',
				],
				'CREATED_BY_ID' => [],
				'DATE_CREATE' => [
					'first_order' => 'desc',
					'type' => 'date',
				],
				'MODIFY_BY_ID' => [],
				'DATE_MODIFY' => [
					'first_order' => 'desc',
					'type' => 'date',
				],
				'WEBFORM_ID' => [
					'type' => 'list',
				],
			],
			\CCrmOwnerType::Company => [
				'LOGO' => [
					'sort' => false,
				],
				'TITLE' => [],
				'COMPANY_TYPE' => [
					'type' => 'list',
				],
				'EMPLOYEES' => [
					'type' => 'list',
					'first_order' => 'desc',
				],
				'ASSIGNED_BY_ID' => [],
				'BANKING_DETAILS' => [
					'sort' => false,
				],
				'INDUSTRY' => [
					'type' => 'list',
				],
				'REVENUE' => [],
				'CURRENCY_ID' => [
					'type' => 'list',
				],
				'COMMENTS' => [
					'sort' => false,
				],
				'CREATED_BY_ID' => [],
				'DATE_CREATE' => [
					'first_order' => 'desc',
					'type' => 'date',
				],
				'MODIFY_BY_ID' => [],
				'DATE_MODIFY' => [
					'first_order' => 'desc',
					'type' => 'date',
				],
				'WEBFORM_ID' => [
					'type' => 'list',
				],
			],
		];

		return $fields[$this->clientEntityTypeId];
	}

	public function prepareFieldValue(string $fieldId, string $value): string
	{
		// all fields values will be prepared directly into kanban entity
		return $value;
	}
}
