/**
 * @module layout/ui/whats-new/analytics
 */
jn.define('layout/ui/whats-new/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class WhatsNewAnalytics
	 */
	class WhatsNewAnalytics extends AnalyticsEvent
	{
		static get Event()
		{
			return {
				makeReaction: 'make_reaction',
				removeReaction: 'remove_reaction',
				openHelpdesk: 'open_helpdesk',
				block_go_read: 'block_go_read',
				drawerOpen: 'drawer_open',
				clickStore: 'click_store',
				transitionToNovelty: 'transition_to_novelty',
			};
		}

		static get Tool()
		{
			return 'intranet';
		}

		static get Category()
		{
			return 'whats_new';
		}

		static getWhatsNewItemId(id)
		{
			return `id_${id}`;
		}

		getDefaults()
		{
			return {
				tool: WhatsNewAnalytics.Tool,
				category: WhatsNewAnalytics.Category,
			};
		}
	}

	module.exports = {
		WhatsNewAnalytics,
	};
});
