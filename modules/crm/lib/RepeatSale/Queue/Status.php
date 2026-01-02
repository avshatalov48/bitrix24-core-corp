<?php

namespace Bitrix\Crm\RepeatSale\Queue;

enum Status: int
{
	case Waiting = 1;
	case Progress = 2;
}
