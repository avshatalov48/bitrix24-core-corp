<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Main\Config\Option;

final class LighterFactory
{
	public static function makeQueries(): LighterQueries
	{
		$lighterCommonReady = Option::get('crm', 'enable_act_counter_light', 'Y') === 'Y';

		if ($lighterCommonReady)
		{
			return new LighterQueriesCommon();
		}
		else
		{
			return new LighterQueriesTransition();
		}
	}

	public static function make(): Lighter
	{
		$queries = self::makeQueries();
		return new Lighter($queries);
	}
}