<?php

namespace Bitrix\Tasks\Internals\Notification;

class EntityOperation
{
	public const STATUS_CHANGED = 'STATUS';
	public const EXPIRES_SOON = 'EXPIRES_SOON';
	public const EXPIRED = 'EXPIRED';
	public const PING_STATUS = 'PING_STATUS';
	public const ADD = 'ADD';
	public const REPLICATE_REGULAR = 'REPLICATE_REGULAR';
	public const START_REGULAR = 'START_REGULAR';
	public const UPDATE = 'UPDATE';
	public const DELETE = 'DELETE';
	public const REPLY = 'REPLY';
	public const ADD_TO_FLOW_WITH_MANUAL_DISTRIBUTION = 'ADD_TO_FLOW_WITH_MANUAL_DISTRIBUTION';
	public const ADD_TO_FLOW_WITH_HIMSELF_DISTRIBUTION = 'ADD_TO_FLOW_WITH_HIMSELF_DISTRIBUTION';
}