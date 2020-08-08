<?php

namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

Loc::loadMessages(__FILE__);

/**
 * Class FilterLimit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit
 */
class FilterLimit extends Limit
{
	protected static $variableName = 'tasks_entity_search_limit';

	/**
	 * @param array|null $params
	 * @return array|null
	 */
	public static function prepareStubInfo(array $params = null): ?array
	{
		if ($params === null)
		{
			$params = [];
		}

		if (!isset($params['REPLACEMENTS']))
		{
			$params['REPLACEMENTS'] = [];
		}
		$params['REPLACEMENTS']['#LIMIT#'] = static::getVariable();

		$params['TITLE'] = ($params['TITLE']?: Loc::getMessage("TASKS_RESTRICTION_FILTER_LIMIT_TITLE"));
		$params['CONTENT'] = ($params['CONTENT']?: Loc::getMessage("TASKS_RESTRICTION_FILTER_LIMIT_TEXT"));

		return Bitrix24::prepareStubInfo($params);
	}
}