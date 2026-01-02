/**
 * @module timeman/analytics
 */
jn.define('timeman/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const TimemanAnalyticsEventType = {
		ON_TIMEMAN_OPEN_DAY: 'OnTimemanOpenDay',
		ON_TIMEMAN_START_DAY: 'OnTimemanStartDay',
		ON_TIMEMAN_CLOSE_DAY: 'OnTimemanCloseDay',
		ON_TIMEMAN_PAUSE_DAY: 'OnTimemanPauseDay',
		ON_TIMEMAN_RESUME_DAY: 'OnTimemanResumeDay',
	};

	const TimemanAnalyticsId = {
		[TimemanAnalyticsEventType.ON_TIMEMAN_OPEN_DAY]: 'popup_open',
		[TimemanAnalyticsEventType.ON_TIMEMAN_START_DAY]: 'start_day',
		[TimemanAnalyticsEventType.ON_TIMEMAN_CLOSE_DAY]: 'finish_day',
		[TimemanAnalyticsEventType.ON_TIMEMAN_PAUSE_DAY]: 'break_start',
		[TimemanAnalyticsEventType.ON_TIMEMAN_RESUME_DAY]: 'resume_day',
	};

	const TimemanAnalyticsSection = {
		AVA_MENU: 'ava_menu',
	};

	class TimemanAnalytics
	{
		subscribeEvents()
		{
			BX.addCustomEvent(TimemanAnalyticsEventType.ON_TIMEMAN_OPEN_DAY, () => {
				this.sendAnalytics(TimemanAnalyticsEventType.ON_TIMEMAN_OPEN_DAY);
			});

			BX.addCustomEvent(TimemanAnalyticsEventType.ON_TIMEMAN_START_DAY, () => {
				this.sendAnalytics(TimemanAnalyticsEventType.ON_TIMEMAN_START_DAY);
			});

			BX.addCustomEvent(TimemanAnalyticsEventType.ON_TIMEMAN_CLOSE_DAY, () => {
				this.sendAnalytics(TimemanAnalyticsEventType.ON_TIMEMAN_CLOSE_DAY);
			});
			BX.addCustomEvent(TimemanAnalyticsEventType.ON_TIMEMAN_PAUSE_DAY, () => {
				this.sendAnalytics(TimemanAnalyticsEventType.ON_TIMEMAN_PAUSE_DAY);
			});
			BX.addCustomEvent(TimemanAnalyticsEventType.ON_TIMEMAN_RESUME_DAY, () => {
				this.sendAnalytics(TimemanAnalyticsEventType.ON_TIMEMAN_RESUME_DAY);
			});
		}

		sendAnalytics(event)
		{
			new AnalyticsEvent({
				tool: 'timeman',
				category: 'workday',
				event: TimemanAnalyticsId[event],
				c_section: TimemanAnalyticsSection.AVA_MENU,
			}).send();
		}
	}

	module.exports = {
		TimemanAnalytics: new TimemanAnalytics(),
		TimemanAnalyticsEventType,
	};
});
