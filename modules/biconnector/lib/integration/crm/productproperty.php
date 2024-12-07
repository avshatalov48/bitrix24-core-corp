<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class ProductProperty
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

		$params = $event->getParameters();
		$result = &$params[1];
		$languageId = $params[2];
		$result['crm_product_property'] = [
			'TABLE_NAME' => 'b_iblock_property',
			'TABLE_ALIAS' => 'IP',
			'FILTER' => [
				'=IBLOCK_ID' => [$crmCatalogIblockId, $crmCatalogIblockOfferId],
			],
			'FILTER_FIELDS' => [
				'IBLOCK_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'IP.IBLOCK_ID',
					'FIELD_TYPE' => 'int',
				],
			],
			'FIELDS' => [
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'IP.ID',
					'FIELD_TYPE' => 'int',
				],
				'NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'IP.NAME',
					'FIELD_TYPE' => 'string',
				],
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_product_property']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_PRODUCT_PROPERTY_TABLE'] ?? 'crm_product_property';
		foreach ($result['crm_product_property']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_PRODUCT_PROPERTY_FIELD_' . $fieldCode] ?? null;
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_PRODUCT_PROPERTY_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}
}
