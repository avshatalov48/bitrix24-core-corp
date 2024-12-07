/**
 * @module im/messenger/lib/element/dialog/message/banner/handler
 */
jn.define('im/messenger/lib/element/dialog/message/banner/handler', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { CustomMessageHandler } = require('im/messenger/lib/element/dialog/message/custom/handler');
	const { BannerMessageConfiguration } = require('im/messenger/lib/element/dialog/message/banner/configuration');
	const { ButtonId } = require('im/messenger/lib/element/dialog/message/banner/const/type');
	const { MessageParams } = require('im/messenger/const');

	/**
	 * @class BannerMessageHandler
	 */
	class BannerMessageHandler extends CustomMessageHandler
	{
		/**
		 * @return {void}
		 */
		bindMethods()
		{
			this.messageBannerButtonTap = this.messageBannerButtonTap.bind(this);
		}

		/**
		 * @return {void}
		 */
		subscribeEvents()
		{
			this.dialogLocator.get('view')
				.on(EventType.dialog.messageBannerButtonTap, this.messageBannerButtonTap)
			;
		}

		/**
		 * @return {void}
		 */
		unsubscribeEvents()
		{
			this.dialogLocator.get('view')
				.off(EventType.dialog.messageBannerButtonTap, this.messageBannerButtonTap)
			;
		}

		/**
		 * @param message
		 * @param {string | number} buttonId
		 * @return {void}
		 */
		messageBannerButtonTap(message, buttonId)
		{
			Logger.log(`${this.constructor.name}.messageBannerButtonTap message:`, message, buttonId);
			const messageId = message.id;
			try
			{
				const configuration = this.getConfiguration(messageId);
				const metaData = this.getMetaData(configuration, buttonId);

				const callback = metaData.buttons?.find((button) => button.id === buttonId)?.callback;
				if (callback)
				{
					callback(configuration.getMessageComponentParams());
				}
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.messageBannerButtonTap error`, error);
			}
		}

		/**
		 * @param {string | number} messageId
		 * @return {BannerMessageConfiguration}
		 */
		getConfiguration(messageId)
		{
			return new BannerMessageConfiguration(messageId);
		}

		/**
		 * @protected
		 * @param {BannerMessageConfiguration} configuration
		 * @param {string} buttonId
		 * @return {BannerMetaDataValue}
		 */
		getMetaData(configuration, buttonId)
		{
			if (this.isPlanLimitsBanner(buttonId))
			{
				return configuration.getMetaData(MessageParams.ComponentId.PlanLimitsMessage).banner;
			}

			const componentId = configuration.getMetaDataKey();
			const componentParams = configuration.getMessageComponentParams();
			if (componentParams?.stageId)
			{
				return configuration.getMetaData(componentId)[componentParams.stageId].banner;
			}

			return configuration.getMetaData(componentId).banner;
		}

		isPlanLimitsBanner(buttonId)
		{
			return buttonId === ButtonId.planLimitsUnlock;
		}
	}

	module.exports = {
		BannerMessageHandler,
	};
});
