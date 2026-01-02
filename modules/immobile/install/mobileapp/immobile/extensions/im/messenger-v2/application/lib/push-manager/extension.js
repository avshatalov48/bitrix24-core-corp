/**
 * @module im/messenger-v2/application/lib/push-manager
 */
jn.define('im/messenger-v2/application/lib/push-manager', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');
	const { DatabasePushMessageHandler } = require('im/messenger/provider/push/message-handler/database');
	const { PushHandler } = require('im/messenger/provider/push');
	const { MessengerPushMessageHandler } = require('im/messenger/provider/push/message-handler/messenger');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	/**
	 * @class PushManager
	 */
	class PushManager
	{
		constructor()
		{
			this.logger = getLoggerWithContext('messenger-v2--push-handler', this);

			this.databasePushMessageHandler = new DatabasePushMessageHandler();
			this.pushHandler = new PushHandler();
			this.messengerPushMessageHandler = new MessengerPushMessageHandler();
		}

		destructor()
		{
			this.databasePushMessageHandler?.destructor();
			this.messengerPushMessageHandler?.destructor();
		}

		async fillDatabaseFromPush()
		{
			if (!Feature.isInstantPushEnabled)
			{
				return;
			}

			const eventList = await this.pushHandler.getPushEventList();

			await this.databasePushMessageHandler.handleMessageBatch(eventList);
		}

		async processPush()
		{
			if (!Feature.isInstantPushEnabled)
			{
				return;
			}

			const eventList = await this.pushHandler.getPushEventList();
			await this.messengerPushMessageHandler.handleMessageBatch(eventList);
			await this.databasePushMessageHandler.handleMessageBatch(eventList);
		}

		async executeStoredPullEvents()
		{
			if (!Feature.isInstantPushEnabled)
			{
				try
				{
					await this.pushHandler.executeAction();
					this.pushHandler.clearHistory();
				}
				catch (error)
				{
					this.logger.error('executeStoredPullEvents old error:', error);
				}

				return;
			}

			try
			{
				await this.processPush();
				await this.pushHandler.executeAction();
				this.pushHandler.clearHistory();
			}
			catch (error)
			{
				this.logger.error('executeStoredPullEvents error:', error);
			}
		}
	}

	module.exports = {
		PushManager,
	};
});
