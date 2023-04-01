<?php

namespace Bitrix\Crm\Timeline;

/**
 * Class DeliveryCategoryType
 * @package Bitrix\Crm\Timeline
 */
class DeliveryCategoryType
{
	const UNDEFINED = 0;
	// region deprecated v1 types
	public const TAXI_ESTIMATION_REQUEST = 1;
	public const TAXI_CALL_REQUEST = 2;
	public const TAXI_CANCELLED_BY_MANAGER = 3;
	public const TAXI_CANCELLED_BY_DRIVER = 4;
	public const TAXI_PERFORMER_NOT_FOUND = 5;
	public const TAXI_SMS_PROVIDER_ISSUE = 6;
	public const TAXI_RETURNED_FINISH = 7;
	// region deprecated v2 types
	public const MESSAGE = 101;
	public const DELIVERY_CALCULATION = 102;
	// endregion
	public const UNIVERSAL = 201;
}
