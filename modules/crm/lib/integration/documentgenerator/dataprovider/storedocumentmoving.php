<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Main\Localization\Loc;

/**
 * Class StoreDocumentMoving
 *
 * @package Bitrix\Crm\Integration\DocumentGenerator\DataProvider
 */
class StoreDocumentMoving extends StoreDocument
{
	/**
	 * @inheritDoc
	 */
	public static function getLangName()
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_MOVING_TITLE');
	}
}
