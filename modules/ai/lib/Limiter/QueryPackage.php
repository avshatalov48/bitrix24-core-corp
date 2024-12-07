<?php
declare(strict_types=1);

namespace Bitrix\AI\Limiter;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\Main\Loader;

/**
 * Pay attention to the order of the constants in the enum.
 * First, the largest package, then the smaller ones.
 * Suffix _1 - for the RU-zone, _2 - for the West-zone.
 */
enum QueryPackage: string
{
	public const DEFAULT_MAX_USAGE = 6000;

	case XL_WEST = 'ai_free_query_package_xl_west';
	case L_WEST = 'ai_free_query_package_l_west';
	case M_WEST = 'ai_free_query_package_m_west';
	case S_WEST = 'ai_free_query_package_s_west';
	case XS_WEST = 'ai_free_query_package_xs_west';

	case M_CIS = 'ai_free_query_package_m_cis';
	case S_CIS = 'ai_free_query_package_s_cis';
	case XS_CIS = 'ai_free_query_package_xs_cis';

	public function getMaxUsageValue(): int
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return self::DEFAULT_MAX_USAGE;
		}

		return Bitrix24::getVariable($this->value);
	}
}
