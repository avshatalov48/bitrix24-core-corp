<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Crm\Discount;
use Bitrix\Crm\Product\Catalog;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\DB\MysqliSqlHelper;
use Bitrix\Main\DB\PgsqlSqlHelper;
use Bitrix\Main\Loader;

trait ProductDataHelperTrait
{
	private static function getDiscountSql(MysqliSqlHelper|PgsqlSqlHelper $helper): string
	{
		$discountForSql = [];
		$discountSemantics = [
			Discount::UNDEFINED,
			Discount::MONETARY,
			Discount::PERCENTAGE,
		];

		foreach ($discountSemantics as $id)
		{
			$discountForSql[] = 'when #FIELD_NAME# = \'' . $helper->forSql($id) . '\' then \'' . $helper->forSql(Discount::resolveName($id)) . '\'';
		}

		return 'case ' . implode("\n", $discountForSql) . ' else null end';
	}

	private static function getParentDescription(MysqliSqlHelper|PgsqlSqlHelper $helper): array
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

	private static function getSuperParentDescription(MysqliSqlHelper|PgsqlSqlHelper $helper): array
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

	private static function getSuperSuperParentDescription(MysqliSqlHelper|PgsqlSqlHelper $helper): array
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

		$crmCatalogIBlockOfferId = Catalog::getDefaultOfferId();
		if (!$crmCatalogIBlockOfferId)
		{
			return null;
		}

		$catalogIBlockTableElement = CatalogIblockTable::getByPrimary(
			$crmCatalogIBlockOfferId,
			[
				'select' => ['SKU_PROPERTY_ID'],
			],
		)->fetch();
		if (!$catalogIBlockTableElement)
		{
			return null;
		}

		$skuPropertyId = (int)$catalogIBlockTableElement['SKU_PROPERTY_ID'];
		if (!$skuPropertyId)
		{
			return null;
		}

		$crmCatalogIBlockOffer = IblockTable::getRow([
			'select' => ['VERSION'],
			'filter' => [
				'=ID' => $crmCatalogIBlockOfferId,
			],
		]);
		if (!$crmCatalogIBlockOffer)
		{
			return null;
		}

		$crmCatalogIBlockOfferVersion = (int)$crmCatalogIBlockOffer['VERSION'];
		if ($crmCatalogIBlockOfferVersion === 1)
		{
			$query = "(SELECT VALUE FROM b_iblock_element_property where IBLOCK_ELEMENT_ID = PR.PRODUCT_ID and IBLOCK_PROPERTY_ID = {$skuPropertyId})";
		} else
		{
			$query = "(SELECT PROPERTY_{$skuPropertyId} FROM b_iblock_element_prop_s{$crmCatalogIBlockOfferId} where IBLOCK_ELEMENT_ID = PR.PRODUCT_ID)";
		}

		return $query;
	}
}