<?php

namespace Bitrix\Crm\Entity\MessageBuilder;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ProcessTodoActivity extends BaseBuilder
{
	public const PROCESS_ACTIVITY_PING = 'PING';
	protected const MESSAGE_BASE_PREFIX = 'CRM_PROCESS_TODO_ACTIVITY';

	/**
	 * Supported phrase codes:
	 * 	CRM_PROCESS_TODO_ACTIVITY_LEAD_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_DEAL_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_RECURRING_DEAL_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_ORDER_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_CONTACT_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_COMPANY_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_SMART_INVOICE_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_QUOTE_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_DYNAMIC_PING
	 * 	CRM_PROCESS_TODO_ACTIVITY_DEFAULT_PING
	 */
	public function buildCode(): string
	{
		return sprintf(
			"%s_%s_%s",
			static::MESSAGE_BASE_PREFIX,
			$this->fetchEntityTypeName(),
			static::PROCESS_ACTIVITY_PING
		);
	}

	final protected function buildDefaultCode(): string
	{
		return sprintf(
			"%s_DEFAULT_%s",
			static::MESSAGE_BASE_PREFIX,
			static::PROCESS_ACTIVITY_PING
		);
	}

	public static function getFilePath(): string
	{
		return __FILE__;
	}
}
