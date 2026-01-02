/**
 * @module im/messenger/controller/dialog/lib/pin/list
 */
jn.define('im/messenger/controller/dialog/lib/pin/list', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Color } = require('tokens');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType } = require('im/messenger/const');
	const { getLogger } = require('im/messenger/lib/logger');
	const { PinListItem } = require('im/messenger/controller/dialog/lib/pin/list-item');
	const logger = getLogger('dialog--pin-list');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

	/**
	 * @class PinList
	 */
	class PinList
	{
		/**
		 * @type {IChatRecentList | null}
		 */
		widget = null;

		/**
		 * @constant
		 * @type {string}
		 */
		#widgetSectionItemsId = 'pinList';

		/**
		 * @type {boolean}
		 */
		#isWidgetOpened = false;

		/**
		 * @param {DialogId} dialogId
		 * @param {number} chatId
		 * @param {DialogLocator} locator
		 * @param {Function} onSelectListItem
		 */
		constructor({ dialogId, chatId, locator, onSelectListItem })
		{
			/**
			 * @type {DialogId}
			 */
			this.dialogId = dialogId;

			/**
			 * @type {number}
			 */
			this.chatId = chatId;

			/**
			 * @type {DialogLocator}
			 */
			this.locator = locator;

			/**
			 * @protected
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();

			/**
			 * @type {[object]}
			 */
			this.items = [];

			/**
			 * @type {Function}
			 */
			this.onSelectListItem = onSelectListItem;
		}

		async openWidget()
		{
			const counter = this.#getPinsCounter();

			try
			{
				this.widget = await PageManager.openWidget(
					'chat.recent',
					{
						titleParams: this.#getTitleParams({ detailText: String(counter) }),
					},
				);
				this.#onWidgetReady();
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.PageManager.openWidget.catch:`, error);
			}
		}

		tryToUpdateState()
		{
			if (!this.#isWidgetOpened)
			{
				return;
			}

			this.items = this.#prepareItems();
			this.widget.setSectionItems(this.items, this.#widgetSectionItemsId);

			this.#setCounterWidget();

			if (this.items.length === 0)
			{
				this.widget.back();
			}
		}

		/**
		 * @param {number} messageId
		 */
		deletePin(messageId)
		{
			this.locator.get('message-service').unpinMessage(Number(messageId));

			AnalyticsService.getInstance().sendMessageUnpin({
				dialogId: this.dialogId,
				chatId: this.chatId,
			});
		}

		#onWidgetReady = () => {
			this.#isWidgetOpened = true;
			this.#subscribeWidgetEvents();
			this.#subscribeStoreEvents();

			const sections = this.#prepareSections();
			this.items = this.#prepareItems();

			this.widget.setRefreshingEnabled(false);
			this.widget.setSections(sections);
			this.widget.setSectionItems(this.items, this.#widgetSectionItemsId);

			AnalyticsService.getInstance().sendPinListOpened({ dialogId: this.dialogId });
		};

		#onAddPin = () => {
			this.tryToUpdateState();
		};

		#onDeletePin = () => {
			this.tryToUpdateState();
		};

		#onUpdateMessages = () => {
			this.tryToUpdateState();
		};

		#onDeleteMessagesByIdList = () => {
			this.tryToUpdateState();
		};

		#onUpdateDialog = () => {
			this.tryToUpdateState();
		};

		#selectItemHandler = async (item) => {
			this.widget.back();

			await this.locator.get('context-manager').goToMessageContext({
				dialogId: this.dialogId,
				messageId: item.id,
			});
		};

		#closeWidgetHandler = () => {
			this.#isWidgetOpened = false;
			this.#unsubscribeStoreEvents();
		};

		#actionItemHandler = (event) => {
			const actionIdentifier = event.action.identifier;
			const messageId = event.item.id;

			if (this[actionIdentifier])
			{
				this[actionIdentifier](messageId);
			}
		};

		#subscribeWidgetEvents()
		{
			this.widget
				.on(EventType.view.close, this.#closeWidgetHandler)
				.on(EventType.recent.itemSelected, this.#selectItemHandler)
				.on(EventType.recent.itemAction, this.#actionItemHandler)
				.on(EventType.recent.itemSelected, this.onSelectListItem);
		}

		#subscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.on('messagesModel/pinModel/add', this.#onAddPin)
				.on('messagesModel/pinModel/delete', this.#onDeletePin)
				.on('messagesModel/pinModel/deleteByIdList', this.#onDeletePin)
				.on('messagesModel/pinModel/updateMessages', this.#onUpdateMessages)
				.on('messagesModel/pinModel/deleteMessagesByIdList', this.#onDeleteMessagesByIdList)
				.on('dialoguesModel/update', this.#onUpdateDialog);
		}

		#unsubscribeStoreEvents()
		{
			serviceLocator.get('core').getStoreManager()
				.off('messagesModel/pinModel/add', this.#onAddPin)
				.off('messagesModel/pinModel/delete', this.#onDeletePin)
				.off('messagesModel/pinModel/deleteByIdList', this.#onDeletePin)
				.off('messagesModel/pinModel/updateMessages', this.#onUpdateMessages)
				.off('messagesModel/pinModel/deleteMessagesByIdList', this.#onDeleteMessagesByIdList)
				.off('dialoguesModel/update', this.#onUpdateDialog);
		}

		#setCounterWidget()
		{
			const counter = this.#getPinsCounter();
			const titleParams = this.#getTitleParams({ detailText: String(counter) });

			this.widget.setTitle(titleParams);
		}

		/**
		 * @returns {number}
		 */
		#getPinsCounter()
		{
			return this.store.getters['messagesModel/pinModel/getPinsCounter'](this.chatId);
		}

		/**
		 * @return {object[]}
		 */
		#prepareSections()
		{
			return [
				{
					id: this.#widgetSectionItemsId,
					title: '',
					backgroundColor: Color.base8.toHex(),
				},
			];
		}

		#prepareItems()
		{
			const pinModels = this.store.getters['messagesModel/pinModel/getListByChatId'](this.chatId);

			return pinModels
				.map((pinModel) => new PinListItem(pinModel).getNativeProps())
				.sort((prev, next) => next.date - prev.date);
		}

		/**
		 * @param {object} customParams
		 * @returns {object}
		 */
		#getTitleParams(customParams = {})
		{
			return {
				text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_LIST_TITLE'),
				type: 'section',
				...customParams,
			};
		}
	}

	module.exports = { PinList };
});
