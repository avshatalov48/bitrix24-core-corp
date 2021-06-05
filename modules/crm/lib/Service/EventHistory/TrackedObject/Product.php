<?php

namespace Bitrix\Crm\Service\EventHistory\TrackedObject;

use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Main\Localization\Loc;

/**
 * Class Product
 *
 * @property ProductRow $objectBeforeSave
 * @property ProductRow $object
 */
class Product extends TrackedObject
{
	protected static function getEntityTitleMethod(): string
	{
		return 'getProductName';
	}

	protected function getTrackedRegularFieldNames(): array
	{
		return [
			'PRODUCT_NAME',
			'PRICE',
			'QUANTITY',
			'DISCOUNT_SUM',
			'TAX_RATE',
			'MEASURE_NAME',
		];
	}

	protected function getDependantUpdateEventName(string $fieldName): string
	{
		return Loc::getMessage(
			'CRM_TRACKED_OBJECT_PRODUCT_DEPENDANT_UPDATE_TEXT',
			['#FIELD_NAME#' => $this->getFieldNameCaption($fieldName), '#PRODUCT_NAME#' => $this->getEntityTitle()]
		);
	}

	protected function getFieldNameCaption(string $fieldName): string
	{
		return Loc::getMessage('CRM_TRACKED_OBJECT_PRODUCT_FIELD_NAME_'.$fieldName) ?? $fieldName;
	}

	protected function getFieldValueCaption(string $fieldName, $fieldValue, string $actualOrCurrent = null): string
	{
		//todo Temporary decision. Can't use \Bitrix\Crm\Service\Localization::getFieldValueCaption because there's no factory for products
		if ($fieldName === 'TAX_RATE')
		{
			return number_format($fieldValue, 2, ',', '').'%';
		}

		if (is_numeric($fieldValue) || is_int($fieldValue) || is_float($fieldValue))
		{
			return number_format($fieldValue, 2, ',', '');
		}

		if (empty($fieldValue))
		{
			return Loc::getMessage('CRM_COMMON_EMPTY');
		}

		return (string)$fieldValue;
	}
}