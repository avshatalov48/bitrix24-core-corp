<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Template;

enum Period: string
{
	case Daily = 'daily';
	case Weekly = 'weekly';
	case Monthly = 'monthly';
	case Yearly = 'yearly';
}
