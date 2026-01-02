<?php

namespace Bitrix\Crm\Integration\BizProc\Starter\Dto;

final class DocumentDto
{
	public function __construct(
		public readonly int $entityTypeId,
		public readonly int $entityId,
	)
	{}
}
