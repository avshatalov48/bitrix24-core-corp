<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Enum;

use Bitrix\Booking\Entity\ValuesTrait;

enum TemplateTypeReminder: string
{
	use ValuesTrait;

	case Base = 'base';
}
