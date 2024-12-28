<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Enum;

enum AutoCheckType: int
{
	case DISABLED = 0;
	case FIRST_INCOMING = 1;
	case INCOMING = 2;
	case OUTGOING = 3;
	case ALL = 4;
}