/**
 * @module im/messenger/controller/search/experimental/config
 */
jn.define('im/messenger/controller/search/experimental/config', (require, exports, module) => {
	/**
	 * @implements {BaseSearchConfig}
	 */
	class RecentConfig
	{
		constructor()
		{
			this.id = 'search-experimental';
			this.clearUnavailableItems = false;
			this.context = 'IM_CHAT_SEARCH';
			this.preselectedItems = [];
			this.entities = [
				{
					id: 'im-recent-v2',
					dynamicSearch: true,
					dynamicLoad: true,
				},
			];
		}

		/**
		* @param {object} options
		* @param {Array<string>} [options.includeOnly] - ['users', 'chats', 'bots'] — find only this entity's
		* @param {Array<string>} [options.exclude] - ['users', 'chats', 'bots'] — kick from search this entity's
		*/
		setOption(options = {})
		{
			this.entities[0].options = options;
		}

		getConfig()
		{
			/** @type {ajaxConfig} */
			return {
				json: {
					dialog: {
						entities: this.entities,
						preselectedItems: this.preselectedItems,
						clearUnavailableItems: this.clearUnavailableItems,
						context: this.context,
						id: this.id,
					},
				},
			};
		}

		getLoadLatestResultEndpoint()
		{
			return 'ui.entityselector.load';
		}

		getSaveItemEndpoint()
		{
			return 'ui.entityselector.saveRecentItems';
		}

		getSearchRequestEndpoint()
		{
			return 'ui.entityselector.doSearch';
		}
	}

	module.exports = { RecentConfig };
});
