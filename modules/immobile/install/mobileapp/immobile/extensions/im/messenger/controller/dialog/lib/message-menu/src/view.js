/**
 * @module im/messenger/controller/dialog/lib/message-menu/view
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/view', (require, exports, module) => {
	const { SeparatorAction } = require('im/messenger/controller/dialog/lib/message-menu/action');

	/**
	 * @class MessageMenuView
	 */
	class MessageMenuView
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

	module.exports = { MessageMenuView };
});
