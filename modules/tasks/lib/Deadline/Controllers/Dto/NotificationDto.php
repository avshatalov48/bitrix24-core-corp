<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Controllers\Dto;

use Bitrix\Tasks\Deadline\SkipNotificationPeriod;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Dto\AbstractBaseDto;

/**
 * @method self setSkipPeriod(SkipNotificationPeriod $period)
 */
class NotificationDto extends AbstractBaseDto
{
	#[Required]
	public SkipNotificationPeriod $skipPeriod;
}
