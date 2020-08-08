<?php

namespace Bitrix\Crm\Timeline;

/**
 * Class DeliveryCategoryType
 * @package Bitrix\Crm\Timeline
 */
class DeliveryCategoryType
{
	const UNDEFINED = 0;
	const TAXI_ESTIMATION_REQUEST = 1;
	const TAXI_CALL_REQUEST = 2;
	const TAXI_CANCELLED_BY_MANAGER = 3;
	const TAXI_CANCELLED_BY_DRIVER = 4;
	const TAXI_PERFORMER_NOT_FOUND = 5;
	const TAXI_SMS_PROVIDER_ISSUE = 6;
	const TAXI_RETURNED_FINISH = 7;
}
