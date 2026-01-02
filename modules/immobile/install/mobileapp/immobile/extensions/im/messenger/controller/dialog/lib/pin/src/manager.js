/**
 * @module im/messenger/controller/dialog/lib/pin/manager
 */
jn.define('im/messenger/controller/dialog/lib/pin/manager', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { isEqual } = require('utils/object');
	const { intersection } = require('utils/array');
	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogType, EventType, PinCount } = require('im/messenger/const');
	const { parser } = require('im/messenger/lib/parser');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { isOnline } = require('device/connection');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { PinList } = require('im/messenger/controller/dialog/lib/pin/list');

	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('dialog--pin-manager');

	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

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
		 * @param {string} chatType
		 */
		constructor({ dialogId, dialogLocator, chatType })
		{
			this.dialogId = dialogId;
			this.chatType = chatType;
			this.store = serviceLocator.get('core').getStore();
			/** @type {PinModelState || null} */
			this.currentPin = null;

			/** @type {DialogLocator} */
			this.dialogLocator = dialogLocator;

			this.pinList = null;

			/** @type {Message[]} */
			this.pinnedMessagesState = [];
			this.discussionMessageId = null;
			this.isShowDiscussionPanel = false;
			this.canEditPin = false;
			this.bindStoreEventHandlers();
			this.bindViewEventHandlers();
		}

		bindStoreEventHandlers()
		{
			this.onSetChatCollection = this.onSetChatCollection.bind(this);
			this.onAddPin = this.onAddPin.bind(this);
			this.onDeletePin = this.onDeletePin.bind(this);
			this.onUpdatePin = this.onUpdatePin.bind(this);
			this.onUpdateMessages = this.onUpdateMessages.bind(this);
			this.onDeleteMessagesByIdList = this.onDeleteMessagesByIdList.bind(this);
			this.onUpdateDiscussionMessage = this.onUpdateDiscussionMessage.bind(this);
			this.onUpdateDialog = this.onUpdateDialog.bind(this);
		}

		bindViewEventHandlers()
		{
			this.onButtonTap = this.onButtonTap.bind(this);
			this.onPinPanelTap = this.onPinPanelTap.bind(this);
			this.onDiscussionPanelTap = this.onDiscussionPanelTap.bind(this);
			this.onSelectListItem = this.onSelectListItem.bind(this);
		}

		subscribeStoreEvents()
		{
			if (this.#isCommentChat())
			{
				return;
			}

			serviceLocator.get('core').getStoreManager()
				.on('messagesModel/pinModel/setChatCollection', this.onSetChatCollection)
				.on('messagesModel/pinModel/add', this.onAddPin)
				.on('messagesModel/pinModel/delete', this.onDeletePin)
				.on('messagesModel/pinModel/updateMessages', this.onUpdateMessages)
				.on('messagesModel/pinModel/updatePin', this.onUpdatePin)
				.on('messagesModel/pinModel/deleteMessagesByIdList', this.onDeleteMessagesByIdList)
				.on('dialoguesModel/update', this.onUpdateDialog)
			;
		}

		unsubscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.off('messagesModel/pinModel/setChatCollection', this.onSetChatCollection)
				.off('messagesModel/pinModel/add', this.onAddPin)
				.off('messagesModel/pinModel/delete', this.onDeletePin)
				.off('messagesModel/pinModel/deleteByIdList', this.onDeletePin)
				.off('messagesModel/pinModel/updateMessages', this.onUpdateMessages)
				.off('messagesModel/pinModel/updatePin', this.onUpdatePin)
				.off('messagesModel/pinModel/deleteMessagesByIdList', this.onDeleteMessagesByIdList)
				.off('dialoguesModel/update', this.onUpdateDialog)
			;

			this.unsubscribeDiscussionStoreEvents();
		}

		subscribeViewEvents()
		{
			if (this.#isCommentChat())
			{
				return;
			}

			this.dialogLocator.get('view').pinPanel.on(EventType.dialog.pinPanel.buttonTap, this.onButtonTap);
			this.dialogLocator.get('view').pinPanel.on(EventType.dialog.pinPanel.itemTap, this.onPinPanelTap);
		}

		subscribeDiscussionViewEvents()
		{
			this.dialogLocator.get('view').on(
				EventType.dialog.viewAreaMessagesChanged,
				this.onViewAreaMessagesChanged.bind(this),
			);
			this.dialogLocator.get('view').pinPanel.on(EventType.dialog.pinPanel.itemTap, this.onDiscussionPanelTap);
		}

		subscribeDiscussionStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.on('messagesModel/update', this.onUpdateDiscussionMessage)
				.on('messagesModel/updateWithId', this.onUpdateDiscussionMessage)
			;
		}

		unsubscribeDiscussionStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.off('messagesModel/update', this.onUpdateDiscussionMessage)
				.off('messagesModel/updateWithId', this.onUpdateDiscussionMessage)
			;
		}

		/**
		 *
		 * @param {MutationPayload<
		 * MessagesUpdateData | MessagesUpdateWithIdData,
		 * MessagesUpdateActions | MessagesUpdateWithIdActions
		 * >} mutation.payload
		 */
		onUpdateDiscussionMessage(mutation)
		{
			if (mutation.payload.actionName === 'updateList')
			{
				const messageList = mutation.payload.data.messageList;
				messageList.forEach((message) => {
					if (Number(message.id) === Number(this.discussionMessageId))
					{
						this.updateDiscussionMessage();
					}
				});

				return;
			}

			if (Number(mutation.payload.data.id) !== Number(this.discussionMessageId))
			{
				return;
			}

			this.updateDiscussionMessage();
		}

		/**
		 *
		 * @param {PinDeleteData} pinData
		 */
		deletePin(pinData)
		{
			const pins = this.getPinsFromStore();

			if (!this.hasPins())
			{
				this.dialogLocator.get('view').pinPanel.hide();

				this.currentPin = null;

				return;
			}

			const pin = pins.reduce((prevPin, currentPin) => {
				return prevPin.id > currentPin.id ? prevPin : currentPin;
			}, { id: 0 });

			if (pin.id === 0)
			{
				this.currentPin = null;

				this.dialogLocator.get('view').pinPanel.hide();
			}

			this.currentPin = pin;
			const message = this.prepareMessage(pin.message);

			logger.log(`${this.constructor.name}.deletePin`, message);

			this.updatePinItem(message);
		}

		/**
		 * @param {PinModelState|null} pinModel
		 */
		redrawPinPanel(pinModel = null)
		{
			if (!this.hasPins())
			{
				this.hidePinPanel();
				this.pinList?.tryToUpdateState();

				return;
			}

			this.currentPin = pinModel || this.getFirstPin();

			this.updatePinPanel();
			this.redrawPinPanelMessage(this.currentPin);
		}

		hidePinPanel()
		{
			this.currentPin = null;

			this.dialogLocator.get('view').pinPanel.hide();
		}

		/**
		 * @param {PinModelState} pinModel
		 */
		redrawPinPanelMessage(pinModel)
		{
			this.currentPin = pinModel;
			const pinnedMessage = this.prepareMessage(pinModel.message);

			this.updatePinItem(pinnedMessage);
		}

		showPinPanel()
		{
			const pinPanelParams = this.getPinPanelParams();
			this.pinnedMessagesState = pinPanelParams.itemList || pinPanelParams.items;

			this.dialogLocator.get('view').pinPanel.show(pinPanelParams);
		}

		updatePinPanel()
		{
			const pinPanelParams = this.getPinPanelParams();
			this.pinnedMessagesState = pinPanelParams.itemList || pinPanelParams.items;

			if (Feature.isPinPanelNewAPIAvailable)
			{
				this.dialogLocator.get('view').pinPanel.update(pinPanelParams);
			}
			else
			{
				this.dialogLocator.get('view').pinPanel.show(pinPanelParams);
			}
		}

		/**
		 * @param {Message} message
		 */
		updatePinItem(message)
		{
			const item = this.preparePinPanelItem(message);

			this.dialogLocator.get('view').pinPanel.updateItem(item);
		}

		/**
		 * @param {number} messageId
		 */
		showDiscussionMessage(messageId)
		{
			setTimeout(() => {
				// timeout is necessary because the messages do not have time to be processed by the native
				this.#processAfterShowDiscussionMessage(messageId)
					.catch((error) => {
						logger.error(`${this.constructor.name}..#processAfterShowDiscussionMessage`, error);
					});
			}, 50);
		}

		hideDiscussionPanel()
		{
			this.isShowDiscussionPanel = false;
			this.dialogLocator.get('view').pinPanel.hide();
		}

		showDiscussionPanel()
		{
			this.isShowDiscussionPanel = true;

			const modelMessage = this.store.getters['messagesModel/getById'](this.discussionMessageId);

			if (!modelMessage)
			{
				logger.error(`${this.constructor.name}.showDiscussionPanel error: Message with id ${this.discussionMessageId} not found`);

				return;
			}

			const title = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_PANEL_DISCUSSION_MESSAGE_TITLE');
			const preparedMessage = this.prepareMessage(modelMessage);
			const params = {
				selectedItemId: String(this.discussionMessageId),
			};

			if (Feature.isPinPanelNewAPIAvailable)
			{
				const pinPanelItem = this.prepareDiscussionPanelItem(preparedMessage);
				params.items = [pinPanelItem];
			}
			else
			{
				params.title = title;
				params.itemList = [preparedMessage];
			}

			this.dialogLocator.get('view').pinPanel.show(params);
		}

		updateDiscussionMessage()
		{
			if (!this.discussionMessageId)
			{
				return;
			}

			const modelMessage = this.store.getters['messagesModel/getById'](this.discussionMessageId);

			if (!('id' in modelMessage))
			{
				logger.error(`${this.constructor.name}.showDiscussionPanel error: Message with id ${this.discussionMessageId} not found`);

				return;
			}

			const preparedMessage = this.prepareMessage(modelMessage);
			const discussionPanelItem = this.prepareDiscussionPanelItem(preparedMessage);
			this.dialogLocator.get('view').pinPanel.updateItem(discussionPanelItem);
		}

		/**
		 * @return {PinModelState||null}
		 */
		getFirstPin()
		{
			const pinModelList = this.getPinsFromStore();

			if (pinModelList.length === 0)
			{
				return null;
			}

			return pinModelList[0];
		}

		getPinPanelParams()
		{
			if (!this.hasPins())
			{
				return null;
			}

			const pinPanelParams = this.preparePinPanelParams();

			logger.log(`${this.constructor.name}.getPinPanelParams`, pinPanelParams);

			return pinPanelParams;
		}

		/**
		 * @return {{
		 * 	itemList: Array<Message>,
		 * 	selectedItemId: string,
		 * 	title: string,
		 * 	buttonType: 'delete' | 'edit'
		 * }}
		 */
		preparePinPanelParams()
		{
			const pinModelList = this.getPinsFromStore();
			const pinnedMessages = pinModelList.map((pin) => this.prepareMessage(pin.message));
			const selectedMessage = this.currentPin
				? this.currentPin.message
				: this.getFirstPin()?.message;
			const preparedSelectedMessage = this.prepareMessage(selectedMessage);
			const params = {
				selectedItemId: preparedSelectedMessage.id,
				buttonType: this.getButtonType(),
				maxCount: PinCount.max,
			};

			if (Feature.isPinPanelNewAPIAvailable)
			{
				params.items = pinnedMessages.map((message) => this.preparePinPanelItem(message));
			}
			else
			{
				const title = this.getPinPanelTitleByMessageId(preparedSelectedMessage.id);
				params.title = title;
				params.itemList = pinnedMessages;
			}

			return params;
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @return {Message}
		 */
		prepareMessage(modelMessage)
		{
			let messageFiles = [];
			if (Type.isArrayFilled(modelMessage.files))
			{
				messageFiles = modelMessage.files
					.map((fileId) => this.store.getters['filesModel/getById'](fileId))
				;
			}

			const user = this.store.getters['usersModel/getById'](modelMessage.authorId);
			const messegeData = this.dialogLocator.get('message-ui-converter').createMessage(modelMessage);

			if (user)
			{
				messegeData.username = user.id === serviceLocator.get('core').getUserId()
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

			messegeData.message = [
				{
					type: 'text',
					text: simplifyMessage,
				},
			];

			return messegeData;
		}

		/**
		 * @param {Message} message
		 * @returns {PinPanelItem<Message>}
		 */
		preparePinPanelItem(message)
		{
			return {
				title: this.getPinPanelTitleByMessageId(message.id),
				item: message,
			};
		}

		/**
		 * @param {Message} message
		 * @returns {PinPanelItem<Message>}
		 */
		prepareDiscussionPanelItem(message)
		{
			return {
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_PANEL_DISCUSSION_MESSAGE_TITLE'),
				item: message,
			};
		}

		isPinInCurrentDialog(chatId)
		{
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId);

			return dialog.dialogId === this.dialogId;
		}

		/**
		 * @returns {PinModelState[]|[]}
		 */
		getPinsFromStore()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);

			if (!dialogModel)
			{
				return [];
			}

			const pins = this.store.getters['messagesModel/pinModel/getListByChatId'](dialogModel.chatId);
			pins.sort((prev, next) => next.message.date - prev.message.date);

			return pins;
		}

		/**
		 *
		 * @param {number} id
		 * @returns {PinModelState | null}
		 */
		getPinByMessageId(id)
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);

			if (!dialogModel)
			{
				return null;
			}

			const pin = this.store.getters['messagesModel/pinModel/getPin'](dialogModel.chatId, id);

			return pin || null;
		}

		/**
		 * @returns {boolean}
		 */
		hasPins()
		{
			const pins = this.getPinsFromStore();

			return pins.length > 0;
		}

		/**
		 * @param {number} id
		 * @returns {boolean}
		 */
		hasPinByMessageId(id)
		{
			const pin = this.getPinByMessageId(id);

			return Boolean(pin);
		}

		/**
		 *
		 * @param {number} pinNumber
		 * @returns {string}
		 */
		getPinPanelTitle(pinNumber = 0)
		{
			if (pinNumber)
			{
				return Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_PANEL_TITLE_WITH_NUMBER', {
					'#ITEM_NUMBER#': pinNumber,
				});
			}

			return Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_PANEL_TITLE');
		}

		/**
		 *
		 * @param {number} id
		 * @returns {string}
		 */
		getPinPanelTitleByMessageId(id)
		{
			const pinModelList = this.getPinsFromStore();

			let title = this.getPinPanelTitle();
			const hasManyPins = pinModelList.length > 1;
			if (hasManyPins)
			{
				const preparedSelectedMessageIndex = [...pinModelList]
					.reverse()
					.findIndex((pinModel) => pinModel.message.id === Number(id)) + 1;
				title = this.getPinPanelTitle(preparedSelectedMessageIndex);
			}

			return title;
		}

		/**
		 * @returns {string}
		 */
		getButtonType()
		{
			this.canEditPin = ChatPermission.canPost(this.dialogId);

			if (!this.canEditPin)
			{
				return null;
			}

			const pins = this.getPinsFromStore();
			const hasManyPins = pins.length > 1;

			if (hasManyPins)
			{
				return ButtonType.edit;
			}

			return ButtonType.delete;
		}

		showNextPinPanelItem()
		{
			if (!this.hasPins())
			{
				return;
			}

			const nextPin = this.getNextPinPanelItem(this.currentPin);

			this.currentPin = nextPin;
			this.dialogLocator.get('view').pinPanel.showNextItem();
			this.updatePinPanel();
		}

		/**
		 * @param {PinModelState} pinModel
		 * @returns {PinModelState}
		 */
		getNextPinPanelItem(pinModel)
		{
			const pins = this.getPinsFromStore();
			const pinIndex = pins.findIndex((pin) => pin.messageId === pinModel.messageId);
			const nextPinIndex = pinIndex >= pins.length - 1
				? 0
				: pinIndex + 1;

			return pins[nextPinIndex];
		}

		// region View EventHandlers

		onViewAreaMessagesChanged(indexList, messageList)
		{
			const isDiscussionMessageOnScreen = messageList
				.some((message) => Number(message.id) === this.discussionMessageId)
			;

			if (isDiscussionMessageOnScreen && this.isShowDiscussionPanel)
			{
				this.hideDiscussionPanel();

				return;
			}

			if (!isDiscussionMessageOnScreen && !this.isShowDiscussionPanel)
			{
				this.showDiscussionPanel();
			}
		}

		/**
		 *
		 * @param {number | string} messageId
		 * @param {'delete' | 'edit'} buttonType
		 */
		async onButtonTap(messageId, buttonType)
		{
			if (buttonType === ButtonType.edit)
			{
				this.pinList = new PinList({
					dialogId: this.dialogId,
					chatId: this.currentPin.chatId,
					locator: this.dialogLocator,
					onSelectListItem: this.onSelectListItem,
				});
				await this.pinList.openWidget();

				return;
			}

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const chatId = this.currentPin.chatId;
			this.dialogLocator.get('message-service').unpinMessage(Number(this.currentPin.messageId));

			AnalyticsService.getInstance().sendMessageUnpin({
				dialogId: this.dialogId,
				chatId,
			});
		}

		onSelectListItem(item)
		{
			const selectedMessageId = Number(item.id);
			const pins = this.getPinsFromStore();
			const selectedPin = pins.find((pin) => pin.message.id === selectedMessageId);
			const nextPin = this.getNextPinPanelItem(selectedPin);

			this.redrawPinPanel(nextPin);
		}

		/**
		 *
		 * @param {number | string} messageId
		 */
		async onPinPanelTap(messageId)
		{
			if (!this.currentPin)
			{
				return;
			}

			await this.dialogLocator.get('context-manager').goToMessageContext({
				dialogId: this.dialogId,
				messageId: this.currentPin.messageId,
			});

			this.showNextPinPanelItem();
		}

		/**
		 *
		 * @param {number | string} messageId
		 */
		async onDiscussionPanelTap(messageId)
		{
			await this.dialogLocator.get('context-manager').goToPostMessageContext({
				postMessageId: Number(this.discussionMessageId),
			});
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

			if (Feature.isPinPanelNewAPIAvailable)
			{
				this.showPinPanel();
			}

			this.redrawPinPanel();
		}

		/**
		 * @param {MutationPayload<PinDeleteMessagesByIdListData, PinDeleteMessagesByIdListActions>} payload
		 */
		onDeleteMessagesByIdList({ payload })
		{
			const pinnedMessageIdsInState = this.pinnedMessagesState.map((message) => Number(message.id));
			const intersectionPinnedMessageIds = intersection(payload.data.idList, pinnedMessageIdsInState);

			if (!Type.isArrayFilled(intersectionPinnedMessageIds))
			{
				return;
			}

			this.redrawPinPanel();
		}

		/**
		 * @param {MutationPayload<PinUpdateMessagesData, PinUpdateMessageActions>} payload
		 */
		onUpdateMessages({ payload })
		{
			const { messageList } = payload.data;
			const chatId = messageList[0]?.chatId;
			if (!this.isPinInCurrentDialog(chatId))
			{
				return;
			}

			const existingPinnedMessageList = messageList.filter((message) => this.hasPinByMessageId(message.id));
			if (!Type.isArrayFilled(existingPinnedMessageList))
			{
				return;
			}

			const foundNotEqualMessages = existingPinnedMessageList.filter((message) => {
				const prevMessageState = this.pinnedMessagesState.find((item) => message.id === Number(item.id));
				const currentMessageState = this.prepareMessage(this.store.getters['messagesModel/getById'](message.id));

				return !isEqual(prevMessageState, currentMessageState);
			});
			if (!Type.isArrayFilled(foundNotEqualMessages))
			{
				return;
			}

			foundNotEqualMessages.forEach((message) => {
				if (message.fields.params.IS_DELETED === 'Y')
				{
					this.dialogLocator.get('message-service').unpinMessage(Number(message.id));
				}
			});

			this.redrawPinPanel();
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

			const pins = this.getPinsFromStore();
			const isAddedFirstPin = pins.length === 1;
			if (isAddedFirstPin)
			{
				this.showPinPanel();
			}
			else
			{
				this.redrawPinPanel();
			}
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
				const { pin } = payload.data;
				this.currentPin = this.getPinByMessageId(pin.messageId);
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
			this.redrawPinPanel();
		}

		/**
		 *
		 * @param {MutationPayload<PinDeleteByIdListData, PinDeleteByIdListActions>} payload
		 */
		onDeleteByIdList({ payload })
		{
			const foundPinnedMessageId = payload.data.idList.find((id) => this.hasPinByMessageId(id));

			if (!foundPinnedMessageId)
			{
				return;
			}

			this.redrawPinPanel();
		}

		/**
		 * @param {MutationPayload<DialoguesUpdateData, DialoguesUpdateActions>} payload
		 */
		onUpdateDialog({ payload })
		{
			if (this.canEditPin === ChatPermission.canPost(payload.data.dialogId))
			{
				return;
			}

			const firstPin = this.getFirstPin();
			if (!firstPin)
			{
				return;
			}

			this.showPinPanel();
		}
		// endregion

		/**
		 * @returns {boolean}
		 */
		#isCommentChat()
		{
			return this.chatType === DialogType.comment;
		}

		/**
		 * @param {number} messageId
		 * @returns {Promise<void>}
		 */
		async #processAfterShowDiscussionMessage(messageId)
		{
			this.discussionMessageId = messageId;
			const { messageList } = await this.dialogLocator.get('view').getViewableMessages();

			if (!messageList.some((message) => Number(message.id) === messageId))
			{
				this.showDiscussionPanel();
			}

			this.subscribeDiscussionViewEvents();
			this.subscribeDiscussionStoreEvents();
		}
	}

	module.exports = { PinManager };
});
