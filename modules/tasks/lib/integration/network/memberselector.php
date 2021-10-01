<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Integration\Network;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util;

class MemberSelector
{
	public const OPTION_SELECT_NETWORK = 'network_enabled';

	public static function isNetworkEnabled(): bool
	{
		if (
			Util::getOption('test_tasks_network_disabled') === 'Y'
			|| time() > mktime(0, 0, 0, 9, 1, 2021)
		)
		{
			return false;
		}

		return
			!Loader::includeModule('bitrix24')
			|| Feature::isFeatureEnabled(FeatureDictionary::TASKS_NETWORK)
		;
	}
}