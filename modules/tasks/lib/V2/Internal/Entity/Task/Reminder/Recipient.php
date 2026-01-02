<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;

enum Recipient: string
{
	case Responsible = 'responsible';
	case Creator = 'creator';
	case Accomplice = 'accomplice';
	case Myself = 'myself';
}