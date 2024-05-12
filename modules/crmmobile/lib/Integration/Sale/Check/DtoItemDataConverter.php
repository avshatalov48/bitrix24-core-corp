<?php

namespace Bitrix\CrmMobile\Integration\Sale\Check;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Cashbox\Check;

class DtoItemDataConverter
{
	public static function convert(Check $check): DtoItemData
	{
		/** @var DateTime|null $dateCreate */
		$dateCreate = $check->getField('DATE_CREATE');

		$itemData = DtoItemData::make([
			'id' => (int)$check->getField('ID'),
			'date' => $dateCreate?->getTimestamp(),
			'name' => '',
			'url' => $check->getUrl(),
			'fields' => [],
		]);

		return $itemData;
	}
}
