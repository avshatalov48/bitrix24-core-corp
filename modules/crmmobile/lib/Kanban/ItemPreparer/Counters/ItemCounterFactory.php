<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer\Counters;

use Bitrix\Crm\Settings\CounterSettings;

class ItemCounterFactory
{
	public static function make(): ItemCounters
	{
		if (CounterSettings::getInstance()->useActivityResponsible())
		{
			return new ItemCounterActivityResponsible();
		}
		else
		{
			return new ItemCounterEntityResponsible();
		}
	}
}