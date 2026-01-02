<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

enum Scenario: string
{
	case Default = 'default';
	case Crm = 'crm';
	case Mobile = 'mobile';
}