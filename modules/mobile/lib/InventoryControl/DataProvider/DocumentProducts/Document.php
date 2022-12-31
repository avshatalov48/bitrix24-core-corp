<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Integration\Catalog\Catalog;
use Bitrix\Mobile\InventoryControl\Dto;
use Bitrix\Catalog\Access;

Loader::includeModule('catalog');

class Document
{
	private const STATUS_CONDUCTED = 'Y';

	public static function load(?int $documentId = null, ?string $documentType = null): Dto\Document
	{
		if ($documentId)
		{
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
		else
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
}
