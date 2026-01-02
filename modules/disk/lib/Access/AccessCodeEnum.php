<?php

declare(strict_types=1);

namespace Bitrix\Disk\Access;

enum AccessCodeEnum: string
{
	case AUTHORIZED_USER = 'AU';
	case CREATOR = 'CR';
	case DEPARTMENT = 'DR';
	case GROUP = 'G';
	case SOCNET_GROUP = 'SG';
	case USER = 'U';
}
