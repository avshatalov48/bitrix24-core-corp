/**
 * @module im/messenger/controller/dialog/lib/floating-buttons-bar-manager
 */
jn.define('im/messenger/controller/dialog/lib/floating-buttons-bar-manager', (require, exports, module) => {
	const { Icon } = require('ui-system/blocks/icon');
	const { Color } = require('tokens');
	const { isEmpty } = require('utils/object');
	const { debounce } = require('utils/function');
	const { Type } = require('type');

	const { AnchorType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Feature } = require('im/messenger/lib/feature');
	const { RestMethod } = require('im/messenger/const');

	const ButtonId = {
		mentions: 'mentions',
		reactions: 'reactions',
		comments: 'comments',
		scrollToNewMessages: 'scrollToNewMessages',
	};

	const ButtonSort = {
		[ButtonId.scrollToNewMessages]: 1,
		[ButtonId.comments]: 2,
		[ButtonId.mentions]: 3,
		[ButtonId.reactions]: 4,
	};

	/**
	 * @class FloatingButtonsBarManager
	 */
	class FloatingButtonsBarManager
	{
		/**
		 * @param {JNChatFloatingButtonsBar} floatingButtonsBar
		 * @param {Function} scrollToNewMessagesHandler
		 * @param {DialogLocator} dialogLocator
		 */
		constructor(floatingButtonsBar, scrollToNewMessagesHandler, dialogLocator)
		{
			this.dialogLocator = dialogLocator;
			this.dialogId = this.dialogLocator.get('dialogId');
			this.scrollToNewMessagesHandler = scrollToNewMessagesHandler;

			/**
			 * @type {CoreApplication}
			 */
			this.core = serviceLocator.get('core');

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();

			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = this.core.getStoreManager();

			this.floatingButtonsBar = floatingButtonsBar;

			this.lastScrolledCommentChatId = 0;
			this.lastScrolledMessageIdWithMention = 0;
			this.lastScrolledMessageIdWithReaction = 0;
			this.newMessageCount = 0;
			this.isScrollToNewMessagesButtonHidden = true;

			this.#onButtonTap = debounce(this.#onButtonTap, 300, this, true);

			this.floatingButtonsBar.on('tap', this.#onButtonTap);

			this.subscribeStoreEvents();

			this.animationQueue = [];
		}

		/**
		 * @return {ContextManager||null}
		 */
		get contextManager()
		{
			return this.dialogLocator.get('context-manager');
		}

		async isMessageWithIdOnScreen(messageId)
		{
			const view = this.dialogLocator.get('view');

			const messageListOnScreen = (await view.getViewableMessages()).messageList;

			const messageIndex = messageListOnScreen
				.findIndex((message) => String(message.id) === String(messageId))
			;

			return messageIndex !== -1;
		}

		/**
		 * @private
		 */
		processAnimationQueue = async () => {
			if (this.isProcessingQueue)
			{
				return;
			}

			this.isProcessingQueue = true;

			const processNext = async () => {
				if (this.animationQueue.length === 0)
				{
					this.isProcessingQueue = false;

					return;
				}

				const action = this.animationQueue.shift();
				if (typeof action === 'function')
				{
					await action();
				}

				await processNext();
			};

			await processNext();
		};

		subscribeStoreEvents()
		{
			this.storeManager
				.on('anchorModel/add', this.#onAnchorUpdate)
				.on('anchorModel/delete', this.#onAnchorUpdate)
				.on('anchorModel/deleteMany', this.#onAnchorListUpdate)
			;
		}

		unsubscribeStoreEvents()
		{
			this.storeManager
				.off('anchorModel/add', this.#onAnchorUpdate)
				.off('anchorModel/delete', this.#onAnchorUpdate)
				.off('anchorModel/deleteMany', this.#onAnchorListUpdate)
			;
		}

		getChatId = () => {
			return this.store.getters['dialoguesModel/getById'](this.dialogId)?.chatId ?? null;
		};

		getReactionButton = () => {
			const reactions = this.store.getters['anchorModel/getByChatId'](this.getChatId(), AnchorType.reaction);

			if (isEmpty(reactions))
			{
				return null;
			}

			const uniqueMessageIds = new Set(reactions.map((anchor) => anchor.messageId));
			const messagesWithReactionsCount = uniqueMessageIds.size;

			if (!messagesWithReactionsCount)
			{
				return null;
			}

			return {
				id: ButtonId.reactions,
				sort: ButtonSort[ButtonId.reactions],
				icon: {
					name: Icon.HEART.getIconName(),
					tintColor: Color.accentMainAlert.toHex(),
				},
				badge: {
					value: messagesWithReactionsCount,
					backgroundColor: Color.accentMainPrimary.toHex(),
				},
			};
		};

		getMentionButton = () => {
			const mentions = this.store.getters['anchorModel/getByChatId'](this.getChatId(), AnchorType.mention);

			if (isEmpty(mentions))
			{
				return null;
			}

			const uniqueMessageIds = new Set(mentions.map((anchor) => anchor.messageId));
			const messagesWithMentionCount = uniqueMessageIds.size;

			if (!messagesWithMentionCount)
			{
				return null;
			}

			return {
				id: ButtonId.mentions,
				sort: ButtonSort[ButtonId.mentions],
				icon: {
					name: Icon.MENTION.getIconName(),
					tintColor: Color.accentMainPrimary.toHex(),
				},
				badge: {
					value: messagesWithMentionCount,
					backgroundColor: Color.accentMainPrimary.toHex(),
				},
			};
		};

		getCommentsButton = () => {
			const postsCountWithCounters = Feature.isMessengerV2Enabled
				? this.store.getters['counterModel/getNumberChildCounters'](this.getChatId())
				: this.store.getters['commentModel/getPostsCountWithCounters'](this.getChatId())
			;

			if (!postsCountWithCounters)
			{
				return null;
			}

			return {
				id: ButtonId.comments,
				sort: ButtonSort[ButtonId.comments],
				icon: {
					name: Icon.MESSENGER.getIconName(),
				},
				badge: {
					value: postsCountWithCounters,
					backgroundColor: Color.accentMainSuccess.toHex(),
				},
			};
		};

		getScrollToNewMessagesButton = () => {
			if (this.isScrollToNewMessagesButtonHidden)
			{
				return null;
			}

			return {
				id: ButtonId.scrollToNewMessages,
				sort: ButtonSort[ButtonId.scrollToNewMessages],
				icon: {
					name: Icon.CHEVRON_DOWN.getIconName(),
				},
				badge: {
					value: this.newMessageCount,
					backgroundColor: Color.accentMainPrimary.toHex(),
				},
			};
		};

		/**
		 * @public
		 * @param {Number} count
		 */
		setNewMessageCounter = (count) => {
			this.newMessageCount = count;
			this.updateNewMessagesButton();
		};

		#onAnchorUpdate = async (mutation) => {
			const { anchor } = mutation.payload.data;

			if (anchor.chatId !== this.getChatId())
			{
				return;
			}

			const shouldReadAnchor = await this.isMessageWithIdOnScreen(anchor.messageId)
				&& this.store.getters['anchorModel/hasAnchor'](anchor);

			if (shouldReadAnchor)
			{
				this.#readAnchor(anchor);

				return;
			}

			if (anchor.type === AnchorType.reaction)
			{
				this.updateReactionsButton();

				return;
			}

			if (anchor.type === AnchorType.mention)
			{
				this.updateMentionsButton();
			}
		};

		/**
		 * @param {AnchorModelState} anchor
		 */
		#readAnchor(anchor)
		{
			if (anchor.messageId)
			{
				this.store.dispatch('anchorModel/delete', { anchor });
				BX.ajax.runAction(RestMethod.imV2AnchorRead, {
					data: {
						messageIds: [anchor.messageId],
					},
				});
			}
		}

		#onAnchorListUpdate = (mutation) => {
			const { anchorList } = mutation.payload.data;

			const mentions = [];
			const reactions = [];

			anchorList.forEach((anchor) => {
				if (anchor.type === AnchorType.reaction)
				{
					reactions.push(anchor);
				}

				if (anchor.type === AnchorType.mention)
				{
					mentions.push(anchor);
				}
			});

			if (Type.isArrayFilled(reactions))
			{
				this.updateReactionsButton();
			}

			if (Type.isArrayFilled(mentions))
			{
				this.updateMentionsButton();
			}
		};

		/**
		 * @public
		 */
		showFloatingButtonsBar = () => {
			this.floatingButtonsBar.setItems(
				[
					this.getReactionButton(),
					this.getMentionButton(),
					this.getCommentsButton(),
					this.getScrollToNewMessagesButton(),
				].filter(Boolean),
			);
		};

		/**
		 * @public
		 */
		updateNewMessagesButton = () => {
			this.floatingButtonsBar.upsert(this.getScrollToNewMessagesButton());
		};

		/**
		 * @public
		 */
		updateMentionsButton = () => {
			const button = this.getMentionButton();
			if (button)
			{
				this.floatingButtonsBar.upsert(button);

				return;
			}

			this.floatingButtonsBar.remove(ButtonId.mentions);
		};

		/**
		 * @public
		 */
		updateReactionsButton = () => {
			const button = this.getReactionButton();
			if (button)
			{
				this.floatingButtonsBar.upsert(button);

				return;
			}

			this.floatingButtonsBar.remove(ButtonId.reactions);
		};

		/**
		 * @public
		 */
		updateCommentsButton = () => {
			const button = this.getCommentsButton();
			if (button)
			{
				this.floatingButtonsBar.upsert(button);

				return;
			}

			this.floatingButtonsBar.remove(ButtonId.comments);
		};

		/**
		 * @public
		 */
		showScrollToNewMessagesButton = () => {
			if (this.isScrollToNewMessagesButtonHidden)
			{
				this.isScrollToNewMessagesButtonHidden = false;
				this.animationQueue.push(() => this.floatingButtonsBar.upsert(this.getScrollToNewMessagesButton()));
				this.processAnimationQueue();
			}
		};

		/**
		 * @public
		 */
		hideScrollToNewMessagesButton = () => {
			if (!this.isScrollToNewMessagesButtonHidden)
			{
				this.isScrollToNewMessagesButtonHidden = true;
				this.animationQueue.push(() => this.floatingButtonsBar.remove(ButtonId.scrollToNewMessages));
				this.processAnimationQueue();
			}
		};

		/**
		 * @param {String} buttonId
		 */
		#onButtonTap = (buttonId) => {
			if (buttonId === ButtonId.reactions)
			{
				this.#onReactionsTap();

				return;
			}

			if (buttonId === ButtonId.mentions)
			{
				this.#onMentionsTap();

				return;
			}

			if (buttonId === ButtonId.comments)
			{
				this.#onCommentsTap();

				return;
			}

			if (buttonId === ButtonId.scrollToNewMessages)
			{
				this.scrollToNewMessagesHandler();
			}
		};

		#onReactionsTap = () => {
			this.lastScrolledMessageIdWithReaction = this.#getNextMessageIdWithReactionToJump();

			void this.#goToMessage(this.lastScrolledMessageIdWithReaction);
		};

		#onMentionsTap = () => {
			this.lastScrolledMessageIdWitMention = this.#getNextMessageIdWithMentionToJump();

			void this.#goToMessage(this.lastScrolledMessageIdWitMention);
		};

		#onCommentsTap = () => {
			this.lastScrolledCommentChatId = this.#getNextCommentChatIdToJump();

			void this.contextManager?.goToMessageContextByCommentChatId({
				dialogId: this.dialogId,
				commentChatId: this.lastScrolledCommentChatId,
			})
		};

		#getNextMessageIdWithReactionToJump = () => {
			const reactions = this.store.getters['anchorModel/getByChatId'](this.getChatId(), AnchorType.reaction);
			if (!Type.isArrayFilled(reactions))
			{
				this.updateReactionsButton();

				return null;
			}

			const messageIds = reactions.map(r => r.messageId);
			const sortedMessageIds = messageIds.sort((a, b) => a - b);

			return this.#getNextIdToJump(sortedMessageIds, this.lastScrolledMessageIdWithReaction);
		};

		#getNextMessageIdWithMentionToJump = () => {
			const mentions = this.store.getters['anchorModel/getByChatId'](this.getChatId(), AnchorType.mention);
			if (!Type.isArrayFilled(mentions))
			{
				this.updateMentionsButton();

				return null;
			}

			const messageIds = mentions.map((mention) => mention.messageId);
			const sortedMessageIds = messageIds.sort((a, b) => a - b);

			return this.#getNextIdToJump(sortedMessageIds, this.lastScrolledMessageIdWithMention);
		};

		#getNextCommentChatIdToJump()
		{
			const commentChatIds = this.#getCommentChatIds();

			commentChatIds.sort((a, b) => a - b);

			return this.#getNextIdToJump(commentChatIds, this.lastScrolledCommentChatId);
		}

		#getCommentChatIds()
		{
			if (!Feature.isMessengerV2Enabled)
			{
				const channelComments = this.store.getters['commentModel/getChannelCounterCollection'](this.getChatId());

				return Object.keys(channelComments).map((chatId) => {
					return Number(chatId);
				});
			}

			const counterList = this.store.getters['counterModel/getByParentChatId'](this.getChatId());

			return counterList.map((counterState) => counterState.chatId);
		}

		#getNextIdToJump(sortedIds, lastId) {
			if (!lastId)
			{
				return sortedIds[0];
			}

			const filteredIds = sortedIds.filter((id) => id > lastId);

			return Type.isArrayFilled(filteredIds) ? filteredIds[0] : sortedIds[0];
		}

		#goToMessage(messageId)
		{
			return this.contextManager?.goToMessageContext({
				dialogId: this.dialogId,
				messageId,
				withMessageHighlight: false,
			});
		}
	}

	module.exports = { FloatingButtonsBarManager };
});
