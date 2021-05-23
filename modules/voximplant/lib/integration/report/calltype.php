<?php

namespace Bitrix\Voximplant\Integration\Report;

/**
 * Class CallType
 * @package Bitrix\Voximplant\Integration\Report
 */
final class CallType
{
	public const OUTGOING = 1;
	public const INCOMING = 2;
	public const MISSED = 3;
	public const CALLBACK = 4;
	public const ALL = 5;
}