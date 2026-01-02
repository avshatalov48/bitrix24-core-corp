<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Entity\Enum;

enum PackageDistributionStrategy: string
{
	case BY_BAAS = 'default';
	case BY_MARKET = 'market';
}
