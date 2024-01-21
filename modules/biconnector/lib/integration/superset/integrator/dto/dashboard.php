<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

final class Dashboard
{
	public const STATUS_READY = 'ready';
	public const STATUS_PREPARE = 'prepare';
	public const STATUS_DATA_LOAD = 'data_load';

	public function __construct(
		public int $id,
		public string $title,
		public string $dashboardStatus,
		public string $url,
		public string $editUrl,
		public bool $isEditable,
		public array $nativeFilterConfig,
	)
	{}
}
