/**
 * @module im/messenger-v2/application/lib/event-handler/messenger
 */
jn.define('im/messenger-v2/application/lib/event-handler/messenger', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { clearDatabaseHandler } = require('im/messenger/lib/clear-database-handler');

	/**
	 * @class MessengerEventHandler
	 */
	class MessengerEventHandler
	{
		static #instance;

		/**
		 * @return {MessengerEventHandler}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		constructor()
		{
			this.isLaunched = false;
			this.logger = getLoggerWithContext('messenger-v2--messenger-event-handler', this);

			this.#bindMethods();
		}

		/**
		 * @type {DialogManager|null}
		 */
		get #dialogManager()
		{
			const dialogManager = serviceLocator.get('dialog-manager');
			if (dialogManager)
			{
				return dialogManager;
			}

			this.logger.error('dialogManager is not initialized.');

			return null;
		}

		#bindMethods()
		{
			this.openDialogHandler = this.openDialogHandler.bind(this);
			this.openLineHandler = this.openLineHandler.bind(this);
			this.getOpenLineParamsHandler = this.getOpenLineParamsHandler.bind(this);
		}

		subscribeEvents()
		{
			if (this.isLaunched)
			{
				return;
			}

			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialogHandler);
			BX.addCustomEvent(EventType.messenger.openLine, this.openLineHandler);
			BX.addCustomEvent(EventType.messenger.getOpenLineParams, this.getOpenLineParamsHandler);
			BX.addCustomEvent(EventType.messenger.clearDatabase, clearDatabaseHandler);

			this.isLaunched = true;
		}

		unsubscribeEvents()
		{
			if (!this.isLaunched)
			{
				return;
			}

			BX.removeCustomEvent(EventType.messenger.openDialog, this.openDialogHandler);
			BX.removeCustomEvent(EventType.messenger.openLine, this.openLineHandler);
			BX.removeCustomEvent(EventType.messenger.getOpenLineParams, this.getOpenLineParamsHandler);
			BX.removeCustomEvent(EventType.messenger.clearDatabase, clearDatabaseHandler);

			this.isLaunched = false;
		}

		/**
		 * @param {DialogOpenOptions} options
		 * @return {Promise<boolean>}
		 */
		async openDialogHandler(options = {})
		{
			this.logger.log('openDialogHandler:', options);

			return this.#dialogManager.openDialog(options);
		}

		/**
		 * @param {DialogOpenOptions} options
		 */
		openLineHandler(options)
		{
			this.logger.log('openLineHandler:', options);

			this.dialog = new Dialog();
			this.dialog.openLine(options);
		}

		/**
		 * @param {DialogOpenOptions} options
		 * @return {Promise<boolean>}
		 */
		getOpenLineParamsHandler(options = {})
		{
			this.logger.log('getOpenLineParamsHandler:', options);

			const requestId = options.userCode ?? options.sessionId;
			const openLineParamsResponseEvent = `${EventType.messenger.openLineParams}::${requestId}`;

			Dialog.getOpenLineParams(options)
				.then((params) => {
					BX.postComponentEvent(openLineParamsResponseEvent, [params]);
				})
				.catch((error) => {
					this.logger.error(error);
				})
			;
		}
	}

	module.exports = { MessengerEventHandler };
});
