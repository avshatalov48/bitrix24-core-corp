/**
 * @module layout/ui/app-rating/src/analytics
 */
jn.define('layout/ui/app-rating/src/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const isAdmin = env.isAdmin ? 'admin_Y' : 'admin_N';

	class AppRatingAnalytics extends AnalyticsEvent
	{
		static get Event()
		{
			return {
				DRAWER_OPEN: 'drawer_open',
				SUBMIT_RATE: 'submit_rate',
				CLICK_STORE: 'click_store',
				CLICK_SUPPORT: 'click_support',
				SUBMIT_FEEDBACK: 'submit_feedback',
			};
		}

		getDefaults()
		{
			return {
				tool: 'intranet',
				category: 'rating_drawer',
				event: null,
				type: null,
				c_section: null,
				c_sub_section: null,
				c_element: null,
				status: null,
				p1: isAdmin,
				p2: null,
				p3: null,
				p4: null,
				p5: null,
			};
		}

		/**
		 * @param {string} params.section
		 * @returns {void}
		 */
		static sendDrawerOpen(params)
		{
			const { section } = params;

			new this({
				event: this.Event.DRAWER_OPEN,
				c_section: section,
			}).send();
		}

		/**
		 * @param {string} params.section
		 * @param {number} params.rating
		 * @param {number} params.attempt
		 * @returns {void}
		 */
		static sendSubmitRate(params)
		{
			const { section, rating, attempt } = params;

			new this({
				event: this.Event.SUBMIT_RATE,
				c_section: section,
				p2: `rate_${rating}`,
				p3: `index_${attempt}`,
			}).send();
		}

		/**
		 * @param {string} params.section
		 * @returns {void}
		 */
		static sendClickStore(params)
		{
			const { section } = params;

			new this({
				event: this.Event.CLICK_STORE,
				c_section: section,
			}).send();
		}

		/**
		 * @param {string} params.section
		 * @returns {void}
		 */
		static sendClickSupport(params)
		{
			const { section } = params;

			new this({
				event: this.Event.CLICK_SUPPORT,
				c_section: section,
			}).send();
		}

		/**
		 * @param {string} params.section
		 * @returns {void}
		 */
		static sendSubmitFeedback(params)
		{
			const { section } = params;

			new this({
				event: this.Event.SUBMIT_FEEDBACK,
				c_section: section,
			}).send();
		}
	}

	module.exports = {
		AppRatingAnalytics,
	};
});
