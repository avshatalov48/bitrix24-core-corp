<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Template;

use Bitrix\Tasks\Internals\Counter\CounterDictionary;

class ComponentAssistant
{

	/**
	 * @param array $counters
	 * @return array
	 */
	public static function getRowCounter(array $counters): array
	{
		$res = [
			'COLOR' => 'gray',
			'VALUE' => 0,
		];

		if (empty($counters))
		{
			return $res;
		}

		if (isset($counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS]) && $counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS])
		{
			$res['COLOR'] = 'success';
			$res['VALUE'] = $counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS];
		}
		if (isset($counters[CounterDictionary::COUNTER_MY_EXPIRED]) && $counters[CounterDictionary::COUNTER_MY_EXPIRED])
		{
			$res['COLOR'] = 'danger';
			$res['VALUE']++;
		}

		if (!$res['VALUE'])
		{
			if (isset($counters[CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS]))
			{
				$res['VALUE'] = $counters[CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS];
			}
			if (isset($counters[CounterDictionary::COUNTER_MY_MUTED_EXPIRED]) && $counters[CounterDictionary::COUNTER_MY_MUTED_EXPIRED])
			{
				$res['VALUE']++;
			}
		}

		if (!$res['VALUE'])
		{
			if (isset($counters[CounterDictionary::COUNTER_PROJECT_COMMENTS]))
			{
				$res['VALUE'] = $counters[CounterDictionary::COUNTER_PROJECT_COMMENTS];
			}
			if (isset($counters[CounterDictionary::COUNTER_PROJECT_EXPIRED]) && $counters[CounterDictionary::COUNTER_PROJECT_EXPIRED])
			{
				$res['VALUE']++;
			}
		}

		return $res;
	}

}