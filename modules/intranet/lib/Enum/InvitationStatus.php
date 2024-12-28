<?php

namespace Bitrix\Intranet\Enum;

enum InvitationStatus: string
{
	case NOT_REGISTERED = 'NOT_REGISTERED'; // user not found
	case INVITED = 'INVITED'; // active = Y and confirm_comde = not_empty
	case INVITE_AWAITING_APPROVE = 'INVITE_AWAITING_APPROVE'; // active = N and confirm_code = not_empty
	case ACTIVE = 'ACTIVE'; // active = Y and confirm_code = empty
	case FIRED = 'FIRED'; // active = N and confirm_code = empty
}
