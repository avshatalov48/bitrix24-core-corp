<?php

namespace Bitrix\CatalogMobile\RealizationList;

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\CatalogMobile\EntityEditor\RealizationDocumentProvider;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentListItem;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentListItemData;
use Bitrix\Sale\ShipmentItem;
use Bitrix\Sale\Tax\VatCalculator;

class Item
{
	private const NOT_DEDUCTED = 'N';
	private const DEDUCTED = 'Y';
	private const CANCELED = 'C';

	private array $document = [];

	public function __construct(array $document = [])
	{
		$this->document = $document;
	}

	public function prepareItem(): DocumentListItem
	{
		$document = $this->document;

		$this->prepareResponsible($document);

		$id = (int)$document['ID'];
		$fields = $this->getFields($document);

		return $this->buildItemDto([
			'id' => $id,
			'docType' => StoreDocumentTable::TYPE_SALES_ORDERS,
			'name' => Loc::getMessage('CATALOG_REALIZATION_LIST_TITLE_MSGVER_1', ['#DOCUMENT_ID#' => $document['ACCOUNT_NUMBER']]),
			'date' => $document['DATE_INSERT']->getTimestamp(),
			'statuses' => $this->getStatus($document),
			'fields' => $fields,
		]);
	}

	private function getFields(array $document): array
	{
		$documentProvider = new RealizationDocumentProvider((int)$document['ID']);
		$entityFields = $documentProvider->getEntityFields();
		$entityData = $documentProvider->getEntityData();
		$mappedFields = [
			'TOTAL_WITH_CURRENCY' => [
				'name' => 'TOTAL_WITH_CURRENCY',
				'editable' => true,
				'title' => Loc::getMessage('CATALOG_REALIZATION_LIST_TOTAL_WITH_CURRENCY'),
				'type' => 'money',
				'multiple' => false,
				'params' => [
					'styleName' => 'money',
					'readOnly' => true,
				],
				'data' => $this->getTotalWithCurrencyData(),
				'config' => $this->getTotalWithCurrencyData(),
				'value' => [
					'amount' => $this->getTotalWithCurrencyValue($document),
					'currency' => $document['CURRENCY'],
				],
			],
		];
		foreach ($entityFields as $entityField)
		{
			$field = $entityField;
			if ($entityField['name'] === 'DOC_STATUS')
			{
				$field['params']['readOnly'] = true;
				$field['config'] = [];
				$field['value'] = $entityData['DOC_STATUS'];
			}
			else if ($entityField['name'] === 'CLIENT')
			{
				$field['params'] = [
					'styleName' => 'client',
					'readOnly' => true,
				];
				$field['data']['map'] = [
					'data' => 'CLIENT',
					'companyId' => 'COMPANY_ID',
					'contactIds' => 'CONTACT_IDS',
				];
				$field['config'] = $field['data'];
				$field['entityList'] = [
					'contact' => $entityData['CLIENT_INFO']['CONTACT_DATA'],
					'company' => $entityData['CLIENT_INFO']['COMPANY_DATA'],
				];
				$field['value'] = $field['entityList'];
			}
			else if ($entityField['name'] === 'RESPONSIBLE_ID')
			{
				$field['data']['provider'] = [
					'context' => 'CATALOG_DOCUMENT',
				];
				$field['params']['readOnly'] = true;
				$field['config'] = [
					'entityListField' => 'RESPONSIBLE_ID_ENTITY_LIST',
					'provider' => [
						'context' => 'CATALOG_DOCUMENT',
					],
					'hasSolidBorder' => true,
					'entityList' => $entityData['RESPONSIBLE_ID_ENTITY_LIST'],
				];
				$field['entityList'] = $entityData['RESPONSIBLE_ID_ENTITY_LIST'];
				$field['value'] = $entityData['RESPONSIBLE_ID'];
			}
			else
			{
				continue;
			}
			$mappedFields[$field['name']] = $field;
		}

		return [
			$mappedFields['DOC_STATUS'],
			$mappedFields['TOTAL_WITH_CURRENCY'],
			$mappedFields['CLIENT'],
			$mappedFields['RESPONSIBLE_ID'],
		];
	}

