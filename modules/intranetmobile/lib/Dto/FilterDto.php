<?php

namespace Bitrix\IntranetMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

class FilterDto extends Dto
{
	public const ALL_DEPARTMENTS = 0;

	public function __construct(
		public readonly string $searchString = '',
		public readonly ?string $presetId = null,
		public readonly int $department = self::ALL_DEPARTMENTS,
	)
	{
		parent::__construct();
	}
}