<?php

namespace Bitrix\Crm\Order\BindingsMaker;

/**
 * Class TimelineBindingsMaker
 * @package Bitrix\Crm\Order\BindingsMaker
 */
class TimelineBindingsMaker extends Base
{
	/**
	 * @inheritDoc
	 */
	protected static function getPrefix(): string
	{
		return 'ENTITY';
	}
}
