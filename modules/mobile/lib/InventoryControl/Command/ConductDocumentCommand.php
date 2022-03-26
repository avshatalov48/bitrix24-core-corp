<?php

namespace Bitrix\Mobile\InventoryControl\Command;

use Bitrix\Catalog\StoreBarcodeTable;
use Bitrix\Catalog\StoreDocumentBarcodeTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Mobile\Command;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use CCatalogDocs;

Loader::requireModule('catalog');

final class ConductDocumentCommand extends Command
{
	use ReadsApplicationErrors;

	/**
	 * @var int
	 */
	private $documentId;

	/**
	 * @var int
	 */
	private $userId;

	public function __construct(int $documentId, int $userId)
	{
		$this->documentId = $documentId;
		$this->userId = $userId;
	}

	public function execute(): Result
	{
		return $this->transaction(function () {
			$result = new Result();

			if (CCatalogDocs::conductDocument($this->documentId, $this->userId))
			{
				$result = $this->updateCatalogBarcodes();
			}
			else
			{
				$error = $this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_IC_COMMAND_CONDUCT_DOCUMENT_ERROR'));

				$result->addError($error);
			}

			return $result;
		});
	}

	/**
	 * Copy barcodes from inventory document to catalog products
	 * @return Result
	 */
	private function updateCatalogBarcodes(): Result
	{
		$result = new Result();

		$document = StoreDocumentTable::getById($this->documentId)->fetch();
		if (!$document)
		{
			return $result;
		}

		$allowedTypes = [
			StoreDocumentTable::TYPE_ARRIVAL,
			StoreDocumentTable::TYPE_STORE_ADJUSTMENT,
		];

		if (!in_array($document['DOC_TYPE'], $allowedTypes, true))
		{
			return $result;
		}

		$documentBarcodes = [];
		$rows = StoreDocumentBarcodeTable::getList([
			'filter' => [
				'=DOCUMENT_ELEMENT.DOC_ID' => $this->documentId,
			],
			'select' => ['BARCODE', 'SKU_ID' => 'DOCUMENT_ELEMENT.ELEMENT_ID']
		]);
		while ($row = $rows->fetch())
		{
			$skuId = (int)($row['SKU_ID']);
			$barcode = $row['BARCODE'];
			if ($skuId > 0 && $barcode)
			{
				$documentBarcodes[$barcode] = $skuId;
			}
		}

		if (empty($documentBarcodes))
		{
			return $result;
		}

		$productBarcodes = [];
		$rows = StoreBarcodeTable::getList([
			'filter' => ['=BARCODE' => array_keys($documentBarcodes)],
			'select' => ['BARCODE', 'PRODUCT_ID']
		]);

		while ($row = $rows->fetch())
		{
			$productBarcodes[$row['BARCODE']] = (int)$row['PRODUCT_ID'];
		}

		foreach ($documentBarcodes as $barcode => $skuId)
		{
			if (isset($productBarcodes[$barcode]))
			{
				$existingSkuId = $productBarcodes[$barcode];
				if ($skuId !== $existingSkuId)
				{
					$message = Loc::getMessage('MOBILE_IC_COMMAND_CONDUCT_DOCUMENT_ERROR_BARCODE_ALREADY_EXISTS', [
						'#BARCODE#' => htmlspecialcharsbx($barcode)
					]);
					$result->addError(new Error($message));
				}
				continue;
			}

			$r = StoreBarcodeTable::add([
				'PRODUCT_ID' => $skuId,
				'BARCODE' => $barcode,
				'CREATED_BY' => $this->userId,
				'MODIFIED_BY' => $this->userId,
			]);

			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}
}
