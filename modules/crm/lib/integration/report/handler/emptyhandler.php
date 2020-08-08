<?php

namespace Bitrix\Crm\Integration\Report\Handler;

/**
 * Class EmptyHandler
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class EmptyHandler extends Base
{
	public function prepare()
	{
		return [];
	}
}