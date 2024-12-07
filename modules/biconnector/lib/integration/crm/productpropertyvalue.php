<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class ProductPropertyValue
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

		$crmCatalogIblock = \Bitrix\Iblock\IblockTable::getRow([
			'select' => ['VERSION'],
			'filter' => [
				'=ID' => $crmCatalogIblockId,
			],
		]);
		$crmCatalogIblockVersion =
			$crmCatalogIblock && $crmCatalogIblock['VERSION']
				? (int)$crmCatalogIblock['VERSION']
				: 1
		;
		$crmCatalogIblockOffer = \Bitrix\Iblock\IblockTable::getRow([
			'select' => ['VERSION'],
			'filter' => [
				'=ID' => $crmCatalogIblockOfferId,
			],
		]);
		$crmCatalogIblockOfferVersion =
			$crmCatalogIblockOffer && $crmCatalogIblockOffer['VERSION']
				? (int)$crmCatalogIblockOffer['VERSION']
				: 1
		;

		$params = $event->getParameters();
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];
		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		if ($crmCatalogIblockVersion === 1 && $crmCatalogIblockOfferVersion === 1)
		{
			$result['crm_product_property_value'] = [
				'TABLE_NAME' => 'b_iblock_element_property',
				'TABLE_ALIAS' => 'IEP',
				'FILTER' => [
					'=IBLOCK_ID' => [$crmCatalogIblockId, $crmCatalogIblockOfferId],
				],
				'FILTER_FIELDS' => [
					'IBLOCK_ID' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IP.IBLOCK_ID',
						'FIELD_TYPE' => 'int',
						'TABLE_ALIAS' => 'IP',
						'JOIN' => 'INNER JOIN b_iblock_property IP ON IP.ID = IEP.IBLOCK_PROPERTY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_iblock_property IP ON IP.ID = IEP.IBLOCK_PROPERTY_ID',
					],
				],
				'FIELDS' => [
					'ID' => [
						'FIELD_NAME' => 'IEP.ID',
						'FIELD_TYPE' => 'string',
					],
					'PRODUCT_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_ELEMENT_ID',
						'FIELD_TYPE' => 'int',
					],
					'PROPERTY_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_PROPERTY_ID',
						'FIELD_TYPE' => 'int',
					],
					'VALUE' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IEP.VALUE',
						'FIELD_TYPE' => 'string',
					],
				],
			];
		}
		elseif ($crmCatalogIblockVersion === 1 && $crmCatalogIblockOfferVersion === 2)
		{
			$result['crm_product_property_value'] = [
				'TABLE_NAME' => 'b_iblock_element_property',
				'TABLE_ALIAS' => 'IEP',
				'FILTER' => [
					'=IBLOCK_ID' => [$crmCatalogIblockId],
				],
				'FILTER_FIELDS' => [
					'IBLOCK_ID' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IP.IBLOCK_ID',
						'FIELD_TYPE' => 'int',
						'TABLE_ALIAS' => 'IP',
						'JOIN' => 'INNER JOIN b_iblock_property IP ON IP.ID = IEP.IBLOCK_PROPERTY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_iblock_property IP ON IP.ID = IEP.IBLOCK_PROPERTY_ID',
					],
				],
				'FIELDS' => [
					'ID' => [
						'FIELD_NAME' => 'IEP.ID',
						'FIELD_TYPE' => 'string',
					],
					'PRODUCT_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_ELEMENT_ID',
						'FIELD_TYPE' => 'int',
					],
					'PROPERTY_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_PROPERTY_ID',
						'FIELD_TYPE' => 'int',
					],
					'VALUE' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IEP.VALUE',
						'FIELD_TYPE' => 'string',
					],
				],
				'UNION' => self::getMultipleUnion($helper, $crmCatalogIblockOfferId) . ' ' . self::getSingleUnion($helper, $crmCatalogIblockOfferId),
			];
		}
		elseif ($crmCatalogIblockVersion === 2 && $crmCatalogIblockOfferVersion === 1)
		{
			$result['crm_product_property_value'] = [
				'TABLE_NAME' => 'b_iblock_element_property',
				'TABLE_ALIAS' => 'IEP',
				'FILTER' => [
					'=IBLOCK_ID' => [$crmCatalogIblockOfferId],
				],
				'FILTER_FIELDS' => [
					'IBLOCK_ID' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IP.IBLOCK_ID',
						'FIELD_TYPE' => 'int',
						'TABLE_ALIAS' => 'IP',
						'JOIN' => 'INNER JOIN b_iblock_property IP ON IP.ID = IEP.IBLOCK_PROPERTY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_iblock_property IP ON IP.ID = IEP.IBLOCK_PROPERTY_ID',
					],
				],
				'FIELDS' => [
					'ID' => [
						'FIELD_NAME' => 'IEP.ID',
						'FIELD_TYPE' => 'string',
					],
					'PRODUCT_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_ELEMENT_ID',
						'FIELD_TYPE' => 'int',
					],
					'PROPERTY_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_PROPERTY_ID',
						'FIELD_TYPE' => 'int',
					],
					'VALUE' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IEP.VALUE',
						'FIELD_TYPE' => 'string',
					],
				],
				'UNION' => self::getMultipleUnion($helper, $crmCatalogIblockId) . ' ' . self::getSingleUnion($helper, $crmCatalogIblockId),
			];
		}
		elseif ($crmCatalogIblockVersion === 2 && $crmCatalogIblockOfferVersion === 2)
		{
			$result['crm_product_property_value'] = [
				'TABLE_NAME' => 'b_iblock_element_prop_m' . $crmCatalogIblockId,
				'TABLE_ALIAS' => 'IEP',
				'FIELDS' => [
					'ID' => [
						'IS_PRIMARY' => 'Y',
						'FIELD_NAME' => 'IEP.ID',
						'FIELD_TYPE' => 'string',
					],
					'PRODUCT_ID' => [
						'FIELD_NAME' => 'IEP.IBLOCK_ELEMENT_ID',
						'FIELD_TYPE' => 'int',
					],
					'PROPERTY_ID' => [
						'IS_PRIMARY' => 'Y',
						'FIELD_NAME' => 'IEP.IBLOCK_PROPERTY_ID',
						'FIELD_TYPE' => 'int',
					],
					'VALUE' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'IEP.VALUE',
						'FIELD_TYPE' => 'string',
					],
				],
				'UNION' =>
					self::getSingleUnion($helper, $crmCatalogIblockId) . ' ' .
					self::getMultipleUnion($helper, $crmCatalogIblockOfferId) . ' ' .
					self::getSingleUnion($helper, $crmCatalogIblockOfferId)
			];
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_product_property_value']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_PRODUCT_PROPERTY_VALUE_TABLE'] ?? 'crm_product_property_value';
		foreach ($result['crm_product_property_value']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_PRODUCT_PROPERTY_VALUE_FIELD_' . $fieldCode] ?? null;
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_PRODUCT_PROPERTY_VALUE_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}

	private static function getSingleUnion($helper, $iblockId): string
	{
		$propertyTableRecords = \Bitrix\Iblock\PropertyTable::getList([
			'select' => ['ID'],
			'filter' => ['=IBLOCK_ID' => $iblockId, '=MULTIPLE' => 'N'],
		])->fetchAll();

		$singlePropertyIds = array_map(static fn($property) => (int)$property['ID'], $propertyTableRecords);
		$union = '';
		foreach ($singlePropertyIds as $singlePropertyId)
		{
			$union .= 'UNION ALL SELECT ' . $helper->getConcatFunction('IBLOCK_ELEMENT_ID', '\':\'', $singlePropertyId) . ' AS ID, IBLOCK_ELEMENT_ID AS PRODUCT_ID, \'' . $singlePropertyId . '\' as PROPERTY_ID, PROPERTY_' . $singlePropertyId . ' as VALUE from b_iblock_element_prop_s' . $iblockId . '
			';
		}

		return $union;
	}

	private static function getMultipleUnion($helper, int $iblockId): string
	{
		return 'UNION ALL SELECT ' . $helper->getConcatFunction('IEP.IBLOCK_ELEMENT_ID', '\':\'', 'IEP.IBLOCK_PROPERTY_ID', '\':\'', 'IEP.ID') . ' AS ID, IEP.IBLOCK_ELEMENT_ID AS PRODUCT_ID, IEP.IBLOCK_PROPERTY_ID AS PROPERTY_ID, IEP.VALUE FROM b_iblock_element_prop_m' . $iblockId . ' AS IEP';
	}
}
