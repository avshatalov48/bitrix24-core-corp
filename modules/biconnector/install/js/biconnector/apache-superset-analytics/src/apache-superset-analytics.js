import { sendData } from 'ui.analytics';
import type { AnalyticsOptions } from 'ui.analytics';

export class ApacheSupersetAnalytics
{
	static toolName = 'BI_Builder';
	static sectionName = 'BI_Builder';

	categoryName: string;
	commonAdditionalInfo: AnalyticsOptions;

	constructor(categoryName: string, commonAdditionalInfo: AnalyticsOptions = {})
	{
		this.categoryName = categoryName;
		this.commonAdditionalInfo = commonAdditionalInfo;
	}

	static sendAnalytics(categoryName: string, eventName: string, additionalData: AnalyticsOptions = {})
	{
		sendData({
			tool: ApacheSupersetAnalytics.toolName,
			c_section: ApacheSupersetAnalytics.sectionName,
			category: categoryName,
			event: eventName,
			...additionalData,
		});
	}

	static buildAppIdForAnalyticRequest(appId: string): string
	{
		if (!appId)
		{
			return 'reportAppId_empty';
		}

		const parts = appId.split('_');

		for (let i = 1; i < parts.length; i++)
		{
			parts[i] = parts[i].charAt(0).toUpperCase() + parts[i].slice(1);
		}

		return `reportAppId_${parts.join('')}`;
	}
}
