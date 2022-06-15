<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Main\Localization\Loc;

/**
 * Class StoreDocumentStoreAdjustment
 *
 * @package Bitrix\Crm\Integration\DocumentGenerator\DataProvider
 */
class StoreDocumentStoreAdjustment extends StoreDocument
{
	/**
	 * @inheritDoc
	 */
	public static function getLangName()
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ADJUSTMENT_TITLE');
	}
}
