/**
 * @module im/messenger/controller/dialog/mention/provider
 */
jn.define('im/messenger/controller/dialog/mention/provider', (require, exports, module) => {
	const { RecentProvider } = require('im/messenger/controller/search/experimental');
	const { MentionConfig } = require('im/messenger/controller/dialog/mention/config');

	class MentionProvider extends RecentProvider
	{
		initConfig()
		{
			this.config = new MentionConfig();
		}

		loadRecentUsers()
		{
			return this.store.getters['recentModel/getSortedCollection']()
				.sort((item1, item2) => item2.dateMessage - item1.dateMessage)
				.map((recentItem) => recentItem.id)
			;
		}
	}

	module.exports = { MentionProvider };
});