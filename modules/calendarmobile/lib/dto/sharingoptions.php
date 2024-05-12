<?php

namespace Bitrix\CalendarMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

final class SharingOptions extends Dto
{
	/** @var bool $sortJointLinksByFrequentUse */
	public $sortJointLinksByFrequentUse;

	public function getCasts(): array
	{
		return [
			'sortJointLinksByFrequentUse' => Type::bool(),
		];
	}
}