<?php

namespace Bitrix\Crm\FieldSet;

use Bitrix\Crm\Requisite\Country;
use Bitrix\Crm\WebForm\EntityFieldProvider;
use Bitrix\Main;
use Bitrix\Crm\Model;
use Bitrix\Crm\EntityRequisite;
use CCrmOwnerType;

class Factory
{
	private const MAX_REQUISITES_FIELDS_COUNT = 3;
	
	public function list(): array
	{
		$rows = Model\FieldSetTable::query()
			->setOrder(['ID' => 'DESC'])
			->fetchAll()
		;
		return $this->createItems($rows);
	}

	public function getItem(int $id): ?Item
	{
		return $this->createItems([Model\FieldSetTable::getRowById($id)])[0] ?? null;
	}

	public function getItemByCode(string $code): ?Item
	{
		return $this->createItems([$this->fetchByCode($code)])[0] ?? null;
	}

	public function getItemByEntityType(
		int $entityTypeId,
		int $clientEntityTypeId
	): ?Item
	{
		$row = Model\FieldSetTable::getFieldSet($entityTypeId, $clientEntityTypeId);
		return $this->createItems([$row])[0] ?? null;
	}

	public function installDefaults(?int $presetId = null): Main\Result
	{
		$result = new Main\Result();
		$items = [];
		foreach ($this->makeDefaultItems($presetId) as $item)
		{
			$saveResult = $this->save($item);
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
				break;
			}

			$items[] = $item;
		}

