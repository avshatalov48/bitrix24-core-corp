<?php

namespace Bitrix\Crm\Component\EntityDetails;

class ComponentMode
{
	public const UNDEFINED = 0x0;
	public const CREATION = 0x1;
	public const MODIFICATION = 0x2;
	public const COPING = 0x3;
	public const VIEW = 0x4;
	public const CONVERSION = 0x5;
	public const CUSTOM = 0x10;
}
