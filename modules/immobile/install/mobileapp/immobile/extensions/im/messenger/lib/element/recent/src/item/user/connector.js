/**
 * @module im/messenger/lib/element/recent/item/user/connector
 */
jn.define('im/messenger/lib/element/recent/item/user/connector', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class ConnectorUserItem
	 */
	class ConnectorUserItem extends UserItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		createTitleStyle()
		{
			const isMuted = this.getDialogHelper()?.isMuted;

			if (isMuted)
			{
				this.styles.title = merge(this.styles.title, {
					additionalImage: {
						name: 'name_status_mute',
					},
				});
			}

			this.styles.title = merge(this.styles.title, {
				image: {
					name: 'name_status_network',
				},
			});

			return this;
		}
	}

	module.exports = {
		ConnectorUserItem,
	};
});
