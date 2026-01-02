<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Enum;

use Bitrix\Booking\Entity\ValuesTrait;

enum TemplateTypeFeedback: string
{
	use ValuesTrait;

	case Animate = 'animate';
	case InAnimate = 'inanimate';
}
