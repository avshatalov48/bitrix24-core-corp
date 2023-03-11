<?php

namespace Bitrix\CrmMobile\Kanban;

use Bitrix\Crm\Component\EntityList\ClientDataProvider\KanbanDataProvider;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Crm\Multifield\Type\Im;

class ClientDataProvider extends KanbanDataProvider
{
	protected function getAdditionalClientInfo(array $clientInfo): array
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);

		$multifields = ($clientInfo['MULTIFIELDS'] ?? []);
		$hidden = !$clientInfo[$entityTypeName . '_IS_ACCESSIBLE'];

		$contactData = [];

		foreach ($multifields as $multiValue)
		{
			$contactKey = mb_strtolower($multiValue['TYPE_ID']);
			if (trim($multiValue['VALUE']))
			{
				$contactData[$contactKey][] = [
					'value' => $multiValue['VALUE'],
					'complexName' => $multiValue['COMPLEX_NAME'],
					'valueType' => $multiValue['VALUE_TYPE'],
				];
			}
		}

		return array_merge(
			$contactData, [
				'id' => $clientInfo[$entityTypeName . '_ID'] ?? null,
				'subtitle' => '',
				'title' => $this->formatTitle($clientInfo),
				'type' => strtolower($entityTypeName),
				'hidden' => $hidden,
			]
		);
	}

	protected function getPreparedMultifieldInfoValues(Result $items, string $entityName): array
	{
		$result = [];

		while ($multifield = $items->fetch())
		{
			$clientId = $multifield['ELEMENT_ID'];
			$multifield['COMPLEX_NAME'] = $this->multifieldsManager::GetEntityNameByComplex(
				$multifield['COMPLEX_ID'],
				false
			);
			$result[$clientId]['MULTIFIELDS'][] = $multifield;
		}

		return $result;
	}
}
