<?php

namespace Bitrix\Crm\Integration\BizProc\Starter\Dto;

final class RunDataDto
{
	public function __construct(
		public readonly ?array $actualFields = null,
		public readonly ?array $previousFields = null,
		/** @var EventDto[] */
		public readonly array $events = [],
		public readonly int $userId = 0,
		public readonly array | string | null $parameters = null,
		public readonly string $scope = '',
	)
	{}
}
