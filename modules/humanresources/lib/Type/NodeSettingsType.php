<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

/**
 * Types for NodeSettingsTable to determine which setting is stored in a row
 */
enum NodeSettingsType: string
{
	case BusinessProcAuthority = 'BUSINESS_PROC_AUTHORITY';
	case ReportsAuthority = 'REPORTS_AUTHORITY';

	public static function getCasesWithAuthorityTypeValue()
	{
		return [
			self::BusinessProcAuthority,
			self::ReportsAuthority,
		];
	}

	use ValuesTrait;
}
