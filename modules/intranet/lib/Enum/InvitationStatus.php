<?php

namespace Bitrix\Intranet\Enum;

enum InvitationStatus
{
	case NOT_REGISTERED; // user not found
	case INVITED; // active = Y and confirm_comde = not_empty
	case INVITE_AWAITING_APPROVE; // active = N and confirm_code = not_empty
	case ACTIVE; // active = Y and confirm_code = empty
	case FIRED; // active = N and confirm_code = empty
}
