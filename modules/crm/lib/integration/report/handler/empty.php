<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Report\VisualConstructor\IReportData;

/**
 * Class Empty
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class Deal extends Base implements IReportData
{
	public function prepare()
	{
		return [];
	}
}