/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message-menu/menu
 */
jn.define('im/messenger/lib/element/dialog/message-menu/menu', (require, exports, module) => {

	const { SeparatorAction } = require('im/messenger/lib/element/dialog/message-menu/action');

	/**
	 * @class MessageMenu
	 */
	class MessageMenu
	{
		constructor()
		{
			this.reactionList = [];
			this.actionList = [];
		}
		static create()
		{
			return new this();
		}

		addReaction(reaction)
		{
			this.reactionList.push(reaction);

			return this;
		}

		addSeparator()
		{
			this.actionList.push(SeparatorAction);

			return this;
		}

		addAction(action)
		{
			this.actionList.push(action);

			return this;
		}
	}

	module.exports = {
		MessageMenu,
	};
});
