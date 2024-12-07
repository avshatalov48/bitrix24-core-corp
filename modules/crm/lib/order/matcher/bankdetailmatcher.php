<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;

class BankDetailMatcher extends BaseRequisiteMatcher
{
	protected $requisites = null;

	protected function getEntity()
	{
		return EntityBankDetail::getSingleInstance();
	}

	protected function getRequisiteEntity()
	{
		return EntityRequisite::getSingleInstance();
	}

	public function setMatchedRequisites(array $requisites)
	{
		$this->requisites = $requisites;
	}

	protected function normalizeHashArray(array $entity)
	{
		return [
			'RQ_NAME' => isset($entity['RQ_NAME']) ? (string)$entity['RQ_NAME'] : (string)$entity['NAME'],
			'RQ_PRESET_ID' => isset($entity['RQ_PRESET_ID']) ? (int)$entity['RQ_PRESET_ID'] : (int)$entity['PRESET_ID'],
			'NAME' => isset($entity['BD_NAME']) ? (string)$entity['BD_NAME'] : (string)$entity['NAME'],
			'COUNTRY_ID' => isset($entity['BD_COUNTRY_ID']) ? (int)$entity['BD_COUNTRY_ID'] : (int)$entity['COUNTRY_ID']
		];
	}

	protected function loadRequisites()
	{
		$requisites = [];

		$requisiteResult = EntityRequisite::getSingleInstance()->getList([
			'select' => ['ID', 'NAME', 'PRESET_ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->getEntityTypeId(),
				'=ENTITY_ID' => $this->getEntityId()
			]
		]);
		foreach ($requisiteResult->fetchAll() as $requisite)
		{
			$requisites[$requisite['ID']] = $requisite;
		}

		return $requisites;
	}

	protected function getRequisites()
	{
		if ($this->requisites === null)
		{
			$this->requisites = $this->loadRequisites();
		}

		return $this->requisites;
	}

	protected function createBlankRequisite($settings)
	{
		return $this->getRequisiteEntity()->add(
			[
				'NAME' => $settings['RQ_NAME'],
				'PRESET_ID' => $settings['RQ_PRESET_ID'],
				'ENTITY_ID' => $this->getEntityId(),
				'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			],
			['DISABLE_REQUIRED_USER_FIELD_CHECK' => true]
		);
	}

	protected function loadExistingEntities()
	{
		$bankDetails = [];

		$requisites = $this->getRequisites();

		if (!empty($requisites))
		{
			$bankDetailResult = $this->getEntity()->getList([
				'filter' => [
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
					'ENTITY_ID' => array_keys(array_column($requisites, null, 'ID'))
				]
			]);
			foreach ($bankDetailResult->fetchAll() as $bankDetail)
			{
				$bankDetail['RQ_NAME'] = $requisites[$bankDetail['ENTITY_ID']]['NAME'];
				$bankDetail['RQ_PRESET_ID'] = $requisites[$bankDetail['ENTITY_ID']]['PRESET_ID'];

				$bankDetails[] = $bankDetail;
			}
		}

		return $bankDetails;
	}

	protected function getEntitiesToMatch()
	{
		$bankDetails = [];

		$requisites = $this->getRequisites();

		foreach ($this->properties as $property)
		{
			$settings = $property['SETTINGS'];

			if (!empty($settings['BD_NAME']))
			{
				$bankDetailHash = $this->getEntityHash($settings);

				if (!isset($bankDetails[$bankDetailHash]) || !is_array($bankDetails[$bankDetailHash]))
				{
					$requisiteId = null;

					foreach ($requisites as $requisite)
					{
						if (
							$settings['RQ_NAME'] === $requisite['NAME']
							&& (int)$settings['RQ_PRESET_ID'] === (int)$requisite['PRESET_ID']
						)
						{
							$requisiteId = $requisite['ID'];
							break;
						}
					}

					if (empty($requisiteId))
					{
						$result = $this->createBlankRequisite($settings);

						if ($result->isSuccess())
						{
							$requisiteId = $result->getId();
						}
						else
						{
							continue;
						}
					}

					$bankDetails[$bankDetailHash] = [
						'NAME' => $settings['BD_NAME'],
						'COUNTRY_ID' => $settings['BD_COUNTRY_ID'],
						'ENTITY_ID' => $requisiteId,
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite
					];
				}

				$bankDetails[$bankDetailHash][$property['CRM_FIELD_CODE']] = $property['VALUE'];
			}
		}

		return array_values($bankDetails);
	}
}