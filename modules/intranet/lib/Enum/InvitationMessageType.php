<?php

namespace Bitrix\Intranet\Enum;

enum InvitationMessageType: string
{
	case INVITE = 'INVITE'; // user invitation letter
	case JOIN = 'JOIN'; // user joining letter
}