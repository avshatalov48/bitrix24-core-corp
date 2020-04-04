<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Integration\Report\Limit;
use Bitrix\Main\Engine\Controller;

class ReportBoard extends Controller
{
	public function resetLimitCacheAction($boardId)
	{
		Limit::resetLimitCache();
		$limited = Limit::isAnalyticsLimited($boardId);
		return [
			'limitEnabled' => $limited,
			'text' => Limit::getLimitText($boardId)
		];
	}
}