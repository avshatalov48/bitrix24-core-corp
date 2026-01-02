<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Enum\TemplateType;

use Bitrix\Booking\Entity\ValuesTrait;

enum TemplateTypeInfo: string
{
	use ValuesTrait;

	case Animate = 'animate';
	case InAnimate = 'inanimate';
}
