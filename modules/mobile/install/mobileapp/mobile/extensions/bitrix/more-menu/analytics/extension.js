/**
 * @module more-menu/analytics
 */
jn.define('more-menu/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	class MoreMenuAnalytics extends AnalyticsEvent
	{
		getDefaults()
		{
			return {
				tool: null,
				category: null,
				event: 'open_section',
				type: null,
				c_section: 'ava_menu',
				c_sub_section: null,
				c_element: null,
				status: null,
				p1: null,
				p2: null,
				p3: null,
				p4: null,
				p5: null,
			};
		}

		static isValidAnalyticsData(data)
		{
			return (
				typeof data === 'object'
				&& data?.tool
				&& data?.category
				&& data?.event
			);
		}

		static sendDrawerOpenEvent()
		{
			new this({
				tool: 'intranet',
				category: 'whats_new',
				event: 'drawer_open',
				c_section: 'ava_menu',
			}).send();
		}
	}

	module.exports = {
		MoreMenuAnalytics,
	};
});
