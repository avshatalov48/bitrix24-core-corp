/**
 * @module im/messenger/provider/services/message/rich
 */
jn.define('im/messenger/provider/services/message/rich', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');
	const { RestMethod } = require('im/messenger/const');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('rich-service--message');
	class RichService
	{
		constructor()
		{
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
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
