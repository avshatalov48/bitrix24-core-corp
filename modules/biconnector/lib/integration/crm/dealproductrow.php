<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class DealProductRow
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_deal_product_row to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_product_row table.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (!Loader::includeModule('crm'))
		{
			return;
		}

		$params = $event->getParameters();
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		$discountForSql = [];
		$discountSemantics = [
			\Bitrix\Crm\Discount::UNDEFINED,
			\Bitrix\Crm\Discount::MONETARY,
			\Bitrix\Crm\Discount::PERCENTAGE,
		];

		$defaultCategoryName = $helper->forSql(\Bitrix\Crm\Category\DealCategory::getDefaultCategoryName());

		foreach ($discountSemantics as $id)
		{
			$discountForSql[] = 'when #FIELD_NAME# = \'' . $helper->forSql($id) . '\' then \'' . $helper->forSql(\Bitrix\Crm\Discount::resolveName($id)) . '\'';
		}
		$discountSql = 'case ' . implode("\n", $discountForSql) . ' else null end';

		$result['crm_deal_product_row'] = [
			'TABLE_NAME' => 'b_crm_product_row',
			'TABLE_ALIAS' => 'PR',
			'FILTER' => [
				'=OWNER_TYPE' => 'D',
			],
			'FILTER_FIELDS' => [
				'OWNER_TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'PR.OWNER_TYPE',
					'FIELD_TYPE' => 'string',
				],
			],
			'FIELDS' => [
				//ID INT (18) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'PR.ID',
					'FIELD_TYPE' => 'int',
				],
				//OWNER_ID INT(1) NOT NULL,
				'DEAL_ID' => [
					'FIELD_NAME' => 'PR.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				//b_crm_deal.DATE_CREATE DATETIME NULL,
				'DEAL_DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'D',
					'JOIN' => 'INNER JOIN b_crm_deal D ON D.ID = PR.OWNER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal D ON D.ID = PR.OWNER_ID',
				],
				//b_crm_deal.CLOSEDATE DATETIME DEFAULT NULL,
				'DEAL_CLOSEDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CLOSEDATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'D',
					'JOIN' => 'INNER JOIN b_crm_deal D ON D.ID = PR.OWNER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal D ON D.ID = PR.OWNER_ID',
				],
				//TODO: OWNER_TYPE CHAR(3) NOT NULL,
				//PRODUCT_ID INT(1) NOT NULL,
				'PRODUCT' => [
					'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.PRODUCT_ID', '\']\'') . ', nullif(PR.PRODUCT_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
				],
				'PRODUCT_ID' => [
					'FIELD_NAME' => 'PR.PRODUCT_ID',
					'FIELD_TYPE' => 'int',
				],
				//PRODUCT_NAME VARCHAR(256) NULL,
				'PRODUCT_NAME' => [
					'FIELD_NAME' => 'PR.PRODUCT_NAME',
					'FIELD_TYPE' => 'string',
				],
				//PRICE DECIMAL(18,2) NOT NULL,
				'PRICE' => [
					'FIELD_NAME' => 'PR.PRICE',
					'FIELD_TYPE' => 'double',
				],
				//PRICE_ACCOUNT DECIMAL(18,2) NOT NULL DEFAULT 0,
				//PRICE_EXCLUSIVE DECIMAL(18,2) NULL,
				'PRICE_EXCLUSIVE' => [
					'FIELD_NAME' => 'PR.PRICE_EXCLUSIVE',
					'FIELD_TYPE' => 'double',
				],
				//PRICE_NETTO DECIMAL(18,2) NULL,
				'PRICE_NETTO' => [
					'FIELD_NAME' => 'PR.PRICE_NETTO',
					'FIELD_TYPE' => 'double',
				],
				//PRICE_BRUTTO DECIMAL(18,2) NULL,
				'PRICE_BRUTTO' => [
					'FIELD_NAME' => 'PR.PRICE_BRUTTO',
					'FIELD_TYPE' => 'double',
				],
				//QUANTITY DECIMAL(18,4) NOT NULL,
				'QUANTITY' => [
					'FIELD_NAME' => 'PR.QUANTITY',
					'FIELD_TYPE' => 'double',
				],
				//DISCOUNT_TYPE_ID TINYINT(1) UNSIGNED NULL,
				'DISCOUNT_TYPE' => [
					'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.DISCOUNT_TYPE_ID', '\']\'') . ', ' . str_replace('#FIELD_NAME#', 'PR.DISCOUNT_TYPE_ID', $discountSql) . ')',
					'FIELD_TYPE' => 'string',
				],
				'DISCOUNT_TYPE_ID' => [
					'FIELD_NAME' => 'PR.DISCOUNT_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'DISCOUNT_TYPE_NAME' => [
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'PR.DISCOUNT_TYPE_ID', $discountSql),
					'FIELD_TYPE' => 'string',
				],
				//DISCOUNT_RATE DECIMAL(18,2) NULL,
				'DISCOUNT_RATE' => [
					'FIELD_NAME' => 'PR.DISCOUNT_RATE',
					'FIELD_TYPE' => 'double',
				],
				//DISCOUNT_SUM DECIMAL(18,2) NULL,
				'DISCOUNT_SUM' => [
					'FIELD_NAME' => 'PR.DISCOUNT_SUM',
					'FIELD_TYPE' => 'double',
				],
				//TAX_RATE DECIMAL(18,2) NULL,
				'TAX_RATE' => [
					'FIELD_NAME' => 'PR.TAX_RATE',
					'FIELD_TYPE' => 'double',
				],
				//TAX_INCLUDED CHAR(1) NULL,
				'TAX_INCLUDED' => [
					'FIELD_NAME' => 'PR.TAX_INCLUDED',
					'FIELD_TYPE' => 'string',
				],
				//CUSTOMIZED CHAR(1) NULL,
				'CUSTOMIZED' => [
					'FIELD_NAME' => 'PR.CUSTOMIZED',
					'FIELD_TYPE' => 'string',
				],
				//MEASURE_CODE INT(1) UNSIGNED NULL,
				'MEASURE' => [
					'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.MEASURE_CODE', '\']\'') . ', nullif(PR.MEASURE_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
				],
				'MEASURE_CODE' => [
					'FIELD_NAME' => 'PR.MEASURE_CODE',
					'FIELD_TYPE' => 'int',
				],
				//MEASURE_NAME VARCHAR(50) NULL,
				'MEASURE_NAME' => [
					'FIELD_NAME' => 'PR.MEASURE_NAME',
					'FIELD_TYPE' => 'string',
				],
				//SORT INT(1) NULL,
				'SORT' => [
					'FIELD_NAME' => 'PR.SORT',
					'FIELD_TYPE' => 'int',
				],
				//TODO: XML_ID varchar(255) DEFAULT NULL,
				'CATEGORY' => [
					'FIELD_NAME' => $helper->getConcatFunction('\'[\'', $helper->getIsNullFunction('DC.ID', '0'), '\'] \'', $helper->getIsNullFunction('DC.NAME', '\'' . $defaultCategoryName . '\'')),
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DC',
					'LEFT_JOIN' => [
						'LEFT JOIN b_crm_deal D ON D.ID = PR.OWNER_ID',
						'LEFT JOIN b_crm_deal_category DC ON DC.ID = D.CATEGORY_ID',
					],
				],
				'CATEGORY_NAME' => [
					'FIELD_NAME' => $helper->getIsNullFunction('DC.NAME', '\'' . $defaultCategoryName . '\''),
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DC',
					'LEFT_JOIN' => [
						'LEFT JOIN b_crm_deal D ON D.ID = PR.OWNER_ID',
						'LEFT JOIN b_crm_deal_category DC ON DC.ID = D.CATEGORY_ID',
					],
				],
				'PARENT' => self::getParentDescription($helper),
				'SUPERPARENT' => self::getSuperParentDescription($helper),
				'SUPERSUPERPARENT' => self::getSuperSuperParentDescription($helper),
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_deal_product_row']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_DEAL_PRODUCT_ROW_TABLE'] ?? 'crm_deal_product_row';
		foreach ($result['crm_deal_product_row']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_DEAL_PRODUCT_ROW_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_DEAL_PRODUCT_ROW_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}

	private static function getParentDescription(\Bitrix\Main\DB\SqlHelper $helper): array
	{
		if (Loader::includeModule('iblock'))
		{
			$parentProductQuery = self::getParentProductIdQuery();

			return [
				'FIELD_NAME' => 'IS1.NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'IS1',
				'LEFT_JOIN' => [
					'LEFT JOIN b_iblock_element IE ON IE.ID = ' . ($parentProductQuery ? $helper->getIsNullFunction($parentProductQuery, 'PR.PRODUCT_ID') : 'PR.PRODUCT_ID'),
					'LEFT JOIN b_iblock_section IS1 ON IS1.ID = IE.IBLOCK_SECTION_ID',
				],
			];
		}

		return [
			'FIELD_NAME' => 'null',
			'FIELD_TYPE' => 'string',
		];
	}

	private static function getSuperParentDescription(\Bitrix\Main\DB\SqlHelper $helper): array
	{
		if (Loader::includeModule('iblock'))
		{
			$parentProductQuery = self::getParentProductIdQuery();

			return [
				'FIELD_NAME' => 'IS2.NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'IS2',
				'LEFT_JOIN' => [
					'LEFT JOIN b_iblock_element IE ON IE.ID = ' . ($parentProductQuery ? $helper->getIsNullFunction($parentProductQuery, 'PR.PRODUCT_ID') : 'PR.PRODUCT_ID'),
					'LEFT JOIN b_iblock_section IS1 ON IS1.ID = IE.IBLOCK_SECTION_ID',
					'LEFT JOIN b_iblock_section IS2 ON IS2.ID = IS1.IBLOCK_SECTION_ID',
				],
			];
		}

		return [
			'FIELD_NAME' => 'null',
			'FIELD_TYPE' => 'string',
		];
	}

	private static function getSuperSuperParentDescription(\Bitrix\Main\DB\SqlHelper $helper): array
	{
		if (Loader::includeModule('iblock'))
		{
			$parentProductQuery = self::getParentProductIdQuery();

			return [
				'FIELD_NAME' => 'IS3.NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'IS3',
				'LEFT_JOIN' => [
					'LEFT JOIN b_iblock_element IE ON IE.ID = ' . ($parentProductQuery ? $helper->getIsNullFunction($parentProductQuery, 'PR.PRODUCT_ID') : 'PR.PRODUCT_ID'),
					'LEFT JOIN b_iblock_section IS1 ON IS1.ID = IE.IBLOCK_SECTION_ID',
					'LEFT JOIN b_iblock_section IS2 ON IS2.ID = IS1.IBLOCK_SECTION_ID',
					'LEFT JOIN b_iblock_section IS3 ON IS3.ID = IS2.IBLOCK_SECTION_ID',
				],
			];
		}

		return [
			'FIELD_NAME' => 'null',
			'FIELD_TYPE' => 'string',
		];
	}

	private static function getParentProductIdQuery(): ?string
	{
		static $query = null;
		if ($query)
		{
			return $query;
		}

		$crmCatalogIblockOfferId = \Bitrix\Crm\Product\Catalog::getDefaultOfferId();
		if  (!$crmCatalogIblockOfferId)
		{
			return null;
		}

		$catalogIblockTableElement = \Bitrix\Catalog\CatalogIblockTable::getByPrimary(
			$crmCatalogIblockOfferId,
			[
				'select' => ['SKU_PROPERTY_ID'],
			],
		)->fetch();
		if (!$catalogIblockTableElement)
		{
			return null;
		}

		$skuPropertyId = (int)$catalogIblockTableElement['SKU_PROPERTY_ID'];
		if (!$skuPropertyId)
		{
			return null;
		}

		$crmCatalogIblockOffer = \Bitrix\Iblock\IblockTable::getRow([
			'select' => ['VERSION'],
			'filter' => [
				'=ID' => $crmCatalogIblockOfferId,
			],
		]);
		if (!$crmCatalogIblockOffer)
		{
			return null;
		}

		$crmCatalogIblockOfferVersion = (int)$crmCatalogIblockOffer['VERSION'];
		if ($crmCatalogIblockOfferVersion === 1)
		{
			$query = "(SELECT VALUE FROM b_iblock_element_property where IBLOCK_ELEMENT_ID = PR.PRODUCT_ID and IBLOCK_PROPERTY_ID = {$skuPropertyId})";
		}
		else
		{
			$query = "(SELECT PROPERTY_{$skuPropertyId} FROM b_iblock_element_prop_s{$crmCatalogIblockOfferId} where IBLOCK_ELEMENT_ID = PR.PRODUCT_ID)";
		}

		return $query;
	}
}
