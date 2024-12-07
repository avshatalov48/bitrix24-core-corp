<?php

namespace Bitrix\Intranet\Enum;

enum InvitationType: string
{
	case EMAIL = 'email';
	case PHONE = 'phone';
}