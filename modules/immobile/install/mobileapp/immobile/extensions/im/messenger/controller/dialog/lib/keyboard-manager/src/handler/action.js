/**
 * @module im/messenger/controller/dialog/lib/keyboard-manager/handler/action
 */
jn.define('im/messenger/controller/dialog/lib/keyboard-manager/handler/action', (require, exports, module) => {
	const { Loc } = require('loc');
	const { openPhoneMenu } = require('communication/phone-menu');

	const {
		EventType,
		KeyboardButtonAction,
	} = require('im/messenger/const');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('dialog--keyboard-manager');

	/**
	 * @class ActionHandler
	 */
	class ActionHandler
	{
		#dialogId;
		#actionHandlers = {
			[KeyboardButtonAction.send]: this.#sendMessage.bind(this),
			[KeyboardButtonAction.put]: this.#insertText.bind(this),
			[KeyboardButtonAction.call]: this.#startCall.bind(this),
			[KeyboardButtonAction.copy]: this.#copyText.bind(this),
			[KeyboardButtonAction.dialog]: this.#openChat.bind(this),
		};

		/**
		 * @param {DialogId} dialogId
		 */
		constructor(dialogId)
		{
			this.#dialogId = dialogId;
		}

		/**
		 * @param {KeyboardButtonConfig['action']} actionName
		 * @param {KeyboardButtonConfig['actionValue']} actionValue
		 * @return {void}
		 */
		handle(actionName, actionValue)
		{
			if (!this.#actionHandlers[actionName])
			{
				logger.error(`${this.constructor.name}.handle: action "${actionName}" not found`);
			}

			logger.log(`${this.constructor.name}.handle`, actionName, actionValue);

			this.#actionHandlers[actionName](actionValue);
		}

		/**
		 * @param {string} actionValue
		 */
		#sendMessage(actionValue)
		{
			MessengerEmitter.emit(EventType.dialog.external.sendMessage, {
				dialogId: this.#dialogId,
				text: actionValue,
			});
		}

		/**
		 * @param {string} actionValue
		 */
		#insertText(actionValue)
		{
			MessengerEmitter.emit(EventType.dialog.external.textarea.insertText, {
				dialogId: this.#dialogId,
				text: actionValue,
			});
		}

		/**
		 * @param {string} actionValue
		 */
		#startCall(actionValue)
		{
			openPhoneMenu({
				number: actionValue,
				canUseTelephony: MessengerParams.canUseTelephony(),
				analyticsSection: 'chat',
			});
		}

		/**
		 * @param {string} actionValue
		 */
		#copyText(actionValue)
		{
			DialogTextHelper.copyToClipboard(
				actionValue,
				{
					notificationText: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_KEYBOARD_BUTTON_ACTION_COPY_SUCCESS'),
				},
			);
		}

		/**
		 * @param {DialogId} actionValue
		 */
		#openChat(actionValue)
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: actionValue,
			});
		}
	}

	module.exports = { ActionHandler };
});
