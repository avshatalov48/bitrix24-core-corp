<?php

namespace Bitrix\Crm\RepeatSale\Statistics;

enum PeriodType: int
{
	case Day30 = 0;
	case Quarter = 1;
	case HalfYear = 2;
	case Year = 3;
}
