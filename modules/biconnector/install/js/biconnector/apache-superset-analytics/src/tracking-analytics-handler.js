import { ApacheSupersetAnalytics } from './apache-superset-analytics';

export class TrackingAnalyticsHandler
{
	static editEvent = {};

	static init()
	{
		this.bindEvents();
	}

	static bindEvents()
	{
		top.BX.addCustomEvent(
			top,
			'crm-analytics-source-edit',
			TrackingAnalyticsHandler.handleSourceEdit,
		);
	}

	static handleSourceEdit(eventData)
	{
		TrackingAnalyticsHandler.editEvent = eventData;
	}

	static handleSliderClose()
	{
		if (!TrackingAnalyticsHandler.editEvent.row)
		{
			return;
		}

		if (TrackingAnalyticsHandler.editEvent.row?.ID)
		{
			if (TrackingAnalyticsHandler.editEvent.updated)
			{
				ApacheSupersetAnalytics.sendAnalytics(
					'web',
					'connection_edit',
					{
						status: 'success',
						p1: `connectionType_${TrackingAnalyticsHandler.editEvent.row.CODE}`,
					},
				);
			}
			else if (TrackingAnalyticsHandler.editEvent.added)
			{
				ApacheSupersetAnalytics.sendAnalytics(
					'web',
					'connection',
					{
						status: 'success',
						p1: `connectionType_${TrackingAnalyticsHandler.editEvent.row.CODE}`,
					},
				);
			}
			else
			{
				ApacheSupersetAnalytics.sendAnalytics(
					'web',
					'connection_edit',
					{
						status: 'error',
						p1: `connectionType_${TrackingAnalyticsHandler.editEvent.row.CODE}`,
					},
				);
			}
		}
		else
		{
			ApacheSupersetAnalytics.sendAnalytics(
				'web',
				'connection',
				{
					status: 'error',
					p1: `connectionType_${TrackingAnalyticsHandler.editEvent.row.CODE}`,
				},
			);
		}

		TrackingAnalyticsHandler.editEvent = {};
	}
}