		$result->setData($items);
		return $result;
	}

	public function deleteItem(Item $item): bool
	{
		$result = Model\FieldSetTable::delete(['ID' => $item->getId(),]);
		return $result->isSuccess();
	}

	public function save(Item $item): Main\Result
	{
		$result = new Main\Result();
		if (!CCrmOwnerType::isCorrectEntityTypeId($item->getEntityTypeId()))
		{
			$result->addError(new Main\Error('Wrong primary entity type ID.'));
			return $result;
		}

		if (!in_array($item->getClientEntityTypeId(), [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
		{
			$result->addError(new Main\Error('Wrong client entity type ID.'));
			return $result;
		}

		$data = [
			'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
			'CLIENT_ENTITY_TYPE_ID' => $item->getClientEntityTypeId(),
			'RQ_PRESET_ID' => $item->getRequisitePresetId(),
			'FIELDS' => $item->getFields(),
			'CODE' => $item->getCode(),
			'IS_SYSTEM' => (int)$item->isSystem(),
			//'TITLE' => '',
		];

		if (!$item->getId())
		{
			$result = Model\FieldSetTable::add($data);
			if ($result->isSuccess())
			{
				$item->setId($result->getId());
			}
		}
		else
		{
			unset($data['CODE'], $data['IS_SYSTEM']);
			$result = Model\FieldSetTable::update($item->getId(), $data);
		}

		return $result;
	}

	private function fetchByCode(string $code): ?array
	{
		return Model\FieldSetTable::query()
			->setSelect(['*'])
			->where('CODE', $code)
			->fetch()
			?: null
		;
	}

	private function makeDefaultItems(?int $presetId = null): array
	{
		/** @var Item[] $items */
		$items = [];
		$code = 'def-req-' . \CCrmOwnerType::Company . ($presetId ? '-'. $presetId : '');

		if (!$this->fetchByCode($code))
		{
			$items[] = (new Item)
				->setCode($code)
				->setEntityTypeId(CCrmOwnerType::SmartDocument)
				->setClientEntityTypeId(\CCrmOwnerType::Company)
				->setSystem(true)
			;
		}

		$code = 'def-req-' . \CCrmOwnerType::Contact . ($presetId ? '-'. $presetId : '');
		if (!$this->fetchByCode($code))
		{
			$items[] = (new Item)
				->setCode($code)
				->setEntityTypeId(CCrmOwnerType::SmartDocument)
				->setClientEntityTypeId(\CCrmOwnerType::Contact)
				->setSystem(true)
			;
		}

		$result = [];
		foreach ($items as $item)
		{
			$presetEntityTypeId = $item->getClientEntityTypeId() ? CCrmOwnerType::Company : 0;
			if (!$presetEntityTypeId)
			{
				continue;
			}

			$rqPresetId = $presetId ?: (EntityRequisite::getDefaultPresetId($presetEntityTypeId) ?: 0);
			if (!$rqPresetId)
			{
				EntityRequisite::installDefaultPresets();
				$rqPresetId =  $presetId ?: (EntityRequisite::getDefaultPresetId($presetEntityTypeId) ?: 0);
			}

			if (!$rqPresetId)
			{
				continue;
			}

			$fields = [];
			$countryId = EntityRequisite::getSingleInstance()->getCountryIdByPresetId($rqPresetId);
			$fieldsMap = $this->getDefaultItemRegionFieldMap();
			$regionFields = $fieldsMap[$countryId] ?? $fieldsMap[Country::ID_USA];
			foreach ([$item->getEntityTypeId(), $item->getClientEntityTypeId()] as $typeId)
			{
				$fields = array_merge($fields, $regionFields[$typeId] ?? []);
			}

			$typeName = CCrmOwnerType::resolveName($typeId);
			
			$prepareFieldsToInsert = function (string $name) use ($typeName)
			{
				return [
					'name' => "{$typeName}_{$name}",
					'required' => true,
					'multiple' => false,
				];
			};
			
			$item
				->setRequisitePresetId($rqPresetId)
				->setFields(array_map(
					$prepareFieldsToInsert,
					$fields
				))
			;
			
			if (empty($item->getFields()))
			{
				$this->setDefaultFieldsForItems($prepareFieldsToInsert, $typeId, $item);
			}

			$result[] = $item;
		}


		return $result;
	}

	private function getDefaultItemFieldMap(): array
	{
		return [
			CCrmOwnerType::Contact => [
				'NAME',
				'LAST_NAME',
				'PHONE',
				'EMAIL',
				'RQ_ADDR_PRIMARY'
			],
			CCrmOwnerType::Company => [
				'TITLE',
				'EMAIL',
				'PHONE',
				'RQ_ADDR_PRIMARY',
			],
		];
	}
	private function getDefaultItemRegionFieldMap(): array
	{
		return [
			Country::ID_GERMANY => [
				CCrmOwnerType::Contact => [
					'RQ_ADDR_PRIMARY',
					'RQ_COMPANY_NAME',
					'NAME',
					'LAST_NAME',
					'POST',
				],
				CCrmOwnerType::Company => [
					'RQ_ADDR_PRIMARY',
					'RQ_COMPANY_NAME',
				],
			],
			Country::ID_POLAND => [
				CCrmOwnerType::Contact => [
					'POST',
					'NAME',
					'LAST_NAME',
					'RQ_COMPANY_NAME',
					'RQ_ADDR_PRIMARY',
					'PHONE',
					'RQ_INN',
					'RQ_REGION',
					'EMAIL',
					'WEB',
				],
				CCrmOwnerType::Company => [
					'RQ_COMPANY_NAME',
					'RQ_ADDR_PRIMARY',
					'PHONE',
					'RQ_INN',
					'RQ_REGION',
					'EMAIL',
					'WEB',
				],
			],
			Country::ID_COLOMBIA => [
				CCrmOwnerType::Contact => [
					'NAME',
					'LAST_NAME',
					'POST',
					'RQ_COMPANY_NAME',
				],
				CCrmOwnerType::Company => [
					'RQ_COMPANY_NAME',
				],
			],
			Country::ID_USA => [
				CCrmOwnerType::Contact => [
					'RQ_COMPANY_NAME',
					'NAME',
					'LAST_NAME',
					'POST',
				],
				CCrmOwnerType::Company => [
					'RQ_COMPANY_NAME',
				],
			],
			Country::ID_RUSSIA => [
				CCrmOwnerType::Contact => [
					'RQ_COMPANY_NAME',
					'PHONE',
					'WEB',
					'EMAIL',
					'RQ_INN',
					'RQ_OGRN',
					'RQ_KPP',
					'RQ_ACC_NUM',
					'RQ_BANK_NAME',
					'RQ_BIC',
					'RQ_COR_ACC_NUM',
					'RQ_ADDR_REGISTERED',
					'RQ_ADDR_PRIMARY',
				],
				CCrmOwnerType::Company => [
					'RQ_COMPANY_NAME',
					'PHONE',
					'WEB',
					'EMAIL',
					'RQ_INN',
					'RQ_OGRN',
					'RQ_KPP',
					'RQ_ACC_NUM',
					'RQ_BANK_NAME',
					'RQ_BIC',
					'RQ_COR_ACC_NUM',
					'RQ_ADDR_REGISTERED',
					'RQ_ADDR_PRIMARY',
				],
			],
		];
	}

	private function createItems(array $rows): array
	{
		$list = [];

		foreach ($rows as $row)
		{
			if (!$row)
			{
				continue;
			}

			$list[] = (new Item())
				->setId((int)($row['ID'] ?? 0))
				->setCode($row['CODE'] ?? null)
				->setEntityTypeId((int)($row['ENTITY_TYPE_ID'] ?? 0))
				->setClientEntityTypeId((int)($row['CLIENT_ENTITY_TYPE_ID'] ?? 0))
				->setRequisitePresetId((int)($row['RQ_PRESET_ID'] ?? 0))
				->setFields($row['FIELDS'] ?? [])
			;
		}

		return $list;
	}
	
	/**
	 * @param \Closure $prepareFieldsToInsert
	 * @param int $typeId
	 * @param Item $item
	 * @return void
	 */
	private function setDefaultFieldsForItems(\Closure $prepareFieldsToInsert, int $typeId, Item $item): void
	{
		$presetFields = EntityFieldProvider::getFields([], $item->getRequisitePresetId());
		$defaultItemFields = array_map(
			$prepareFieldsToInsert,
			$this->getDefaultItemFieldMap()[$typeId]
		);
		
		$fields = [];
		$fieldCounter = 0;
		foreach ($presetFields as $presetField) {
			if (mb_strpos($presetField['entity_field_name'], 'RQ_') === false
				|| !in_array($presetField['type'], ['string', 'typed_string'])
				|| $fieldCounter >= self::MAX_REQUISITES_FIELDS_COUNT
			) {
				continue;
			}
			
			$fieldCounter++;
			$fields[] = $presetField;
		}
		
		$fields = array_merge($fields, $defaultItemFields ?? []);
		$item->setFields($fields);
	}
}
