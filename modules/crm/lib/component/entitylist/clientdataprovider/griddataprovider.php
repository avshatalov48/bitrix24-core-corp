<?php

namespace Bitrix\Crm\Component\EntityList\ClientDataProvider;

class GridDataProvider extends \Bitrix\Crm\Component\EntityList\ClientDataProvider
{
	/**
	 * Get extra grid headers for client fields
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		if (!$this->hasPermissions())
		{
			return [];
		}

		$headers = array_merge(
			$this->getBaseHeaders(),
			$this->getMultifieldsHeaders(),
			$this->getUfHeaders()
		);
		if (!$this->isExportMode)
		{
			$entityName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);
			$iconUrl = '/bitrix/images/crm/grid_icons/' . strtolower($entityName) . '.svg';
			$iconTitle = $this->fieldHelper->getEntityTitle();
			foreach ($headers as $id => $header)
			{
				$headers[$id]['iconUrl'] = $iconUrl;
				$headers[$id]['iconTitle'] = $iconTitle;

				$headers[$id]['section_id'] = $entityName;
			}
		}

		return $headers;
	}

	/**
	 * Add values of client fields to $deals result in grid compatible format
	 *
	 * @param array $deals
	 */
	public function appendResult(array &$deals): void
	{
		$clientFieldId = $this->fieldHelper->addPrefixToFieldId(self::ID_FIELD);
		if (empty($this->realSelectFields))
		{
			if ($this->addRawIdToSelect)
			{
				$this->addRawIdToResult($deals);
			}

			return;
		}

		$isAccessibleFieldId = $this->fieldHelper->addPrefixToFieldId('IS_ACCESSIBLE');

		$clientIds = $this->extractClientIds($deals, $clientFieldId);
		if (empty($clientIds))
		{
			return;
		}
		$clientsInfo = $this->loadClientsInfo($clientIds);

		$rawIdFieldId = $this->fieldHelper->addPrefixToFieldId(self::RAW_ID_FIELD);
		foreach ($deals as $dealId => &$deal)
		{
			$clientId = $deal[$clientFieldId];
			if ($this->addRawIdToSelect && $clientId > 0)
			{
				$deal[$rawIdFieldId] = $clientId;
			}
			if ($clientId > 0 && isset($clientsInfo[$clientId]))
			{
				$deal = array_merge(
					$deal,
					$clientsInfo[$clientId]
				);
				if ($deal[$isAccessibleFieldId])
				{
					$normalized = $this->ufManager->normalizeBooleanValues([$dealId => $deal]);
					$deal = $normalized[$dealId];
				}
			}
		}
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
				'SOURCE_DESCRIPTION' => [
					'sort' => false,
				],
				'EXPORT' => [
					'type' => 'checkbox',
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
}
