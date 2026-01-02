<?php

namespace Bitrix\Crm\RepeatSale\DataCollector;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;

final class CopilotMarkerLimitManager
{
	use Singleton;

	private const DEAL_FIELDS_MIN_LIMIT_OPTION_NAME = 'ai_integration_repeat_sale_marker_min_limit_value';
	private const DEAL_FIELDS_MIN_LIMIT_VALUE = 500;

	private const COMMUNICATION_FIELDS_LIMIT_OPTION_NAME = 'ai_integration_repeat_sale_marker_crm_data_sufficient_value';
	private const COMMUNICATION_FIELDS_LIMIT_VALUE = 2500;

	public function getDealFieldsMinLimit(): int
	{
		return (int)Option::get(
			'crm',
			self::DEAL_FIELDS_MIN_LIMIT_OPTION_NAME,
			self::DEAL_FIELDS_MIN_LIMIT_VALUE
		);
	}

	public function getCommunicationFieldsLimit(): int
	{
		return (int)Option::get(
			'crm',
			self::COMMUNICATION_FIELDS_LIMIT_OPTION_NAME,
			self::COMMUNICATION_FIELDS_LIMIT_VALUE
		);
	}
}
