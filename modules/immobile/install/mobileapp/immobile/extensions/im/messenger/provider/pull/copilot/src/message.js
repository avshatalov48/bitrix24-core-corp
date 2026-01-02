/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/copilot/message
 */
jn.define('im/messenger/provider/pull/copilot/message', (require, exports, module) => {
	const { getLogger } = require('im/messenger/lib/logger');
	const { ChatMessagePullHandler } = require('im/messenger/provider/pull/chat');
	const { CopilotNewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/copilot');

	const logger = getLogger('pull-handler--copilot-message');

	/**
	 * @class CopilotMessagePullHandler
	 */
	class CopilotMessagePullHandler extends ChatMessagePullHandler
	{
		constructor()
		{
			super({ logger });

			this.setWritingTimer(300_000);
		}

		setWritingTimer(value)
		{
			this.inputActionTimer = value;
		}

		handleMessage(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleMessage and nothing happened`, params, extra);
			// handle action message is not available now for copilot chat
		}

		handleReadMessage(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleReadMessage and nothing happened`, params);
			// handle action read message is not available now for copilot chat
		}

		handleUnreadMessage(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleUnreadMessage and nothing happened`, params);
			// handle action unread message is not available now for copilot chat
		}

		handleReadMessageOpponent(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleReadMessageOpponent and nothing happened`, params);
			// handle action read message opponent is not available now for copilot chat
		}

		handleUnreadMessageOpponent(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleUnreadMessageOpponent and nothing happened`, params);
			// handle action unread message opponent is not available now for copilot chat
		}

		handleMessagesAutoDeleteDelayChanged(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleMessagesAutoDeleteDelayChanged and nothing happened`, params, extra);
			// handle action messages auto-delete delay changed  is not available now for copilot chat
		}

		handlePinAdd(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handlePinAdd and nothing happened`, params);
			// handle action pin add is not available now for copilot chat
		}

		handlePinDelete(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handlePinDelete and nothing happened`, params, extra);
			// handle action pin delete is not available now for copilot chat
		}

		handleReadAllChannelComments(params, extra, command)
		{
			logger.info(`${this.constructor.name}.handleReadAllChannelComments and nothing happened`, params);
			// handle action read all channel comments opponent is not available now for copilot chat
		}

		isNeedNotify(params, extra, command)
		{
			return false;
		}

		/**
		 * @override
		 */
		saveShareDialogCache()
		{
			return true;
		}

		getNewMessageManager(params, extra = {})
		{
			return new CopilotNewMessageManager(params, extra);
		}
	}

	module.exports = {
		CopilotMessagePullHandler,
	};
});
