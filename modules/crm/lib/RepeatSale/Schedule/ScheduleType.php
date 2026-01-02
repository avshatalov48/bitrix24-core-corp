<?php

namespace Bitrix\Crm\RepeatSale\Schedule;

enum ScheduleType: int
{
	case Regular = 1; // once per day, every day
	case Scheduling = 2; // @todo for the future
	case Once = 3;  // @todo for the future
}
