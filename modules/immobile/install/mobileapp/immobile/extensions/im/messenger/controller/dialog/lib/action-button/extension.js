/**
 * @module im/messenger/controller/dialog/lib/action-button
 */
jn.define('im/messenger/controller/dialog/lib/action-button', (require, exports, module) => {
	const { Type } = require('type');
	const { AutoDeleteMessages } = require('im/messenger/lib/messages-auto-delete');
	const { MessagesAutoDeleteContextMenu } = require('im/messenger/lib/ui/context-menu/messages-auto-delete');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const {
		EventType,
		MessagesAutoDeleteDelay,
		MessagesAutoDeleteMenuIds,
	} = require('im/messenger/const');

	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('dialog--action-button-manager');

	/**
	 * @class ActionButtonManager
	 */
	class ActionButtonManager
	{
		/**
		 *
		 * @param {chatId} number
		 * @param {DialogLocator} dialogLocator
		 */
		constructor({ dialogLocator, dialogId })
		{
			this.dialogLocator = dialogLocator;
			this.dialogId = dialogId;

			this.store = this.dialogLocator.get('store');

			this.isActive = false;

			this.#bindViewEventHandlers();

			this.autoDeleteMessages = new AutoDeleteMessages(this.dialogId);
		}

		/**
		 * @return {ChatService}
		 */
		get chatService()
		{
			return this.dialogLocator.get('chat-service');
		}

		#bindViewEventHandlers()
		{
			this.actionButtonTapHandler = this.#actionButtonTapHandler.bind(this);
			this.actionButtonMenuItemTap = this.#actionButtonMenuItemTap.bind(this);
			this.actionButtonMenuHidden = this.#actionButtonMenuHidden.bind(this);
			this.dialogUpdateHandler = this.#dialogUpdateHandler.bind(this);
		}

		subscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.on('dialoguesModel/update', this.dialogUpdateHandler)
			;
		}

		unsubscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.off('dialoguesModel/update', this.dialogUpdateHandler)
			;
		}

		subscribeViewEvents()
		{
			this.dialogLocator.get('view').textField.on(EventType.dialog.textField.actionButtonTap, this.actionButtonTapHandler);
			this.dialogLocator.get('view').textField.on(EventType.dialog.textField.actionButtonMenuItemTap, this.actionButtonMenuItemTap);
			this.dialogLocator.get('view').textField.on(EventType.dialog.textField.actionButtonMenuHidden, this.actionButtonMenuHidden);
		}

		/**
		 * @param {MutationPayload<DialoguesUpdateData, DialoguesUpdateActions>} mutation.payload
		 */
		#dialogUpdateHandler(mutation)
		{
			const { fields, dialogId } = mutation.payload.data;
			const { messagesAutoDeleteDelay } = fields;
			const isCurrentDialog = this.dialogId === dialogId;

			const canShowActionButton = isCurrentDialog
				&& this.#isButtonSupportedByChatType()
				&& Type.isNumber(messagesAutoDeleteDelay)
			;

			if (canShowActionButton)
			{
				if (messagesAutoDeleteDelay > 0)
				{
					this.#showActionButton();
				}
				else
				{
					this.dialogLocator.get('view').hideActionButton();
				}
			}
		}

		showActionButton()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const canShowActionButton = dialogModel?.messagesAutoDeleteDelay > 0 && this.#isButtonSupportedByChatType();

			if (canShowActionButton)
			{
				this.#showActionButton();
			}
		}

		#isButtonSupportedByChatType()
		{
			const dialogHelper = DialogHelper.createByDialogId(this.dialogId);

			return !dialogHelper?.isChannel && !dialogHelper?.isCopilot;
		}

		#actionButtonMenuHidden()
		{
			this.isActive = false;
			this.#showActionButton();
		}

		/**
		 * @param {string} id
		 */
		#actionButtonMenuItemTap(id)
		{
			logger.log(`${this.constructor.name}.actionButtonMenuItemTap`);

			const delay = MessagesAutoDeleteDelay[id];
			if (Type.isNumber(delay))
			{
				this.autoDeleteMessages.updateMessagesAutoDeleteDelay({ delay });

				return;
			}

			if (id === MessagesAutoDeleteMenuIds.helpdesk)
			{
				MessagesAutoDeleteContextMenu.openHelpdesk();
			}
		}

		#actionButtonTapHandler()
		{
			logger.log(`${this.constructor.name}.actionButtonTapHandler`);

			const autoDeleteDelayContextMenu = this.autoDeleteMessages.createMessagesAutoDeleteContextMenu();
			const menuConfig = autoDeleteDelayContextMenu?.getMenuConfig();

			if (menuConfig)
			{
				this.isActive = true;
				this.#showActionButton();
				this.dialogLocator.get('view').showActionButtonPopupMenu(menuConfig);
			}
		}

		#showActionButton()
		{
			this.dialogLocator.get('view').showActionButton(this.isActive);
		}
	}

	module.exports = { ActionButtonManager };
});
