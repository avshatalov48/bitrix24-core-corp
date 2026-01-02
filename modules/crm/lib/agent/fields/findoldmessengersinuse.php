<?php

namespace Bitrix\Crm\Agent\Fields;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\Multifield\Type\Im;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;

class FindOldMessengersInUse extends AgentBase
{
	public static function doRun(): void
	{
		$oldMessengersInUse = FieldMultiTable::query()
			->setSelect(['VALUE_TYPE'])
			->setDistinct()
			->where('TYPE_ID', 'IM')
			->whereIn('VALUE_TYPE', Im::DEPRECATED_MESSENGERS)
			->fetchAll()
		;

		$oldMessengersInUse = array_column($oldMessengersInUse, 'VALUE_TYPE');

		if (!empty($oldMessengersInUse))
		{
			$oldMessengersInUse = Json::encode($oldMessengersInUse);
			Option::set('crm', 'old_messengers_in_use', $oldMessengersInUse);
		}
	}
}
