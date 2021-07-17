<?php

namespace Bitrix\Crm\Order\BindingsMaker;

/**
 * Class ActivityBindingsMaker
 * @package Bitrix\Crm\Order\BindingsMaker
 */
class ActivityBindingsMaker extends Base
{
	/**
	 * @inheritDoc
	 */
	protected static function getPrefix(): string
	{
		return 'OWNER';
	}
}
