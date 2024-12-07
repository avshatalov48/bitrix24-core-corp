<?php

namespace Bitrix\Crm\Entity\MessageBuilder;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Supported phrase codes:
 * 	CRM_PROCESS_ENTITY_LEAD_STAGE_CHANGE
 * 	CRM_PROCESS_ENTITY_DEAL_STAGE_CHANGE
 * 	CRM_PROCESS_ENTITY_ORDER_STAGE_CHANGE
 * 	CRM_PROCESS_ENTITY_SMART_INVOICE_STAGE_CHANGE
 * 	CRM_PROCESS_ENTITY_QUOTE_STAGE_CHANGE_MSGVER_1
 * 	CRM_PROCESS_ENTITY_DYNAMIC_STAGE_CHANGE
 * 	CRM_PROCESS_ENTITY_DEFAULT_STAGE_CHANGE
 */
class ProcessEntityStage extends BaseBuilder
{
	public const PROCESS_STAGE_CHANGE = 'STAGE_CHANGE';

	public function buildCode(): string
	{
		return sprintf(
			"%s_%s_%s",
			static::MESSAGE_BASE_PREFIX,
			$this->fetchEntityTypeName(),
			static::PROCESS_STAGE_CHANGE
		);
	}

	final protected function buildDefaultCode(): string
	{
		return sprintf(
			"%s_DEFAULT_%s",
			static::MESSAGE_BASE_PREFIX,
			static::PROCESS_STAGE_CHANGE
		);
	}

	public static function getFilePath(): string
	{
		return __FILE__;
	}
}
