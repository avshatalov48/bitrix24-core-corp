/**
 * @module im/messenger/view/dialog/restrictions
 */
jn.define('im/messenger/view/dialog/restrictions', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');

	const { ProxyView } = require('im/messenger/view/lib/proxy-view');

	/**
	 * @class DialogRestrictions
	 */
	class DialogRestrictions extends ProxyView
	{
		/**
		 * @return {AvailableEventCollection}
		 */
		getAvailableEvents()
		{
			return {
				[EventFilterType.selectMessagesMode]: [],
			};
		}

		/**
		 * @param {ChatRestrictionsParams} restrictions
		 */
		update(restrictions)
		{
			if (this.isUiAvailable())
			{
				return this.ui.update(restrictions);
			}

			return false;
		}
	}

	module.exports = {
		DialogRestrictions,
	};
});
