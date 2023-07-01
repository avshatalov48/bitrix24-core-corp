/**
 * @module crm/timeline/action/base
 */
jn.define('crm/timeline/action/base', (require, exports, module) => {
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @abstract
	 * @class BaseTimelineAction
	 */
	class BaseTimelineAction
	{
		/**
		 * @param {any} value
		 * @param {any} actionParams
		 * @param {any} analytics
		 * @param {TimelineItemBase} source
		 * @param {TimelineEntityProps} entity
		 * @param {TimelineAction} factory
		 */
		constructor({ value, actionParams, analytics, source, entity, factory, scheduler })
		{
			this.value = value;
			this.actionParams = actionParams;
			this.analytics = analytics;

			/** @type TimelineItemBase */
			this.source = source;

			/** @type {TimelineEntityProps} */
			this.entity = entity;

			/** @type {typeof TimelineAction} */
			this.factory = factory;

			/** @type {typeof TimelineScheduler} */
			this.scheduler = scheduler;
		}

		/**
		 * @abstract
		 */
		execute()
		{}

		sendAnalytics()
		{
			if (this.analytics)
			{
				AnalyticsLabel.send(this.analytics);
			}
		}
	}

	module.exports = { BaseTimelineAction };
});
