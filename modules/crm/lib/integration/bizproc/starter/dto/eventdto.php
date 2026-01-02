<?php

namespace Bitrix\Crm\Integration\BizProc\Starter\Dto;

final class EventDto
{
	public function __construct(
		public readonly string $triggerCode,
		/** @var DocumentDto[] */
		public readonly array $documents,
		public readonly array $parameters = [],
	)
	{}
}
