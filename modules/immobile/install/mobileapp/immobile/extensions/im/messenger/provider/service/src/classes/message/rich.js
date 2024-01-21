/**
 * @module im/messenger/provider/service/classes/message/rich
 */
jn.define('im/messenger/provider/service/classes/message/rich', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { runAction } = require('im/messenger/lib/rest');
	const { RestMethod } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('rich-service--message');
	class RichService
	{
		constructor()
		{
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = core.getStore();
		}

		async deleteRichLink(messageId, attachId)
		{
			this.store.dispatch('messagesModel/deleteAttach', {
				messageId,
				attachId,
			}).catch((error) => {
				logger.error('RichService.deleteRichLink local error:', error);
			});

			runAction(RestMethod.imV2ChatMessageDeleteRichUrl, {
				data: {
					messageId,
				},
			}).catch((error) => {
				logger.error('RichService.deleteRichLink server error:', error);
			});
		}
	}

	module.exports = { RichService };
});
