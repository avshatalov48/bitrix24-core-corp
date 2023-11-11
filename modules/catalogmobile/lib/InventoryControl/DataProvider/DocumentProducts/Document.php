<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\Catalog;
use Bitrix\CatalogMobile\InventoryControl\Dto;
use Bitrix\Catalog\Access;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentItem;

Loader::includeModule('catalog');

class Document
{
	private const STATUS_CONDUCTED = 'Y';

	public static function load(?int $documentId = null, ?string $documentType = null): Dto\Document
	{
		if ($documentId)
		{
			if ($documentType === StoreDocumentTable::TYPE_SALES_ORDERS)
			{
				return self::loadRealization($documentId);
			}

			$document = StoreDocumentTable::getById($documentId)->fetch();

			if (!$document)
			{
				throw new \DomainException("Document $documentId not found");
			}

			return new Dto\Document([
				'id' => (int)$document['ID'],
				'type' => $document['DOC_TYPE'],
				'currency' => $document['CURRENCY'],
				'editable' => (
					$document['STATUS'] !== self::STATUS_CONDUCTED
					&& AccessController::getCurrent()->check(
						ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
						Access\Model\StoreDocument::createFromArray($document)
					)
				),
				'total' => [
					'amount' => (float)$document['TOTAL'],
					'currency' => $document['CURRENCY'],
				]
			]);
		}

		return self::getEmptyDocument($documentType);
	}

	private static function loadRealization(int $entityId): Dto\Document
	{
		if (!Loader::includeModule('sale'))
		{
			return self::getEmptyDocument(StoreDocumentTable::TYPE_SALES_ORDERS);
		}

		$shipment = ShipmentRepository::getInstance()->getById($entityId);
		if (!$shipment)
		{
			throw new \DomainException("Document $entityId not found");
		}

		return new Dto\Document([
			'id' => $shipment->getId(),
			'type' => StoreDocumentTable::TYPE_SALES_ORDERS,
			'currency' => $shipment->getOrder()->getCurrency(),
			'editable' => (
				!$shipment->isShipped()
				&& AccessController::getCurrent()->check(
					ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
					Access\Model\StoreDocument::createForSaleRealization($shipment->getId())
				)
			),
			'total' => [
				'amount' => self::getShipmentTotal($shipment),
				'currency' => $shipment->getOrder()->getCurrency(),
			]
		]);
	}

	private static function getShipmentTotal(Shipment $shipment): float
	{
		$total = 0;

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			$total += $basketItem->getPrice() * $shipmentItem->getQuantity();
		}

		return $total;
	}

	private static function getEmptyDocument(?string $documentType = null): Dto\Document
	{
		$currency = Catalog::getBaseCurrency();

		return new Dto\Document([
			'type' => $documentType,
			'currency' => $currency,
			'total' => [
				'amount' => 0.0,
				'currency' => $currency,
			]
		]);
	}
}
