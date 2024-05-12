/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/copilot/dialog
 */
jn.define('im/messenger/provider/pull/copilot/dialog', (require, exports, module) => {
	const { DialogBasePullHandler } = require('im/messenger/provider/pull/lib');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Counters } = require('im/messenger/lib/counters');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--copilot-dialog');

	/**
	 * @class CopilotDialogPullHandler
	 */
	class CopilotDialogPullHandler extends DialogBasePullHandler
	{
		constructor()
		{
			super({ logger });
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
	}

	module.exports = {
		CopilotDialogPullHandler,
	};
});
