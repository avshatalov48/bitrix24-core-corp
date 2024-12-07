<?php declare(strict_types=1);

namespace Bitrix\AI\Limiter\Enums;

enum ErrorLimit: string
{
	case PROMO_LIMIT = 'PROMO_LIMIT';
	case BAAS_LIMIT = 'BAAS_LIMIT';
}
