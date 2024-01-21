<?php

namespace Bitrix\Crm\Dto\MailTemplate;
final class AccessEntity
{
	public string $entityType;
	public int $entityCode;
	public function __construct(public int $entityId, string|int $entityCodeOrType)
	{
		if(is_int($entityCodeOrType))
		{
			$this->entityCode = $entityCodeOrType;
			$this->entityType = \Bitrix\Crm\MailTemplate\MailTemplateAccess::getTypeByCode($entityCodeOrType);
		}
		else
		{
			$this->entityType = $entityCodeOrType;
			$this->entityCode = \Bitrix\Crm\MailTemplate\MailTemplateAccess::getCodeByType($entityCodeOrType);
		}
	}
}