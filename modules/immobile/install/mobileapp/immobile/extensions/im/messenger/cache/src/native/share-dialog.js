/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/share-dialog
 */
jn.define('im/messenger/cache/share-dialog', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { throttle } = require('utils/function');
	const { utils } = require('native/im');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { FeatureFlag } = require('im/messenger/const/feature-flag');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ComponentCode } = require('im/messenger/const');

	class ShareDialogCache
	{
		constructor()
		{
			this.saveRecentItemList = throttle(this.saveRecentItemList, 10000, this);
		}

		saveRecentItemList(recentItemList)
		{
			recentItemList = clone(recentItemList);

			return new Promise((resolve, reject) => {
				if (!FeatureFlag.native.imUtilsModuleSupported)
				{
					reject(new Error('imUtilsModule not supported by the current app version'));

					return;
				}

				const componentCode = MessengerParams.getComponentCode();
				if (componentCode === ComponentCode.imCopilotMessenger)
				{
					reject(new Error('Copilot recent cache not available for current app version'));

					return;
				}

				recentItemList = recentItemList.map((item) => {
					let lastMessageTimestamp = 0;
					if (item.message && item.message.id !== 0 && item.message.date)
					{
						lastMessageTimestamp = +DateHelper.cast(item.message.date);
					}

					return {
						id: item.id,
						title: item.title,
						subTitle: '',
						imageUrl: item.avatar ? item.avatar : '',
						color: item.color,
						lastMessageTimestamp,
					};
				});

				utils.setRecentUsers(recentItemList);
				resolve(recentItemList);
			});
		}
	}

	module.exports = {
		ShareDialogCache: new ShareDialogCache(),
	};
});
