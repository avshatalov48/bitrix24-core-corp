<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\DB\MysqliSqlHelper;
use Bitrix\Main\DB\PgsqlSqlHelper;

class DynamicItemsProductMapping
{
	use ProductDataHelperTrait;

	public static function getMapping(MysqliSqlHelper|PgsqlSqlHelper $helper, string $languageId): array
	{
		$types = TypeTable::query()->setSelect(['ENTITY_TYPE_ID', 'TITLE'])->fetchCollection();

		$discountSql = self::getDiscountSql($helper);
		$parentData = self::getParentDescription($helper);
		$parentData['FIELD_DESCRIPTION'] = Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PARENT', $languageId);
		$superParentData = self::getSuperParentDescription($helper);
		$superParentData['FIELD_DESCRIPTION'] = Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_SUPERPARENT', $languageId);
		$superSuperParentData = self::getSuperSuperParentDescription($helper);
		$superSuperParentData['FIELD_DESCRIPTION'] = Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_SUPERSUPERPARENT', $languageId);

		$result = [];
		foreach ($types as $type)
		{
			$result['crm_dynamic_items_prod_' . $type->getEntityTypeId()] = [
				'TABLE_NAME' => 'b_crm_product_row',
				'TABLE_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_TABLE', $languageId, ['#TITLE#' => $type->getTitle()]) ?? $type->getTitle(),
				'TABLE_ALIAS' => 'PR',
				'FILTER' => [
					'=OWNER_TYPE' => \CCrmOwnerTypeAbbr::ResolveByTypeID($type->getEntityTypeId()),
				],
				'FILTER_FIELDS' => [
					'OWNER_TYPE' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'PR.OWNER_TYPE',
						'FIELD_TYPE' => 'string',
					],
				],
				'FIELDS' => [
					'ID' => [
						'IS_PRIMARY' => 'Y',
						'FIELD_NAME' => 'PR.ID',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_ID', $languageId),
					],
					'ITEM_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'PR.OWNER_ID',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_ITEM_ID', $languageId),
					],
					'PRODUCT' => [
						'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.PRODUCT_ID', '\']\'') . ', nullif(PR.PRODUCT_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRODUCT', $languageId),
					],
					'PRODUCT_ID' => [
						'FIELD_NAME' => 'PR.PRODUCT_ID',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRODUCT_ID', $languageId),
					],
					'PRODUCT_NAME' => [
						'FIELD_NAME' => 'PR.PRODUCT_NAME',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRODUCT_NAME', $languageId),
					],
					'PRICE' => [
						'FIELD_NAME' => 'PR.PRICE',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE', $languageId),
					],
					'PRICE_EXCLUSIVE' => [
						'FIELD_NAME' => 'PR.PRICE_EXCLUSIVE',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE_EXCLUSIVE', $languageId),
					],
					'PRICE_NETTO' => [
						'FIELD_NAME' => 'PR.PRICE_NETTO',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE_NETTO', $languageId),
					],
					'PRICE_BRUTTO' => [
						'FIELD_NAME' => 'PR.PRICE_BRUTTO',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE_BRUTTO', $languageId),
					],
					'QUANTITY' => [
						'FIELD_NAME' => 'PR.QUANTITY',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_QUANTITY', $languageId),
					],
					'DISCOUNT_TYPE' => [
						'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.DISCOUNT_TYPE_ID', '\']\'') . ', ' . str_replace('#FIELD_NAME#', 'PR.DISCOUNT_TYPE_ID', $discountSql) . ')',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_TYPE', $languageId),
					],
					'DISCOUNT_TYPE_ID' => [
						'FIELD_NAME' => 'PR.DISCOUNT_TYPE_ID',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_TYPE_ID', $languageId),
					],
					'DISCOUNT_TYPE_NAME' => [
						'FIELD_NAME' => str_replace('#FIELD_NAME#', 'PR.DISCOUNT_TYPE_ID', $discountSql),
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_TYPE_NAME', $languageId),
					],
					'DISCOUNT_RATE' => [
						'FIELD_NAME' => 'PR.DISCOUNT_RATE',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_RATE', $languageId),
					],
					'DISCOUNT_SUM' => [
						'FIELD_NAME' => 'PR.DISCOUNT_SUM',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_SUM', $languageId),
					],
					'TAX_RATE' => [
						'FIELD_NAME' => 'PR.TAX_RATE',
						'FIELD_TYPE' => 'double',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_TAX_RATE', $languageId),
					],
					'TAX_INCLUDED' => [
						'FIELD_NAME' => 'PR.TAX_INCLUDED',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_TAX_INCLUDED', $languageId),
					],
					'CUSTOMIZED' => [
						'FIELD_NAME' => 'PR.CUSTOMIZED',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_CUSTOMIZED', $languageId),
						'FIELD_DESCRIPTION_FULL' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_CUSTOMIZED_FULL', $languageId),
					],
					'MEASURE' => [
						'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.MEASURE_CODE', '\']\'') . ', nullif(PR.MEASURE_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_MEASURE', $languageId),
					],
					'MEASURE_CODE' => [
						'FIELD_NAME' => 'PR.MEASURE_CODE',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_MEASURE_CODE', $languageId),
					],
					'MEASURE_NAME' => [
						'FIELD_NAME' => 'PR.MEASURE_NAME',
						'FIELD_TYPE' => 'string',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_MEASURE_NAME', $languageId),
					],
					'SORT' => [
						'FIELD_NAME' => 'PR.SORT',
						'FIELD_TYPE' => 'int',
						'FIELD_DESCRIPTION' => Localization::getMessage('CRM_DYNAMIC_ITEMS_PROD_FIELD_SORT', $languageId),
					],
					'PARENT' => $parentData,
					'SUPERPARENT' => $superParentData,
					'SUPERSUPERPARENT' => $superSuperParentData,
				],
			];
		}

		return $result;
	}
}