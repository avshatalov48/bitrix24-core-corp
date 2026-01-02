<?php

namespace Bitrix\Crm\Integration\BizProc\Starter\Mixins\Dto;

final class TriggerBindingDocumentsDto
{
	public function __construct(
		public readonly int $entityTypeId,
		public readonly int $entityId,
		public readonly string $triggerCode,
		public readonly bool $isDynamicSearchAvailable = true,
		public readonly bool $searchClientBindings = true,
	)
	{}
}
