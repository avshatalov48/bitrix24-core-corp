<?php

namespace Bitrix\Crm\Entity\MessageBuilder;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Supported phrase codes:
 * 	CRM_PROCESS_ENTITY_LEAD_ADD
 * 	CRM_PROCESS_ENTITY_DEAL_ADD
 * 	CRM_PROCESS_ENTITY_RECURRING_DEAL_ADD
 * 	CRM_PROCESS_ENTITY_ORDER_ADD
 * 	CRM_PROCESS_ENTITY_ORDER_PAYMENT_ADD
 * 	CRM_PROCESS_ENTITY_ORDER_SHIPMENT_ADD
 * 	CRM_PROCESS_ENTITY_CONTACT_ADD
 * 	CRM_PROCESS_ENTITY_COMPANY_ADD
 * 	CRM_PROCESS_ENTITY_SMART_INVOICE_ADD
 * 	CRM_PROCESS_ENTITY_QUOTE_ADD_MSGVER_1
 * 	CRM_PROCESS_ENTITY_DYNAMIC_ADD
 * 	CRM_PROCESS_ENTITY_DEFAULT_ADD
 */
class ProcessEntity extends BaseBuilder
{
	public const PROCESS_ADD = 'ADD';

	private string $type;

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function buildCode(): string
	{
		return sprintf(
			"%s_%s_%s",
			static::MESSAGE_BASE_PREFIX,
			$this->fetchEntityTypeName(),
			$this->type
		);
	}

	final protected function buildDefaultCode(): string
	{
		return sprintf(
			"%s_DEFAULT_%s",
			static::MESSAGE_BASE_PREFIX,
			$this->type
		);
	}

	public static function getFilePath(): string
	{
		return __FILE__;
	}
}
