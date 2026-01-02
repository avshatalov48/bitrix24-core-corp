/**
 * @module im/messenger/lib/element/recent/item/user/bot
 */
jn.define('im/messenger/lib/element/recent/item/user/bot', (require, exports, module) => {
	const { merge } = require('utils/object');

	const { BotCode	} = require('im/messenger/const');
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class BotItem
	 */
	class BotItem extends UserItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		createActions()
		{
			this.actions = [
				this.getPinAction(),
				this.getReadAction(),
				this.getMuteAction(),
			];

			if (this.canHide())
			{
				this.actions.push(this.getHideAction());
			}

			return this;
		}

		createTitleStyle()
		{
			const userModel = this.getUserModelByDialogId(Number(this.getModelItem().id));

			if (userModel?.botData?.code === BotCode.aiAssistant)
			{
				return this;
			}

			this.styles.title = merge(this.styles.title, {
				image: {
					name: 'name_status_bot',
				},
			});

			return this;
		}

		canHide()
		{
			const dialogHelper = this.getDialogHelper();

			if (!dialogHelper)
			{
				return false;
			}

			return !dialogHelper.isAiAssistant;
		}
	}

	module.exports = {
		BotItem,
	};
});
