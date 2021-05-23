<?php

namespace Bitrix\Voximplant\Integration\Report;

use Bitrix\Main\Loader;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Voximplant\Integration\Report\Handler\Base;

class ReportHandlerFactory
{
	public static function createWithReportId($reportGId)
	{
		if(!Loader::includeModule("report"))
		{
			return null;
		}

		$report = Report::getReportByGId($reportGId);
		if(!$report)
		{
			return null;
		}

		$handler = $report->getReportHandler(true);

		if($handler instanceof Base)
		{
			return $handler;
		}

		return null;
	}
}