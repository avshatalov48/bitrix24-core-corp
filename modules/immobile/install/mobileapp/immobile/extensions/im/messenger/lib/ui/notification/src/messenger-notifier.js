/**
 * @module im/messenger/lib/ui/notification/messenger-notifier
 */
jn.define('im/messenger/lib/ui/notification/messenger-notifier', (require, exports, module) => {
	/* global InAppNotifier, include  */
	include('InAppNotifier');
	const { transparent } = require('utils/color');

	const { Theme } = require('im/lib/theme');

	/**
	 * @class MessengerNotifier
	 */
	class MessengerNotifier
	{
		/**
		 * @param {ShowNotifierParams} params
		 */
		static show(params)
		{
			const data = {
				time: 1,
				backgroundColor: transparent(Theme.colors.baseBlackFixed, 0.8),
				...params,
			};

			InAppNotifier.showNotification(data);
		}
	}

	module.exports = { MessengerNotifier };
});
