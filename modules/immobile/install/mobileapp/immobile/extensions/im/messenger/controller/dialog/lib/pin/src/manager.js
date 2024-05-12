/**
 * @module im/messenger/controller/dialog/lib/pin/manager
 */
jn.define('im/messenger/controller/dialog/lib/pin/manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType } = require('im/messenger/const');
	const { parser } = require('im/messenger/lib/parser');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Feature } = require('im/messenger/lib/feature');
	const { Notification } = require('im/messenger/lib/ui/notification');

	const ButtonType = {
		delete: 'delete',
		edit: 'edit',
	};

	/**
	 * @class PinManager
	 */
	class PinManager
	{
		/**
		 *
		 * @param {DialogId} dialogId
		 * @param {DialogLocator} locator
		 */
		constructor({ dialogId, locator })
		{
			this.dialogId = dialogId;
			this.store = serviceLocator.get('core').getStore();
			/** @type {PinModelState || null} */
			this.currentPin = null;

			/** @type {string || null} */
			this.firstMessageId = null;

			/** @type {DialogLocator} */
			this.locator = locator;

			this.firstMessageId = null;
			this.bindStoreEventHandlers();
			this.bindViewEventHandlers();
		}

		bindStoreEventHandlers()
		{
			this.onSetChatCollection = this.onSetChatCollection.bind(this);
			this.onAddPin = this.onAddPin.bind(this);
			this.onDeletePin = this.onDeletePin.bind(this);
			this.onUpdatePin = this.onUpdatePin.bind(this);
			this.onUpdateMessage = this.onUpdateMessage.bind(this);
			this.onDeleteByChatId = this.onDeleteByChatId.bind(this);
			this.onDeleteMessagesByIdList = this.onDeleteMessagesByIdList.bind(this);
		}

		bindViewEventHandlers()
		{
			this.onButtonTap = this.onButtonTap.bind(this);
		}

		subscribeStoreEvents()
		{
			if (!Feature.isMessagePinSupported)
			{
				return;
			}

			serviceLocator.get('core').getStoreManager()
				.on('messagesModel/pinModel/setChatCollection', this.onSetChatCollection)
				.on('messagesModel/pinModel/add', this.onAddPin)
				.on('messagesModel/pinModel/delete', this.onDeletePin)
				.on('messagesModel/pinModel/updateMessage', this.onUpdateMessage)
				.on('messagesModel/pinModel/updatePin', this.onUpdatePin)
				.on('messagesModel/pinModel/deleteByChatId', this.onDeleteByChatId)
				.on('messagesModel/pinModel/deleteMessagesByIdList', this.onDeleteMessagesByIdList)
			;
		}

		unsubscribeStoreEvents()
		{
			if (!Feature.isMessagePinSupported)
			{
				return;
			}

			serviceLocator.get('core').getStoreManager()
				.off('messagesModel/pinModel/setChatCollection', this.onSetChatCollection)
				.off('messagesModel/pinModel/add', this.onAddPin)
				.off('messagesModel/pinModel/delete', this.onDeletePin)
				.off('messagesModel/pinModel/deleteByIdList', this.onDeletePin)
				.off('messagesModel/pinModel/updateMessage', this.onUpdateMessage)
				.off('messagesModel/pinModel/updatePin', this.onUpdatePin)
				.off('messagesModel/pinModel/deleteByChatId', this.onDeleteByChatId)
				.off('messagesModel/pinModel/deleteMessagesByIdList', this.onDeleteMessagesByIdList)
			;
		}

		subscribeViewEvents()
		{
			if (!Feature.isMessagePinSupported)
			{
				return;
			}

			this.locator.get('view').pinPanel.on(EventType.dialog.pinPanel.buttonTap, this.onButtonTap);
			this.locator.get('view').pinPanel.on(EventType.dialog.pinPanel.itemTap, (messageId) => {
				Notification.showComingSoon();
			})
			;
		}

		getPinPanelParams()
		{
			if (!Feature.isMessagePinSupported)
			{
				return null;
			}

			const lastPin = this.getLastPin();
			if (!lastPin)
			{
				return null;
			}

			const pinPanelParams = this.preparePinPanelParams(lastPin);

			this.firstMessageId ??= pinPanelParams.itemList[0].id;
			this.currentPin = lastPin;
			this.pinnedMessage = pinPanelParams.itemList[0];

			return pinPanelParams;
		}

		/**
		 *
		 * @param {PinDeleteData} pinData
		 */
		deletePin(pinData)
		{
			const pins = this.store.getters['messagesModel/pinModel/getListByChatId'](pinData.chatId);

			if (pins.length === 0)
			{
				this.locator.get('view').pinPanel.hide();

				this.currentPin = null;
				this.firstMessageId = null;
				this.pinnedMessage = null;

				return;
			}

			const pin = pins.reduce((prevPin, currentPin) => {
				return prevPin.id > currentPin.id ? prevPin : currentPin;
			}, { id: 0 });

			if (pin.id === 0)
			{
				this.currentPin = null;
				this.firstMessageId = null;
				this.pinnedMessage = null;

				this.locator.get('view').pinPanel.hide();
			}

			this.currentPin = pin;
			const message = this.prepareMessage(pin.message);
			message.id = this.firstMessageId;
			this.pinnedMessage = message;

			this.locator.get('view').pinPanel.updateItem(message);
		}

		redrawPanel()
		{
			const lastPin = this.getLastPin();

			if (!lastPin)
			{
				if (this.currentPin)
				{
					this.hidePanel();
				}

				return;
			}

			if (!this.currentPin)
			{
				this.showPanel(lastPin);

				return;
			}

			if (lastPin.dateCreate >= this.currentPin.dateCreate)
			{
				this.redrawMessage(lastPin);
			}
		}

		hidePanel()
		{
			this.firstMessageId = null;
			this.currentPin = null;
			this.pinnedMessage = null;

			this.locator.get('view').pinPanel.hide();
		}

		/**
		 * @param {PinModelState} pinModel
		 */
		redrawMessage(pinModel)
		{
			this.pinnedMessage = this.prepareMessage(pinModel.message);
			this.pinnedMessage.id = this.firstMessageId;
			this.currentPin = pinModel;

			this.locator.get('view').pinPanel.updateItem(this.pinnedMessage);
		}

		/**
		 * @param {PinModelState} pinModel
		 */
		showPanel(pinModel)
		{
			this.pinnedMessage = this.prepareMessage(pinModel.message);
			this.firstMessageId = this.pinnedMessage.id;
			this.currentPin = pinModel;

			this.locator.get('view').pinPanel.show({
				title: this.getTitle(),
				itemList: [this.pinnedMessage],
				selectedItemId: this.firstMessageId,
				buttonType: ButtonType.delete,
			});
		}

		/**
		 * @return {PinModelState||null}
		 */
		getLastPin()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);

			if (!dialogModel)
			{
				return null;
			}
			const pinModelList = this.store.getters['messagesModel/pinModel/getListByChatId'](dialogModel.chatId);

			if (pinModelList.length === 0)
			{
				return null;
			}

			return pinModelList.reduce((prevPin, currentPin) => {
				return prevPin.dateCreate > currentPin.dateCreate ? prevPin : currentPin;
			}, pinModelList[0]);
		}

		/**
		 *
		 * @param {PinModelState} pinModel
		 * @return {{
		 * 	itemList: Array<Message>,
		 * 	selectedItemId: string,
		 * 	title: string,
		 * 	buttonType: 'delete' | 'edit'
		 * }}
		 */
		preparePinPanelParams(pinModel)
		{
			const message = this.prepareMessage(pinModel.message);
			const title = this.getTitle();

			return {
				itemList: [message],
				selectedItemId: message.id,
				title,
				buttonType: ButtonType.delete,
			};
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @return {Message}
		 */
		prepareMessage(modelMessage)
		{
			const messageFiles = modelMessage.files
				.map((fileId) => this.store.getters['filesModel/getById'](fileId))
			;

			const user = this.store.getters['usersModel/getById'](modelMessage.authorId);

			const message = DialogConverter.createMessage(modelMessage);

			if (user)
			{
				message.username = user.id === MessengerParams.getUserId()
					? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_PANEL_YOU_MSGVER_1')
					: `${user.firstName}:`
				;
			}

			const simplifyMessage = parser.simplify({
				text: modelMessage.text,
				attach: modelMessage?.params?.ATTACH ?? false,
				files: messageFiles,
				showFilePrefix: false,
			});

			message.message = [{
				type: 'text',
				text: simplifyMessage,
			}];

			return message;
		}

		isPinInCurrentDialog(chatId)
		{
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId);

			return dialog.dialogId === this.dialogId;
		}

		getTitle()
		{
			return Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_PANEL_TITLE');
		}

		// region View EventHandlers

		/**
		 *
		 * @param {number | string} messageId
		 * @param {'delete' | 'edit'} buttonType
		 */
		onButtonTap(messageId, buttonType)
		{
			if (buttonType === ButtonType.edit)
			{
				return; // TODO unsupported type
			}

			this.locator.get('message-service')
				.unpinMessage(Number(this.currentPin.messageId))
			;
		}
		// endregion

		// region Store Event Handlers

		/**
		 *
		 * @param {MutationPayload<PinSetChatCollectionData, PinSetChatCollectionActions>} payload
		 */
		onSetChatCollection({ payload })
		{
			if (!this.isPinInCurrentDialog(payload.data.chatId))
			{
				return;
			}

			this.redrawPanel();
		}

		/**
		 * @param {MutationPayload<PinDeleteMessagesByIdListData, PinDeleteMessagesByIdListActions>} payload
		 */
		onDeleteMessagesByIdList({ payload })
		{
			this.redrawPanel();
		}

		/**
		 * @param {MutationPayload<PinDeleteByChatIdData, PinDeleteByChatIdActions>} payload
		 */
		onDeleteByChatId({ payload })
		{
			if (!this.isPinInCurrentDialog(payload.data.chatId))
			{
				return;
			}

			this.redrawPanel();
		}

		/**
		 * @param {MutationPayload<PinUpdateMessageData, PinUpdateMessageActions>} payload
		 */
		onUpdateMessage({ payload })
		{
			if (!this.isPinInCurrentDialog(payload.data.chatId))
			{
				return;
			}

			this.redrawPanel();
		}

		/**
		 * @param {MutationPayload<PinAddData, PinAddActions>} payload
		 */
		onAddPin({ payload })
		{
			if (!this.isPinInCurrentDialog(payload.data.chatId))
			{
				return;
			}

			this.redrawPanel();
		}

		/**
		 * @param {MutationPayload<PinUpdatePinData, PinUpdatePinActions>} payload
		 */
		onUpdatePin({ payload })
		{
			if (!this.isPinInCurrentDialog(payload.data.chatId))
			{
				return;
			}

			if (this.currentPin?.messageId === payload.data.pin.messageId)
			{
				const { chatId, pin } = payload.data;
				this.currentPin = this.store.getters['messagesModel/pinModel/getPin'](chatId, pin.messageId);
			}
		}

		/**
		 *
		 * @param {MutationPayload<PinDeleteData, PinDeleteActions>} payload
		 */
		onDeletePin({ payload })
		{
			if (!this.isPinInCurrentDialog(payload.data.chatId))
			{
				return;
			}

			this.deletePin(payload.data);
		}

		/**
		 *
		 * @param {MutationPayload<PinDeleteByIdListData, PinDeleteByIdListActions>} payload
		 */
		onDeleteByIdList({ payload })
		{
			this.redrawPanel();
		}
		// endregion
	}

	module.exports = { PinManager };
});
