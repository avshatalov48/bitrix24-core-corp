/**
 * @module im/messenger/controller/search/experimental/helper/search-date-formatter
 */
jn.define('im/messenger/controller/search/experimental/helper/search-date-formatter', (require, exports, module) => {
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DateHelper } = require('im/messenger/lib/helper');

	function formatDateByDialogId(dialogId)
	{
		/** @type {RecentModelState || RecentSearchModelState} */
		let item = null;
		const recentItem = serviceLocator.get('core').getStore().getters['recentModel/getById'](dialogId);
		if (recentItem)
		{
			item = recentItem;
		}
		const recentSearchItem = serviceLocator.get('core').getStore().getters['recentModel/searchModel/getById'](dialogId);

		if (recentSearchItem)
		{
			item = recentSearchItem;
		}

		if (!item)
		{
			return '';
		}

		const date = DateHelper.cast(item.dateMessage, null);

		if (!date)
		{
			return '';
		}

		return DateFormatter.getRecentFormat(date);
	}

	module.exports = { formatDateByDialogId };
});