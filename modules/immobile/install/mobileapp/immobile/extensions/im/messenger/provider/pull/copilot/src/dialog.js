/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/copilot/dialog
 */
jn.define('im/messenger/provider/pull/copilot/dialog', (require, exports, module) => {
	const { BaseDialogPullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Counters } = require('im/messenger/lib/counters');
	const { DialogHelper } = require('im/messenger/lib/helper');

	const logger = LoggerManager.getInstance().getLogger('pull-handler--copilot-dialog');

	/**
	 * @class CopilotDialogPullHandler
	 */
	class CopilotDialogPullHandler extends BaseDialogPullHandler
	{
		constructor()
		{
			super({ logger });
		}

		handleChatCopilotRoleUpdate(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleChatCopilotRoleUpdate.params`, params);
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

		handleReadAllChats(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleReadAllChats and nothing happened`, params);
			// TODO read all action is not available now for copilot chat
		}

		handleChatMuteNotify(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleChatMuteNotify and nothing happened`, params);
			// TODO mute is not available now for copilot chat
		}

		handleGeneralChatId(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleGeneralChatId and nothing happened`, params);
			// TODO general change action is not available now for copilot chat
		}

		handleChatUnread(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleChatUnread and nothing happened`, params);
			// TODO unread action is not available now for copilot chat
		}

		handleChatAvatar(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleChatAvatar and nothing happened`, params);
			// TODO change avatar action is not available now for copilot chat
		}

		handleChatChangeColor(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleChatChangeColor and nothing happened`, params);
			// TODO change color action is not available now for copilot chat
		}

		/**
		 * @override
		 * @param {String} dialogId
		 * @void
		 */
		deleteCounters(dialogId)
		{
			delete Counters.copilotCounter.detail[dialogId];
		}

		/**
		 * @param {DialoguesModelState} chatData
		 * @return {boolean}
		 */
		shouldDeleteChat(chatData)
		{
			const helper = DialogHelper.createByModel(chatData);

			if (!helper?.isCopilot)
			{
				return false;
			}

			return true;
		}
	}

	module.exports = {
		CopilotDialogPullHandler,
	};
});
