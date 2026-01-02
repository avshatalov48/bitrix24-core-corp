<?php

namespace Bitrix\Crm\History\StageHistoryWithSupposed;

enum CloseDateDirective
{
	case DoNothing;
	case SetNow;
	case Reset;
	case SetLastKnownInNew;
}
