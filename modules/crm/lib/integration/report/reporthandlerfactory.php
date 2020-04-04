<?php

namespace Bitrix\Crm\Integration\Report;

use Bitrix\Main\Loader;
use Bitrix\Report\VisualConstructor\Entity\Report;

class ReportHandlerFactory
{
	/**
	 * Returns report handler for the given report GId.
	 *
	 * @param string $reportGId Global id of the report.
	 * @return Handler\Base
	 */
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

		if($handler instanceof Handler\Base)
		{
			return $handler;
		}

		return null;
	}
}