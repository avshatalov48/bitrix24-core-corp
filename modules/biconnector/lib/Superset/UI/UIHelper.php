<?php

namespace Bitrix\BIConnector\Superset\UI;

use Bitrix\Main\UI\Extension;

class UIHelper
{
	public static function getOpenMarketScript(string $marketUrl, string $analyticSource = 'unknown'): string
	{
		Extension::load([
			'sidepanel',
			'biconnector.apache-superset-analytics',
		]);

		$marketUrl = \CUtil::JSEscape($marketUrl);
		$analyticSource = \CUtil::JSEscape($analyticSource);

		return <<<JS
				top.BX.SidePanel.Instance.open('{$marketUrl}', { customLeftBoundary: 0 });
				BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('market', 'market_call', {
					c_element: '{$analyticSource}'
				});
		JS;
	}
}
