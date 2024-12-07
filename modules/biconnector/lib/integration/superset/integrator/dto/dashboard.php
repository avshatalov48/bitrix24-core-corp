<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

use Bitrix\Main\Type\DateTime;

final class Dashboard
{
	public function __construct(
		public int $id,
		public string $title,
		public string $url,
		public string $editUrl,
		public bool $isEditable,
		public bool $published,
		public array $nativeFilterConfig,
		public ?DateTime $dateModify,
	)
	{}
}
