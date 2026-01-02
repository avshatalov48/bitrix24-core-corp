<?php

namespace Bitrix\Mobile\Profile\Enum;

enum UserStatus: string
{
	case ONLINE = 'ONLINE';
	case FIRED = 'FIRED';
	case DND = 'DND';
	case ON_VACATION = 'ON_VACATION';
	case OFFLINE = 'OFFLINE';
}
