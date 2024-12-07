<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class Product
{
	/**
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (
			!\Bitrix\Main\Loader::includeModule('crm')
			|| !\Bitrix\Main\Loader::includeModule('catalog')
		)
		{
			return;
		}

		$crmCatalogIblockId = \Bitrix\Crm\Product\Catalog::getDefaultId();
		$crmCatalogIblockOfferId = \Bitrix\Crm\Product\Catalog::getDefaultOfferId();
		if (!$crmCatalogIblockId)
		{
			return;
		}
		$basePriceId = \Bitrix\Catalog\GroupTable::getBasePriceTypeId();

		$params = $event->getParameters();
		$result = &$params[1];
		$languageId = $params[2];
		$result['crm_product'] = [
			'TABLE_NAME' => 'b_catalog_product',
			'TABLE_ALIAS' => 'P',
			'FILTER' => [
				'=IBLOCK_ID' => [$crmCatalogIblockId, $crmCatalogIblockOfferId],
			],
			'FILTER_FIELDS' => [
				'IBLOCK_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'IE.IBLOCK_ID',
					'FIELD_TYPE' => 'int',
					'TABLE_ALIAS' => 'IE',
					'JOIN' => 'INNER JOIN b_iblock_element IE ON IE.ID = P.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_iblock_element IE ON IE.ID = P.ID',
				],
			],
			'FIELDS' => [
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'P.ID',
					'FIELD_TYPE' => 'int',
				],
				'NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'IE.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'IE',
					'JOIN' => 'INNER JOIN b_iblock_element IE ON IE.ID = P.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_iblock_element IE ON IE.ID = P.ID',
				],
				'TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'PT.NAME',
					'FIELD_TYPE' => 'string',
					'LEFT_JOIN' => self::getProductTypeTemporaryTableLeftJoin(),
				],
				'PARENT_ID' => self::getParentIdDescription($crmCatalogIblockOfferId),
				'MEASURE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'IF(M.SYMBOL_RUS IS NULL OR M.SYMBOL_RUS = \'\', M.SYMBOL_INTL, M.SYMBOL_RUS)',
					'FIELD_TYPE' => 'string',
					'LEFT_JOIN' => 'LEFT JOIN b_catalog_measure M ON M.ID = P.MEASURE',
				],
				'PRICE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => $basePriceId ? 'CP.PRICE' : 'NULL',
					'FIELD_TYPE' => 'double',
					'LEFT_JOIN' => $basePriceId ? ('LEFT JOIN b_catalog_price CP ON CP.PRODUCT_ID = P.ID AND (CP.QUANTITY_FROM = 1 OR CP.QUANTITY_FROM IS NULL) AND (CP.QUANTITY_TO = 1 OR CP.QUANTITY_TO IS NULL) AND CP.CATALOG_GROUP_ID = ' . $basePriceId) : null,
				],
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_product']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_PRODUCT_TABLE'] ?? 'crm_product';
		foreach ($result['crm_product']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_PRODUCT_FIELD_' . $fieldCode] ?? null;
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_PRODUCT_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}

	private static function getProductTypeTemporaryTableLeftJoin(): string
	{
		$productTypes = \Bitrix\Catalog\ProductTable::getProductTypes(true);
		$temporaryTableQuery = '';
		foreach ($productTypes as $productTypeId => $productTypeName)
		{
			if ($temporaryTableQuery)
			{
				$temporaryTableQuery .= ' UNION ';
			}
			$temporaryTableQuery .= 'SELECT ' . $productTypeId . ' as ID, \'' . $productTypeName . '\' as NAME';
		}

		return 'LEFT JOIN (' . $temporaryTableQuery . ') PT ON PT.ID = P.TYPE';
	}

	private static function getParentIdDescription(?int $crmCatalogIblockOfferId): array
	{
		$defaultDescription = [
			'IS_METRIC' => 'N',
			'FIELD_NAME' => 'null',
			'FILED_TYPE' => 'int',
		];
		if (!$crmCatalogIblockOfferId)
		{
			return $defaultDescription;
		}

		$catalogIblockTableElement = \Bitrix\Catalog\CatalogIblockTable::getRow([
			'select' => ['SKU_PROPERTY_ID'],
			'filter' => ['=IBLOCK_ID' => $crmCatalogIblockOfferId],
		]);
		if (!$catalogIblockTableElement || !$catalogIblockTableElement['SKU_PROPERTY_ID'])
		{
			return $defaultDescription;
		}
		$skuPropertyId = (int)$catalogIblockTableElement['SKU_PROPERTY_ID'];

		$crmCatalogIblockOffer = \Bitrix\Iblock\IblockTable::getRow([
			'select' => ['VERSION'],
			'filter' => [
				'=ID' => $crmCatalogIblockOfferId,
			],
		]);
		if (!$crmCatalogIblockOffer)
		{
			return $defaultDescription;
		}
		$crmCatalogIblockOfferVersion = (int)$crmCatalogIblockOffer['VERSION'];

		if ($crmCatalogIblockOfferVersion === 1)
		{
			return [
				'IS_METRIC' => 'N',
				'FIELD_NAME' => 'IEP.VALUE',
				'FIELD_TYPE' => 'int',
				'TABLE_ALIAS' => 'IEP',
				'LEFT_JOIN' => 'LEFT JOIN b_iblock_element_property IEP ON IEP.IBLOCK_ELEMENT_ID = P.ID AND IEP.IBLOCK_PROPERTY_ID = ' . $skuPropertyId,
			];
		}

		return [
			'IS_METRIC' => 'N',
			'FIELD_NAME' => 'IEP.PROPERTY_' . $skuPropertyId,
			'FIELD_TYPE' => 'int',
			'TABLE_ALIAS' => 'IEP',
			'LEFT_JOIN' => "LEFT JOIN b_iblock_element_prop_s{$crmCatalogIblockOfferId} IEP ON IEP.IBLOCK_ELEMENT_ID = P.ID",
		];
	}
}
