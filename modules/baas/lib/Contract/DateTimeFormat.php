<?php

namespace Bitrix\Baas\Contract;

enum DateTimeFormat: string
{
	case LOCAL_DATE = 'Y-m-d';
	case LOCAL_DATETIME = 'Y-m-d H:i:s';
}
