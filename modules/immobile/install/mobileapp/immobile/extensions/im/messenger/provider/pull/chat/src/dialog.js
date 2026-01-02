/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/dialog
 */
jn.define('im/messenger/provider/pull/chat/dialog', (require, exports, module) => {
	const { BaseDialogPullHandler } = require('im/messenger/provider/pull/base');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatNewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/chat');
	const { readAllCountersOnClient } = require('im/messenger/lib/counters/counter-manager/messenger/actions');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('pull-handler--chat-dialog');

	/**
	 * @class ChatDialogPullHandler
	 */
	class ChatDialogPullHandler extends BaseDialogPullHandler
	{
		constructor()
		{
			super({ logger });
		}

		handleReadAllChats(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info(`${this.getClassName()}.handleReadAllChats`);

			readAllCountersOnClient()
				.catch((error) => {
					this.logger.error(`${this.getClassName()}.handleReadAllChats: readAllCountersOnClient error`, error);
				})
			;
		}

		/**
		 * @param {DialoguesModelState} chatData
		 * @return {boolean}
		 */
		shouldDeleteChat(chatData)
		{
			const helper = DialogHelper.createByModel(chatData);

			return !helper?.isOpenChannel || helper?.isCurrentUserParticipant;
		}

		handleChatAvatar(params, extra, command)
		{
			const isCopilot = this.getNewMessageManager(params, extra).isCopilotChat();
			if (isCopilot)
			{
				return;
			}

			super.handleChatAvatar(params, extra, command);
		}

		handleChatChangeColor(params, extra, command)
		{
			const isCopilot = this.getNewMessageManager(params, extra).isCopilotChat();
			if (isCopilot)
			{
				return;
			}

			super.handleChatChangeColor(params, extra, command);
		}

		getNewMessageManager(params, extra = {})
		{
			return new ChatNewMessageManager(params, extra);
		}

		handleChatCopilotRoleUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleChatCopilotRoleUpdate.params`, params);
			this.store.dispatch(
				'dialoguesModel/copilotModel/updateRole',
				{
					dialogId: params.dialogId,
					fields: {
						chats: params.copilotRole.chats,
						roles: params.copilotRole.roles,
					},
				},
			).catch((error) => logger.error(`${this.constructor.name}.handleChatCopilotRoleUpdate.catch:`, error));
		}
	}

	module.exports = {
		ChatDialogPullHandler,
	};
});
