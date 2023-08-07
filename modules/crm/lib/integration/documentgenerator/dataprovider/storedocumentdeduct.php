<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Main\Localization\Loc;

class StoreDocumentDeduct extends StoreDocument
{
	/**
	 * @inheritDoc
	 */
	public static function getLangName()
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_DEDUCT_TITLE');
	}
}
