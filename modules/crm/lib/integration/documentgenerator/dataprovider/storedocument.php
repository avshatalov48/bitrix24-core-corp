<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\MeasureTable;
use Bitrix\Catalog\StoreDocumentElementTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Store\Document\Element;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\Dictionary\ProductVariant;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

/**
 * Class StoreDocument
 *
 * @package Bitrix\Crm\Integration\DocumentGenerator\DataProvider
 */
abstract class StoreDocument extends ProductsDataProvider implements Nameable
{
	/**
	 * @inheritDoc
	 */
	public function getFields()
	{
		if (!is_null($this->fields))
		{
			return $this->fields;
		}

		$fields = [
			'DOCUMENT_RESPONSIBLE' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_FLD_DOCUMENT_RESPONSIBLE'),
				'PROVIDER' => User::class,
				'OPTIONS' => [
					'FORMATTED_NAME_FORMAT' => [
						'format' => CrmEntityDataProvider::getNameFormat(),
					]
				],
				'VALUE' => [$this, 'getDocumentResponsibleId'],
			],
			'DOCUMENT_STORE_FROM_TITLE' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_FLD_DOCUMENT_STORE_FROM_TITLE'),
			],
			'DOCUMENT_STORE_TO_TITLE' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_FLD_DOCUMENT_STORE_TO_TITLE'),
			],
			'CURRENT_TIME' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_FLD_CURRENT_TIME'),
				'TYPE' => static::FIELD_TYPE_DATE,
				'VALUE' => [$this, 'getCurrentTime'],
			],
		];
		$this->fields = array_merge(
			parent::getFields(),
			$fields
		);

		if (isset($this->fields['PRODUCTS']))
		{
			$this->fields['PRODUCTS']['OPTIONS']['ITEM_PROVIDER'] = Element::class;
		}

		return $this->fields;
	}

	/**
	 * @inheritDoc
	 */
	public function hasAccess($userId)
	{
		if ($this->isLoaded())
		{
			return Loader::includeModule('catalog') && AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getLangName()
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function getCrmOwnerType(): int
	{
		return \CCrmOwnerType::StoreDocument;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUserFieldEntityID(): ?string
	{
		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function getTableClass(): ?string
	{
		return StoreDocumentTable::class;
	}

	/**
	 * @inheritDoc
	 */
	public function isPrintable(): Result
	{
		$result = new Result();

		$elementsList = StoreDocumentElementTable::getList([
			'filter' => ['=DOC_ID' => (int)$this->source],
			'select' => ['STORE_TO'],
			'group' => ['STORE_TO'],
		]);

		if ($elementsList->getSelectedRowsCount() > 1)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_MULTI_STORE_PRINT_NOT_AVAILABLE'))
			);
		}

		return $result;
	}

	/**
	 * @return DateTime
	 */
	public function getCurrentTime(): DateTime
	{
		return new DateTime();
	}

	/**
	 * @return int
	 */
	public function getDocumentResponsibleId(): int
	{
		return isset($this->data['RESPONSIBLE_ID']) ? (int)$this->data['RESPONSIBLE_ID'] : 0;
	}

	/**
	 * @inheritDoc
	 */
	protected function fetchData()
	{
		if ($this->data === null)
		{
			parent::fetchData();
			$this->fetchAdditionalData();
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $data
	 *
	 * @return Element
	 */
	protected function createProduct(array $data): Product
	{
		return new Element($data);
	}

	/**
	 * @return array
	 */
	protected function loadProductsData()
	{
		$result = [];

		$documentElementList = StoreDocumentElementTable::getList([
			'select' => [
				'ID',
				'ELEMENT_ID',
				'AMOUNT',
				'PURCHASING_PRICE',
				'COMMENT',
			],
			'filter' => [
				'=DOC_ID' => $this->data['ID'],
			],
		]);

		while ($documentElementRaw = $documentElementList->fetch())
		{
			if (!$this->isProductVariantSupported(ProductVariant::GOODS))
			{
				continue;
			}

			$sku = $this->getSkuByProductId($documentElementRaw['ELEMENT_ID']);

			$result[] = [
				'ID' => $documentElementRaw['ID'],
				'NAME' => $sku ? $sku->getName() : '',
				'PRODUCT_ID' => $documentElementRaw['ELEMENT_ID'],
				'QUANTITY' => $documentElementRaw['AMOUNT'],
				'PRICE' => $documentElementRaw['PURCHASING_PRICE'],
				'MEASURE_CODE' => $sku ? $this->getMeasureCodeBySku($sku) : null,
				'PRODUCT_VARIANT' => ProductVariant::GOODS,
				'CUSTOMIZED' => 'Y',
				'CURRENCY_ID' => $sku ? $this->getCurrencyBySku($sku) : null,
				'COMMENT' => $documentElementRaw['COMMENT'],
			];
		}

		return $result;
	}

	private function fetchAdditionalData(): void
	{
		$documentElementRaw = StoreDocumentElementTable::getList([
			'select' => [
				'*',
				'STORE_FROM_REF',
				'STORE_TO_REF',
			],
			'filter' => [
				'=DOC_ID' => $this->data['ID'],
			],
		])->fetch();

		if (!$documentElementRaw)
		{
			return;
		}

		/**
		 * Stores data
		 */
		$this->data['DOCUMENT_STORE_FROM_TITLE'] = $documentElementRaw['CATALOG_STORE_DOCUMENT_ELEMENT_STORE_FROM_REF_TITLE'];
		$this->data['DOCUMENT_STORE_TO_TITLE'] = $documentElementRaw['CATALOG_STORE_DOCUMENT_ELEMENT_STORE_TO_REF_TITLE'];

		/**
		 * Currency data
		 */
		$sku = $this->getSkuByProductId($documentElementRaw['ELEMENT_ID']);
		if ($sku)
		{
			$basePrice = $sku->getPriceCollection()->findBasePrice();
			if ($basePrice)
			{
				$this->data['CURRENCY_ID'] = $basePrice->getCurrency();
			}
		}
	}

	/**
	 * @param int $productId
	 * @return BaseSku|null
	 */
	private function getSkuByProductId(int $productId): ?BaseSku
	{
		return ServiceContainer::getRepositoryFacade()->loadVariation($productId);
	}

	/**
	 * @param BaseSku $sku
	 * @return string|null
	 */
	private function getCurrencyBySku(BaseSku $sku): ?string
	{
		$basePrice = $sku->getPriceCollection()->findBasePrice();
		if (!$basePrice)
		{
			return null;
		}

		return $basePrice->getCurrency();
	}

	/**
	 * @param BaseSku $sku
	 * @return int|null
	 */
	private function getMeasureCodeBySku(BaseSku $sku): ?int
	{
		$measureId = (int)$sku->getField('MEASURE');
		if (!$measureId)
		{
			$measureId = $this->getDefaultMeasureId();
		}

		if (!$measureId)
		{
			return null;
		}

		$measureItem = \CCatalogMeasure::getList(
			['CODE' => 'ASC'],
			['=ID' => $measureId],
			false,
			['nTopCount' => 1],
			['CODE']
		)->fetch();

		return $measureItem ? (int)$measureItem['CODE'] : null;
	}

	/**
	 * @return int|null
	 */
	private function getDefaultMeasureId(): ?int
	{
		$item = MeasureTable::getList([
			'select' => ['ID'],
			'filter' => ['=IS_DEFAULT' => 'Y',],
		])->fetch();

		return $item ? (int)$item['ID'] : null;
	}
}
