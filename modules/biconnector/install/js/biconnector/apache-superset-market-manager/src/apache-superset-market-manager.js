import { Loc } from 'main.core';

/**
 * @namespace BX.BIConnector
 */
export class ApacheSupersetMarketManager
{
	static openMarket(isMarketInstalled: boolean, marketUrl: string, analyticSource: string): void
	{
		if (!isMarketInstalled)
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('BIC_MARKET_MANAGER_NO_MODULE'),
			});

			return;
		}

		top.BX.SidePanel.Instance.open(marketUrl, { customLeftBoundary: 0 });
		BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('market', 'market_call', {
			c_element: analyticSource,
		});
	}
}
