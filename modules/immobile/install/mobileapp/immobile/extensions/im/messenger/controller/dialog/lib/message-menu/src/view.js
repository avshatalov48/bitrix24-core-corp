/**
 * @module im/messenger/controller/dialog/lib/message-menu/view
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/view', (require, exports, module) => {
	const { SeparatorAction, ActionViewType } = require('im/messenger/controller/dialog/lib/message-menu/action');

	/**
	 * @class MessageMenuView
	 * @implements IMessageMenuView
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

		/**
		 * @param {MessageContextMenuButton} action
		 */
		addAction(action)
		{
			this.actionList.push(action);

			return this;
		}

		/**
		 * @param {MessageContextMenuButton | MessageContextMenuSeparator} action
		 * @returns {boolean}
		 */
		isSeparator(action)
		{
			return action.type === ActionViewType.separator;
		}

		clearUnnecessarySeparators()
		{
			this.actionList = this.actionList.filter((action, index) => {
				const prevAction = this.actionList[index - 1];

				return !(this.isSeparator(action) && this.isSeparator(prevAction));
			});

			const firstAction = this.actionList[0];
			if (this.isSeparator(firstAction))
			{
				this.actionList.shift();
			}

			const lastAction = this.actionList[this.actionList.length - 1];
			if (this.isSeparator(lastAction))
			{
				this.actionList.pop();
			}
		}
	}

	module.exports = { MessageMenuView };
});
