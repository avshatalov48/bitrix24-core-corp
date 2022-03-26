<?php

namespace Bitrix\Mobile\Integration\Catalog\StoreDocumentList;

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Mobile\Integration\Catalog\EntityEditor\StoreDocumentProvider;
use Bitrix\Catalog\EO_StoreDocument;

class Item
{
	protected const NOT_CONDUCTED = 'N';
	protected const CONDUCTED = 'Y';
	protected const CANCELLED = 'C';

	protected const SUM_FIELD_ID = 'TOTAL_WITH_CURRENCY';
	protected const CONTRACTOR_FIELD_ID = 'CONTRACTOR_ID';
	protected const STATUS_FIELD_ID = 'DOC_STATUS';

	protected $document = [];

	public function __construct($document)
	{
		$this->document = $document;
	}

	public function prepareItem(): array
	{
		$document = $this->document;

		$this->prepareContractor($document);
		$this->prepareResponsible($document);

		$data = [];
		if (isset($document['CONTRACTOR_PERSON_NAME']))
		{
			$data['CONTRACTOR_PERSON_NAME'] = $document['CONTRACTOR_PERSON_NAME'];
		}
		if (isset($document['CONTRACTOR_PERSON_NAME']))
		{
			$data['CONTRACTOR_COMPANY'] = $document['CONTRACTOR_COMPANY'];
		}

		$dp = StoreDocumentProvider::createByArray($document, [
			'skipFiles' => true,
			'skipProducts' => true,
			'skipUsers' => isset($document['USER_INFO']),
			'data' => $data,
		]);

		$entityData = $dp->getEntityData();

		$id = (int)$document['ID'];
		$docType = $document['DOC_TYPE'];

		return [
			'id' => $id,
			'data' => [
				'id' => $id,
				'docType' => $docType,
				'name' => $entityData['TITLE'] ?: StoreDocumentTable::getTypeList(true)[$docType],
				'date' => $entityData['DATE_CREATE'],
				'statuses' => $this->getStatus($document),
				'fields' => $this->getFields($dp),
			],
		];
	}

	protected function prepareContractor(array &$document): void
	{
		if (isset($document['CATALOG_STORE_DOCUMENT_CONTRACTOR_ID']))
		{
			$document['CONTRACTOR_ID'] = $document['CATALOG_STORE_DOCUMENT_CONTRACTOR_ID'];
			$document['CONTRACTOR_PERSON_NAME'] = $document['CATALOG_STORE_DOCUMENT_CONTRACTOR_PERSON_NAME'];
			$document['CONTRACTOR_COMPANY'] = $document['CATALOG_STORE_DOCUMENT_CONTRACTOR_COMPANY'];
			unset(
				$document['CATALOG_STORE_DOCUMENT_CONTRACTOR_ID'],
				$document['CATALOG_STORE_DOCUMENT_CONTRACTOR_PERSON_NAME'],
				$document['CATALOG_STORE_DOCUMENT_CONTRACTOR_COMPANY'],
			);
		}
	}

	protected function prepareResponsible(array &$document): void
	{
		if (isset($document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_ID']))
		{
			if (!isset($document['USER_INFO']))
			{
				$document['USER_INFO'] = [];
			}

			$userId = $document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_ID'];
			$document['USER_INFO'][$userId] = [
				'ID' => $userId,
				'LOGIN' => $document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_LOGIN'],
				'NAME' => $document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_NAME'],
				'LAST_NAME' => $document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_LAST_NAME'],
				'SECOND_NAME' => $document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_SECOND_NAME'],
				'PERSONAL_PHOTO' => $document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_PERSONAL_PHOTO'],
			];
			unset(
				$document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_ID'],
				$document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_LOGIN'],
				$document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_NAME'],
				$document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_LAST_NAME'],
				$document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_SECOND_NAME'],
				$document['CATALOG_STORE_DOCUMENT_RESPONSIBLE_PERSONAL_PHOTO'],
			);
		}
	}

	protected function getFields(StoreDocumentProvider $item): array
	{
		$fieldsConfig = $this->getFieldsConfig($item);
		$entityData = $item->getEntityData();

		$fields = [];
		foreach ($fieldsConfig as $fieldConfig)
		{
			if ($fieldConfig['name'] === self::SUM_FIELD_ID)
			{
				$value = [
					'amount' => $entityData['TOTAL'],
					'currency' => $entityData['CURRENCY'],
				];
			}
			elseif ($fieldConfig['type'] === 'entity-selector' || $fieldConfig['type'] === 'user')
			{
				$fieldConfig['entityList'] = ($entityData[$fieldConfig['name'] . '_ENTITY_LIST'] ?? []);
				$fieldConfig['config']['entityList'] = ($entityData[$fieldConfig['name'] . '_ENTITY_LIST'] ?? []);
				$value = ($fieldConfig['config']['entityList'] ? current($fieldConfig['config']['entityList'])['id'] : null);
			}
			else
			{
				$value = ($entityData[$fieldConfig['name']] ?? null);
			}

			if ($value !== null || !empty($fieldConfig['required']))
			{
				$fieldConfig['value'] = $value;
				$fields[] = $fieldConfig;
			}
		}

		return $fields;
	}

	protected function getFieldsConfig(StoreDocumentProvider $item): array
	{
		$fields = $item->getEntityFieldsForListView();
		foreach ($fields as &$field)
		{
			$this->prepareField($field);
		}
		unset($field);

		return $fields;
	}

	protected function prepareField(array &$field): void
	{
		if ($field['name'] === self::CONTRACTOR_FIELD_ID)
		{
			$field['params']['styleName'] = 'client';
		}

		if ($field['name'] === self::SUM_FIELD_ID)
		{
			$field['params']['styleName'] = 'money';
		}

		$field['params']['readOnly'] = true;
	}

	protected function getStatus(array $item): array
	{
		$result = [];

		if ($item['STATUS'] === 'N' && $item['WAS_CANCELLED'] === 'Y')
		{
			$result[] = self::CANCELLED;
		}

		if ($item['STATUS'] === 'N' && $item['WAS_CANCELLED'] === 'N')
		{
			$result[] = self::NOT_CONDUCTED;
		}

		if ($item['STATUS'] === 'Y')
		{
			$result[] = self::CONDUCTED;
		}

		return $result;
	}

	protected function getContractorInfo(EO_StoreDocument $item): array
	{
		$result = [];

		$contractor = $item->getContractor();
		if ($contractor === null)
		{
			return $result;
		}

		if ((int)$contractor->getPersonType() === CONTRACTOR_INDIVIDUAL)
		{
			$result = [
				'contactName' => $contractor->getPersonName(),
				'contactId' => $contractor->getId(),
			];
		}
		elseif ((int)$contractor->getPersonType() === CONTRACTOR_JURIDICAL)
		{
			$result = [
				'companyName' => $contractor->getCompany(),
				'companyId' => $contractor->getId(),
				'contactName' => $contractor->getPersonName(),
			];
		}

		return $result;
	}
}
