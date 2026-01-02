/**
 * @module im/messenger/controller/sidebar-v2/search
 */
jn.define('im/messenger/controller/sidebar-v2/search', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Color } = require('tokens');
	const { Type } = require('type');
	const { uniqBy } = require('utils/array');
	const { debounce } = require('utils/function');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { SidebarSearchAnalytics } = require('im/messenger/controller/sidebar-v2/search/src/analytics');
	const { SidebarSearchMemoryStorage } = require('im/messenger/controller/sidebar-v2/search/src/memory-storage');
	const { RestMethod } = require('im/messenger/const/rest');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog/dialog');
	const { getLogger } = require('im/messenger/lib/logger');
	const {
		prepareTextToItem,
		cropTextByCenterWord,
		highlightWord,
		withColor,
	} = require('im/messenger/controller/sidebar-v2/search/src/utils');
	const { Feature } = require('im/messenger/lib/feature');

	const MINIMAL_SERVER_SEARCH_LENGTH = 3;
	const SEARCH_RESULT_SUBTITLE_MAX_LENGTH = Application.getPlatform() === 'android' ? 50 : 60;
	const GENERAL_SECTION_CODE = 'general';
	const LOGGER_KEY = 'sidebarV2–search';
	const ServiceItemsId = {
		LOADING: 'loading',
		EMPTY_SEARCH: 'empty-search',
		START_TYPING: 'start-typing',
		PADDING: 'padding',
	};

	/**
	 * @class SidebarSearch
	 */
	class SidebarSearch
	{
		/**
		 * @param {Object} options
		 * @param {string} options.dialogId
		 * @param {Object} options.dialogLocator
		 */
		constructor(options)
		{
			this.dialogId = options.dialogId;
			this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);
			this.dialogModel = this.dialogHelper.dialogModel;
			this.memoryStorage = new SidebarSearchMemoryStorage(this.dialogId);

			this.widget = null;
			this.search = null;
			this.searchText = this.memoryStorage.getSearchText();
			this.items = [];

			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();
			/**
			 * @private
			 * @type {MessageRepository}
			 */
			this.repository = this.core.getRepository().message;

			this.dialogLocator = options.dialogLocator;
			this.contextManager = this.dialogLocator.get('context-manager');

			this.debounceServerSearch = debounce(() => this.#onServerSearch(), 500, this);

			this.analytics = new SidebarSearchAnalytics(this.dialogHelper);
			this.logger = getLogger(LOGGER_KEY);
		}

		#subscribeSearchEvents()
		{
			this.search.on('textChanged', this.#onTextChange);

			this.search.on('itemSelected', this.#onItemSelected);

			this.search.on('cancel', this.#onCancel);

			this.search.on('hide', this.#onHide);

			this.search.on('show', this.#onShow);

			this.search.on('clickEnter', this.#onClickEnter);
		}

		#unsubscribeSearchEvents()
		{
			this.search.off('textChanged', this.#onTextChange);

			this.search.off('itemSelected', this.#onItemSelected);

			this.search.off('cancel', this.#onCancel);

			this.search.off('hide', this.#onHide);

			this.search.off('show', this.#onShow);

			this.search.off('clickEnter', this.#onClickEnter);
		}

		#setLoading(isLoading)
		{
			if (isLoading)
			{
				this.#addItems([this.#getLoadingItem()]);

				return;
			}

			this.items = this.items.filter((item) => item.type !== 'loading');
			this.search.setItems(this.items);
		}

		/**
		 * @public
		 * @param {PageManager} widget
		 */
		open(widget)
		{
			this.widget = widget;
			this.search = widget.search;
			this.search.mode = 'overlay';

			this.#subscribeSearchEvents();

			this.search.show();
		}

		#onTextChange = async ({ text }) => {
			this.searchText = text.trim();
			this.memoryStorage.setSearchText(this.searchText);

			if (!Type.isStringFilled(this.searchText))
			{
				this.search.setItems([this.#getStartTypingItem()]);

				return;
			}

			try
			{
				await this.#searchLocal();
			}
			finally
			{
				if (this.searchText.length >= MINIMAL_SERVER_SEARCH_LENGTH)
				{
					this.debounceServerSearch();
				}
			}
		};

		#onCancel = async () => {
			await this.memoryStorage.clear();
			this.analytics.sendCancelSearchEvent();
		};

		#onHide = () => {
			this.#unsubscribeSearchEvents();
		};

		#onShow = () => {
			this.search.setItems([this.#getStartTypingItem()]);
			this.analytics.sendOpenSearchEvent();

			if (this.searchText.length > 0)
			{
				this.search.text = this.searchText;
				this.#onTextChange({ text: this.searchText });
			}
		};

		#onClickEnter = () => {
			Keyboard.dismiss();
		};

		#searchLocal = async () => {
			const { items: messages } = await this.repository.searchByText({
				chatId: this.dialogModel.chatId,
				searchText: this.searchText,
			});

			this.#processSearchResult(messages, true);
		};

		#onServerSearch = () => {
			BX.ajax.runAction(RestMethod.imV2ChatMessageSearch, {
				data: {
					dialogId: this.dialogId,
					filter: {
						searchMessage: this.searchText,
					},
					order: {
						id: 'DESC',
					},
					limit: 50,
				},
			}).then((response) => {
				const messages = response.data?.messages ?? [];
				this.#processSearchResult(messages);
			}).catch((response) => {
				this.logger.error(response);
			});
		};

		#processSearchResult(messages, isLocal = false)
		{
			if (!Type.isStringFilled(this.searchText))
			{
				return;
			}

			if (isLocal)
			{
				this.#setMessages(messages);
				if (this.searchText.length >= MINIMAL_SERVER_SEARCH_LENGTH)
				{
					this.#setLoading(true);
				}
			}
			else
			{
				this.#setLoading(false);
				this.#addMessages(messages);
			}

			if (this.items.length > 0)
			{
				if (!this.isServiceItem(this.items[0]))
				{
					this.analytics.sendSearchResultEventSuccess();
					this.#addItems([this.#getPaddingItem()]); // todo need add padding from native side
				}
			}
			else
			{
				if (Type.isStringFilled(this.searchText))
				{
					this.#addItems([this.#getEmptySearchItem()]);
				}
				this.analytics.sendSearchResultEventNotFound();
			}
		}

		#setMessages(messages)
		{
			this.items = this.#prepareItems(messages);
			this.search.setItems(this.items);
		}

		#addMessages(messages)
		{
			this.items = uniqBy([...this.items, ...this.#prepareItems(messages)], 'id').sort(this.sortItemsCallback);
			this.search.setItems(this.items);
		}

		#addItems(items)
		{
			this.items = uniqBy([...this.items, ...items], 'id').sort(this.sortItemsCallback);
			this.search.setItems(this.items);
		}

		#prepareItems(messages)
		{
			return messages
				.map((message) => this.mapMessageToItem(message))
				.filter(Boolean)
				.sort(this.sortItemsCallback)
			;
		}

		sortItemsCallback(a, b)
		{
			if (a.id === ServiceItemsId.PADDING || b.id === ServiceItemsId.PADDING)
			{
				return a.id === ServiceItemsId.PADDING ? 1 : -1;
			}

			return b.sortDate > a.sortDate ? 1 : -1;
		}

		mapMessageToItem(message)
		{
			const authorId = message.author_id || message.authorId;
			if (!authorId)
			{
				return null;
			}
			// put options with messageId and chatId, for get avatar copilot role
			const chatAvatar = ChatAvatar.createFromDialogId(
				authorId,
				{ messageId: message.id, chatId: message.chat_id || this.dialogModel?.chatId },
			);
			const chatTitle = ChatTitle.createFromDialogId(authorId);
			const displayedDate = DateFormatter.getRecentFormat(new Date(message.date));

			const messageText = prepareTextToItem(
				message.text,
				this.searchText,
				Color.accentMainPrimaryalt.toHex(),
				SEARCH_RESULT_SUBTITLE_MAX_LENGTH,
				true,
			);

			if (Feature.isChatRecentItemTypeInSearchOverlayAvailable)
			{
				return {
					id: message.id,
					title: chatTitle.getTitle(),
					subtitle: messageText,
					avatar: chatAvatar.getRecentItemAvatarProps({ useNotes: false }),
					sectionCode: GENERAL_SECTION_CODE,
					type: 'chat.recent',
					displayedDate,
					sortDate: new Date(message.date),
					styles: {
						date: {
							font: {
								size: 13,
							},
						},
						subtitle: {
							font: {
								size: 15,
							},
						},
						title: {
							font: {
								size: 16,
								fontStyle: 'semibold',
							},
						},
					},
				};
			}

			const subtitle = `${withColor(displayedDate, Color.base2.toHex())}: ${prepareTextToItem(message.text, this.searchText)}`;

			return {
				id: message.id,
				title: chatTitle.getTitle(),
				subtitle,
				avatar: chatAvatar.getListItemAvatarProps(),
				sectionCode: GENERAL_SECTION_CODE,
				sortDate: new Date(message.date),
			};
		}

		#getPaddingItem()
		{
			return {
				id: ServiceItemsId.PADDING,
				title: '',
				type: 'button',
				sectionCode: GENERAL_SECTION_CODE,
				unselectable: true,
			};
		}

		#getStartTypingItem()
		{
			return {
				id: ServiceItemsId.START_TYPING,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_START_TYPING_TEXT'),
				type: 'button',
				sectionCode: GENERAL_SECTION_CODE,
				unselectable: true,
			};
		}

		#getEmptySearchItem()
		{
			return {
				id: ServiceItemsId.EMPTY_SEARCH,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_EMPTY_SEARCH_TEXT'),
				type: 'button',
				sectionCode: GENERAL_SECTION_CODE,
				unselectable: true,
			};
		}

		#getLoadingItem()
		{
			return {
				id: ServiceItemsId.LOADING,
				type: 'loading',
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_LOADING_TEXT'),
				sectionCode: GENERAL_SECTION_CODE,
				unselectable: true,
			};
		}

		#onItemSelected = (data) => {
			// for ios and android different signature
			const item = data?.item || data;

			if (!item.id || item.unselectable)
			{
				return;
			}

			const index = this.items.map((item) => Number(item.id)).indexOf(Number(item.id));
			this.analytics.sendSelectSearchResultEvent(index);

			this.search.close();

			this.contextManager.goToMessageContext({
				messageId: Number(item.id),
				dialogId: this.dialogId,
				targetMessagePosition: AfterScrollMessagePosition.center,
			});
		};

		isServiceItem(item)
		{
			return Object.values(ServiceItemsId).includes(item.id);
		}
	}

	module.exports = {
		SidebarSearch,
		SidebarSearchMemoryStorage,
		// for tests
		cropTextByCenterWord,
		highlightWord,
		prepareTextToItem,
	};
});
