/**
 * @module im/messenger/controller/search/experimental/search-item
 */
jn.define('im/messenger/controller/search/experimental/search-item', (require, exports, module) => {
	const { SearchEntityIdTypes } = require('im/messenger/const');
	class RecentSearchItem
	{
		constructor(itemOptions)
		{
			/**
			 * @private
			 * @type {RecentProviderItem}
			 */
			this.itemOptions = itemOptions;
		}

		get dialogId()
		{
			return this.itemOptions.id;
		}

		get entityId()
		{
			return this.itemOptions.entityId;
		}

		get entityType()
		{
			return this.itemOptions.entityType;
		}

		get title()
		{
			return this.itemOptions.title;
		}

		get avatar()
		{
			return this.itemOptions.avatar;
		}

		get isUser()
		{
			return this.entityType === SearchEntityIdTypes.imUser;
		}

		get isChat()
		{
			return this.entityType === SearchEntityIdTypes.chat;
		}

		get customData()
		{
			return this.itemOptions.customData;
		}

		get dateMessage()
		{
			return this.itemOptions.customData.dateMessage;
		}
	}

	module.exports = { RecentSearchItem };
});
