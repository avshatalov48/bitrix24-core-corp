/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/share-dialog
 */
jn.define('im/messenger/cache/share-dialog', (require, exports, module) => {
	const { throttle } = require('utils/function');
	const { utils } = require('native/im');

	const { DateHelper } = require('im/messenger/lib/helper');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ComponentCode } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('cache--share-dialog');

	class ShareDialogCache
	{
		constructor()
		{
			this.saveRecentItemListThrottled = throttle(this.saveRecentItemList, 10000, this);
			this.store = serviceLocator.get('core').getStore();
		}

		get className()
		{
			return this.constructor.name;
		}

		saveRecentItemList()
		{
			try
			{
				const recentFirstPage = this.getRecentFirstPage();

				const componentCode = MessengerParams.getComponentCode();
				if (componentCode === ComponentCode.imCopilotMessenger || recentFirstPage.length === 0)
				{
					return;
				}

				const formattedRecentList = recentFirstPage.map((item) => {
					let lastMessageTimestamp = 0;
					if (item.message && item.message.id !== 0 && item.message.date)
					{
						lastMessageTimestamp = Number(DateHelper.cast(item.message.date));
					}

					const chatAvatar = ChatAvatar.createFromDialogId(item.id);
					const chatTitle = ChatTitle.createFromDialogId(item.id);
					const color = chatAvatar.getRecentItemAvatarProps().placeholder.backgroundColor;

					return {
						id: item.id,
						title: chatTitle.getTitle(),
						subTitle: chatTitle.getDescription(),
						imageUrl: chatAvatar.getAvatarUrl(),
						color,
						lastMessageTimestamp,
					};
				});
				logger.log(`${this.className}.saveRecentItemList set recent`, formattedRecentList);

				utils.setRecentUsers(formattedRecentList);
			}
			catch (error)
			{
				logger.error(`${this.className}.saveRecentItemList error`, error);
			}
		}

		/**
		 * @return {Array<RecentModelState>}
		 */
		getRecentFirstPage(count = 50)
		{
			if (Feature.isMessengerV2Enabled)
			{
				return this.store.getters['recentModel/getChatFirstPage']();
			}

			return this.store.getters['recentModel/getRecentPage'](1, count);
		}
	}

	module.exports = {
		ShareDialogCache,
	};
});
