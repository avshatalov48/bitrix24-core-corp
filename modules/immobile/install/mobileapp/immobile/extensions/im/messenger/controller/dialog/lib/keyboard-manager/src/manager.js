/**
 * @module im/messenger/controller/dialog/lib/keyboard-manager/manager
 */
jn.define('im/messenger/controller/dialog/lib/keyboard-manager/manager', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');

	const { ActionHandler } = require('im/messenger/controller/dialog/lib/keyboard-manager/handler/action');
	const { BotCommandHandler } = require('im/messenger/controller/dialog/lib/keyboard-manager/handler/bot-command');
	const {
		EventType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('dialog--keyboard-manager');

	/**
	 * @class KeyboardManager
	 */
	class KeyboardManager
	{
		#dialogLocator;
		#dialogId;

		/**
		 * @param {IServiceLocator<DialogLocatorServices>} dialogLocator
		 */
		constructor(dialogLocator)
		{
			/**
			 * @private
			 * @type {IServiceLocator<DialogLocatorServices>}
			 */
			this.#dialogLocator = dialogLocator;
			this.#dialogId = this.#dialogLocator.get('dialogId');

			this.#bindMethods();
			this.#subscribeEvents();
		}

		/**
		 * @return {MessengerCoreStore|null}
		 */
		get #store()
		{
			const store = this.#dialogLocator.get('store');
			if (store)
			{
				return store;
			}

			this.#logError('store is not initialized.');

			return null;
		}

		#log(...message)
		{
			logger.log(`${this.constructor.name}.`, ...message);
		}

		#logError(...message)
		{
			logger.error(`${this.constructor.name}.`, ...message);
		}

		/**
		 * @private
		 * @return {void}
		 */
		#bindMethods()
		{
			this.messageKeyboardButtonTap = this.messageKeyboardButtonTap.bind(this);
		}

		/**
		 * @private
		 * @return {void}
		 */
		#subscribeEvents()
		{
			this.#dialogLocator.get('view')
				.on(EventType.dialog.messageKeyboardButtonTap, this.messageKeyboardButtonTap)
			;
		}

		/**
		 * @param {string} messageId
		 * @param {KeyboardButtonConfig} button
		 * @return {void}
		 */
		async messageKeyboardButtonTap(messageId, button)
		{
			this.#log('messageKeyboardButtonTap', messageId, button);

			if (button.disabled || button.wait)
			{
				this.#log('messageKeyboardButtonTap: button is disabled');

				return;
			}

			if (button.action && button.actionValue)
			{
				const actionHandler = new ActionHandler(this.#dialogId);
				actionHandler.handle(button.action, button.actionValue);

				return;
			}

			if (button.appId)
			{
				this.#logError('messageKeyboardButtonTap: open app is not implemented.');

				return;
			}

			if (button.link)
			{
				inAppUrl.open(button.link);

				return;
			}

			if (button.command)
			{
				await this.#store.dispatch('messagesModel/disableKeyboardByMessageId', messageId);

				const dialogId = this.#dialogLocator.get('dialogId');
				const botCommandHandler = new BotCommandHandler(dialogId, messageId);
				botCommandHandler
					.handle(button.botId, button.command, button.commandParams)
					.catch(async (error) => {
						logger.error(`${this.constructor.name}.handle: error`, error);
					})
				;
			}
		}
	}

	module.exports = { KeyboardManager };
});
