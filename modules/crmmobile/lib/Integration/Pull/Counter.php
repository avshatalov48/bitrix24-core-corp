<?php

namespace Bitrix\CrmMobile\Integration\Pull;

use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Counter
{
	private const TYPE_CRM = 'all_no_orders';
	private const MODULE_ID = 'crm';

	public static function onGetMobileCounterTypes(Event $event): EventResult
	{
		return new EventResult(EventResult::SUCCESS, [
			self::TYPE_CRM => [
				'NAME' => Loc::getMessage('M_CRM_INTEGRATION_PULL_COUNTER_TYPE'),
				'DEFAULT' => false,
			],
		], self::MODULE_ID);
	}

	public static function onGetMobileCounter(Event $event): EventResult
	{
		$params = $event->getParameters();
		$counter = EntityCounterManager::prepareValue('crm_all_no_orders', $params['USER_ID'] ?? 0);

		return new EventResult(EventResult::SUCCESS, [
			'TYPE' => self::TYPE_CRM,
			'COUNTER' => $counter,
		], self::MODULE_ID);
	}
}
