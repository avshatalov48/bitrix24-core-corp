/**
 * @module im/messenger/api/dialog-integration/message/context-menu
 */
jn.define('im/messenger/api/dialog-integration/message/context-menu', (require, exports, module) => {
	const { MessageMenuActionType } = require('im/messenger/const');

	/**
	 * @abstract
	 * @class MessageContextMenu
	 * @implements {IMessageContextMenu}
	 */
	class MessageContextMenu
	{
		/**
		 * @param {MessageMenuContext} context
		 */
		constructor(context)
		{
			const {
				getDialog,
				relatedEntity,
			} = context;

			/**
			 * @protected
			 * @type {() => DialoguesModelState} */
			this.getDialog = getDialog;

			/**
			 * @protected
			 * @type {RelatedEntityData}
			 * */
			this.relatedEntity = relatedEntity;
		}

		/**
		 * @abstract
		 * @return {Record<string, (MessageMenuView, MessageMenuMessage) => void>}
		 */
		getActions()
		{
			throw new Error(`${this.constructor.name}: getActions() must be override in subclass.`);
		}

		/**
		 * @abstract
		 * @return {Record<string, (message: IMessageMenuMessage) => void>}
		 */
		getActionHandlers()
		{
			throw new Error(`${this.constructor.name}: getActionHandlers() must be override in subclass.`);
		}

		/**
		 * @param message
		 * @return {Promise<string[]>}
		 */
		async getOrderedActions(message)
		{
			return [
				MessageMenuActionType.reply,
				MessageMenuActionType.openVoteResult,
				MessageMenuActionType.revote,
				MessageMenuActionType.copy,
				MessageMenuActionType.copyLink,
				MessageMenuActionType.edit,
				MessageMenuActionType.subscribe,
				MessageMenuActionType.unsubscribe,
				MessageMenuActionType.pin,
				MessageMenuActionType.unpin,
				MessageMenuActionType.forward,
				MessageMenuActionType.feedback,
				MessageMenuActionType.create,
				MessageMenuActionType.downloadToDevice,
				MessageMenuActionType.downloadToDisk,
				MessageMenuActionType.profile,
				MessageMenuActionType.finishVote,
				MessageMenuActionType.delete,
				MessageMenuActionType.multiselect,
			];
		}
	}

	module.exports = {
		MessageContextMenu,
	};
});