	private function getTotalWithCurrencyValue(array $document): float
	{
		$shipmentBasketResult = ShipmentItem::getList([
			'select' => [
				'PRICE' => 'BASKET.PRICE',
				'VAT_RATE' => 'BASKET.VAT_RATE',
				'VAT_INCLUDED' => 'BASKET.VAT_INCLUDED',
				'ORDER_DELIVERY_ID',
				'QUANTITY',
			],
			'filter' => ['=ORDER_DELIVERY_ID' => $document['ID']],
		]);
		$totalValue = 0;
		while ($shipmentItem = $shipmentBasketResult->fetch())
		{
			$priceWithVat = (float)$shipmentItem['PRICE'];
			if ($shipmentItem['VAT_RATE'] !== null)
			{
				$vatCalculator = new VatCalculator((float)$shipmentItem['VAT_RATE']);

				$priceWithVat = ($shipmentItem['VAT_INCLUDED'] === 'Y')
					? $priceWithVat
					: $vatCalculator->accrue($priceWithVat);
			}

			$totalValue += $priceWithVat * $shipmentItem['QUANTITY'];
		}

		return $totalValue;
	}

	private function getTotalWithCurrencyData(): array
	{
		return [
			'largeFormat' => true,
			'affectedFields' => ['CURRENCY', 'TOTAL'],
			'amount' => 'TOTAL',
			'amountReadOnly' => true,
			'currency' => [
				'name' => 'CURRENCY',
				'items' => $this->prepareCurrencyList(),
			],
			'formatted' => 'FORMATTED_TOTAL',
			'formattedWithCurrency' => 'FORMATTED_TOTAL_WITH_CURRENCY',
		];
	}

	private function prepareCurrencyList(): array
	{
		static $currencyList = [];
		if (!empty($currencyList))
		{
			return $currencyList;
		}

		$existingCurrencies = CurrencyTable::getList([
			'select' => [
				'CURRENCY',
				'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME',
			],
			'order' => [
				'BASE' => 'DESC',
				'SORT' => 'ASC',
				'CURRENCY' => 'ASC',
			],
			'cache' => ['ttl' => 86400],
		])->fetchAll();
		foreach ($existingCurrencies as $currency)
		{
			$currencyList[] = [
				'name' => $currency['FULL_NAME'],
				'value' => $currency['CURRENCY'],
			];
		}

		return $currencyList;
	}

	private function prepareResponsible(array &$document): void
	{
		if (isset($document['RESPONSIBLE_BY_ID']))
		{
			if (!isset($document['USER_INFO']))
			{
				$document['USER_INFO'] = [];
			}

			$userId = $document['RESPONSIBLE_BY_ID'];
			$document['USER_INFO'][$userId] = [
				'ID' => $userId,
				'LOGIN' => $document['RESPONSIBLE_BY_LOGIN'],
				'NAME' => $document['RESPONSIBLE_BY_NAME'],
				'LAST_NAME' => $document['RESPONSIBLE_BY_LAST_NAME'],
				'SECOND_NAME' => $document['RESPONSIBLE_BY_SECOND_NAME'],
				'PERSONAL_PHOTO' => $document['RESPONSIBLE_BY_PERSONAL_PHOTO'],
			];
			unset(
				$document['RESPONSIBLE_BY_ID'],
				$document['RESPONSIBLE_BY_LOGIN'],
				$document['RESPONSIBLE_BY_NAME'],
				$document['RESPONSIBLE_BY_LAST_NAME'],
				$document['RESPONSIBLE_BY_SECOND_NAME'],
				$document['RESPONSIBLE_BY_PERSONAL_PHOTO'],
			);
		}
	}

	private function buildItemDto(array $data): DocumentListItem
	{
		$item = DocumentListItem::make([
			'id' => $data['id'],
		]);
		$item->data = DocumentListItemData::make($data);
		return $item;
	}

	private function getStatus(array $item): array
	{
		$result = [];

		if ($item['DEDUCTED'] === 'N' && !empty($item['EMP_DEDUCTED_ID']))
		{
			$result[] = self::CANCELED;
		}

		if ($item['DEDUCTED'] === 'N' && empty($item['EMP_DEDUCTED_ID']))
		{
			$result[] = self::NOT_DEDUCTED;
		}

		if ($item['DEDUCTED'] === 'Y')
		{
			$result[] = self::DEDUCTED;
		}

		return $result;
	}
}
