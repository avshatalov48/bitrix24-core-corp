/**
 * @module crm/message-senders-connector
 */
jn.define('crm/message-senders-connector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { Haptics } = require('haptics');

	const SenderTypes = {
		bitrix24: 'bitrix24',
		sms: 'sms_provider',
	};

	let TelegramConnectorManager = null;
	let NotificationServiceConsent = null;

	try
	{
		TelegramConnectorManager = require('imconnector/connectors/telegram').TelegramConnectorManager;
		NotificationServiceConsent = require('imconnector/consents/notification-service').NotificationServiceConsent;
	}
	catch (e)
	{
		console.warn(e, 'Imconnector extensions not found');

		return;
	}

	/**
	 * @class MessageSendersConnector
	 */
	class MessageSendersConnector
	{
		/**
		 * @public
		 * @returns {{telegram}}
		 */
		static get managers()
		{
			return {
				telegram: new TelegramConnectorManager(),
			};
		}

		constructor(options)
		{
			this.layout = options.layout || PageManager;
			this.manager = options.manager || MessageSendersConnector.managers.telegram;
			this.senderType = options.senderType || SenderTypes.bitrix24;
		}

		/**
		 * @public
		 * @returns {Promise<*|null>}
		 */
		async checkAndGetLine()
		{
			const isApproved = await this.checkConsentApproved();
			if (isApproved)
			{
				return this.getLineId();
			}

			Haptics.notifyWarning();

			// eslint-disable-next-line no-undef
			Notify.showUniqueMessage(
				Loc.getMessage('M_CRM_MESSAGE_SENDER_AGREEMENT_NOTIFY'),
				null,
				{ time: 5 },
			);

			return null;
		}

		/**
		 * @private
		 */
		async getLineId()
		{
			try
			{
				const { lineId } = await this.manager.openRegistrar(this.layout);

				return lineId;
			}
			catch (err)
			{
				this.handleError(err);

				return null;
			}
		}

		/**
		 * @private
		 * @returns {Promise}
		 */
		async checkConsentApproved()
		{
			if (this.senderType !== SenderTypes.bitrix24)
			{
				return true;
			}

			const consent = new NotificationServiceConsent();

			try
			{
				return await consent.open(this.layout);
			}
			catch (err)
			{
				console.error(err);

				return false;
			}
		}

		/**
		 * @private
		 * @param error
		 */
		handleError(error)
		{
			const { ex } = error || {};

			if (ex)
			{
				Alert.alert(
					ex ? ex.error_description : Loc.getMessage('M_CRM_MESSAGE_SENDER_COMMON_ERROR_TITLE'),
					Loc.getMessage('M_CRM_MESSAGE_SENDER_COMMON_ERROR_DESCRIPTION'),
					() => {},
					Loc.getMessage('M_CRM_MESSAGE_SENDER_COMMON_ERROR_OK_BUTTON'),
				);
			}

			console.error(error);
		}
	}

	module.exports = { MessageSendersConnector, SenderTypes };
});
