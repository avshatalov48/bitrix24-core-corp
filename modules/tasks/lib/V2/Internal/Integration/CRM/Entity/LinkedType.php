<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Entity;

enum LinkedType: string
{
	case Task = 'task';
	case Template = 'template';
}
