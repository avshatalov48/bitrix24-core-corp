<?php

namespace Bitrix\BIConnector\Superset\Logger;

class MarketDashboardLogger extends Logger
{
	final protected static function getAuditSubType(): string
	{
		return 'MARKET_DASHBOARD_INSTALL';
	}
}