<?php

namespace Bitrix\HumanResources\Enum\HcmLink;

use Bitrix\HumanResources\Contract;

enum EntityEventName implements Contract\Enum\EventName
{
	case HCMLINK_EMPLOYEE_ADD;
	case HCMLINK_EMPLOYEE_UPDATE;
	case HCMLINK_EMPLOYEE_DELETE;
}
