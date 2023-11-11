<?php

namespace Bitrix\Tasks\Internals\Notification;

class EntityOperation
{
	public const STATUS_CHANGED = 'STATUS';
	public const EXPIRES_SOON = 'EXPIRES_SOON';
	public const EXPIRED = 'EXPIRED';
	public const PING_STATUS = 'PING_STATUS';
	public const ADD = 'ADD';
	public const UPDATE = 'UPDATE';
	public const DELETE = 'DELETE';
	public const REPLY = 'REPLY';
}