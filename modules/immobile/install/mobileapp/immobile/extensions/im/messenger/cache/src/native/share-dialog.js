/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/share-dialog
 */
jn.define('im/messenger/cache/share-dialog', (require, exports, module) => {

	const { clone } = require('utils/object');
	const { throttle } = require('utils/function');
	const { utils } = require('native/im');
	const { FeatureFlag } = require('im/messenger/const/feature-flag');

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
					return reject(new Error('imUtilsModule not supported by the current app version'));
				}

				recentItemList = recentItemList.map((item) => {
					let lastMessageTimestamp = 0;
					if (item.message && item.message.id !== 0 && item.message.date)
					{
						lastMessageTimestamp = +item.message.date;
					}

					return {
						id: item.id,
						title: item.title,
						subTitle: '',
						imageUrl: item.avatar ? item.avatar : '',
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
