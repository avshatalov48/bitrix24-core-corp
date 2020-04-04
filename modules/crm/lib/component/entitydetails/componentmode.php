<?php
namespace Bitrix\Crm\Component\EntityDetails;

class ComponentMode
{
	const UNDEFINED		= 0x0;
	const CREATION		= 0x1;
	const MODIFICATION	= 0x2;
	const COPING		= 0x3;
	const VIEW			= 0x4;
	const CUSTOM		= 0x10;
}