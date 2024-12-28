/**
 * @module im/messenger/controller/dialog/lib/mention/manager
 */
jn.define('im/messenger/controller/dialog/lib/mention/manager', (require, exports, module) => {
	const { MentionProvider } = require('im/messenger/controller/dialog/lib/mention/provider');
	const { ChatAvatar } = require('im/messenger/lib/element');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { EventType, BBCode } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Type } = require('type');

	const MENTION_SYMBOL = new Set(['@', '+']);
	const CLOSE_MENTION_SYMBOLS = new Set([' ', '\n']);
	const MENTION_PREFIX = new Set([' ', '\n']);

	const logger = LoggerManager.getInstance().getLogger('mention');

	class MentionManager
	{
		/**
		 * @param {DialogView} view
		 * @param dialogId
		 */
		constructor({ view, dialogId })
		{
			/**
			 * @private
			 * @type {DialogView}
			 */
			this.view = view;

			this.dialogId = dialogId;
			/**
			 * @private
			 * @type {MentionProvider || null}
			 */
			this.provider = null;
			/**
			 * @private
			 * @type {boolean}
			 */
			this.isProcessed = false;
			/**
			 * @private
			 * @type {string}
			 */
			this.curruntQuery = '';
			/**
			 * @private
			 * @type {boolean}
			 */
			this.isLoading = false;
			/**
			 * @private
			 * @type {number || null}
			 */
			this.mentionSymbolPosition = null;
			/**
			 * @private
			 * @type {number || null}
			 */
			this.lastQuerySymbolPosition = null;

			/**
			 * @private
			 * @type {number}
			 */
			this.focusIndexPosition = 1;

			/**
			 * @private
			 * @type {boolean}
			 */
			this.isDialogShow = true;

			/**
			 * @private
			 * @type {Array<{id: string|number, type: string}>}
			 */
			this.externalMentionQueue = [];
			this.bindMethods();
			this.initProvider();
			this.subscribeEvents();
		}

		bindMethods()
		{
			/**
			 * @private
			 * @type {function}
			 */
			this.changeTextStateHandler = this.onChangeText.bind(this);
			/**
			 * @private
			 * @type {function}
			 */
			this.mentionItemSelectedHandler = this.onMentionItemSelected.bind(this);
			/**
			 * @private
			 * @type {function}
			 */
			this.externalMention = this.onExternalMention.bind(this);
		}

		/**
		 * @public
		 */
		subscribeEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.mention, this.externalMention);

			if (!this.canUse())
			{
				return;
			}
			this.view.textField.on(EventType.dialog.textField.changeState, this.changeTextStateHandler);
			this.view.mentionPanel.on('itemTap', this.mentionItemSelectedHandler);
		}

		/**
		 * @public
		 */
		unsubscribeEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.mention, this.externalMention);

			if (!this.canUse())
			{
				return;
			}
			this.view.textField.off(EventType.dialog.textField.changeState, this.changeTextStateHandler);
			this.view.mentionPanel.off('itemTap', this.mentionItemSelectedHandler);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		get isMentionProcessed()
		{
			return this.isProcessed;
		}

		/**
		 * @public
		 */
		finishMentioning()
		{
			this.hideLoader();
			this.closeMentionPanel();
			this.recoverFocusIndexPosition();
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		canUse()
		{
			return Boolean(this.view.textField && this.view.mentionPanel);
		}

		/**
		 * @private
		 */
		showLoader()
		{
			this.view.mentionPanel.showLoader();
			this.isLoading = true;
		}

		/**
		 * @private
		 */
		hideLoader()
		{
			this.view.mentionPanel.hideLoader();
			this.isLoading = false;
		}

		/**
		 * @private
		 */
		closeMentionPanel()
		{
			this.view.mentionPanel.close();

			this.isProcessed = false;
			this.curruntQuery = '';
			this.mentionSymbolPosition = null;
			this.lastQuerySymbolPosition = null;

			this.provider.closeSession();
		}

		recoverFocusIndexPosition()
		{
			this.focusIndexPosition = 1;
		}

		/**
		 * @private
		 */
		initProvider()
		{
			this.provider = new MentionProvider(this.getProviderOptions());
		}

		/**
		 * @return {object}
		 */
		getProviderOptions()
		{
			return {
				dialogId: this.dialogId,
				loadSearchProcessed: (dialogIdList, isStartServerSearch) => {
					if (isStartServerSearch)
					{
						if (!this.isLoading)
						{
							logger.log('Mention: show loader');
							this.showLoader();
						}

						this.drawItems(dialogIdList);

						return;
					}

					if (this.isLoading)
					{
						logger.log('Mention: hide local loader');
						this.hideLoader();
					}

					this.drawItems(dialogIdList);
				},
				loadSearchComplete: (dialogIdList, query) => {
					if (query !== this.curruntQuery)
					{
						return;
					}

					if (this.isLoading)
					{
						logger.log('Mention: hide server loader');
						this.hideLoader();
					}
					this.drawItems(dialogIdList);
				},
			};
		}

		/**
		 * @private
		 * @param {string} text
		 * @param {string} inputCharacters
		 * @param {number} cursorPosition
		 */
		onChangeText(text, inputCharacters, cursorPosition)
		{
			logger.log('Mention.onChangeText', text, inputCharacters, cursorPosition);
			if (this.isMentionProcessed)
			{
				this.onProcessedMentionChangeText(text, inputCharacters, cursorPosition);

				return;
			}

			this.onInactiveMentionChangeText(text, inputCharacters, cursorPosition);
		}

		/**
		 * @private
		 * @param text
		 * @param inputCharacters
		 * @param cursorPosition
		 */
		async onProcessedMentionChangeText(text, inputCharacters, cursorPosition)
		{
			logger.log('Mention.onProcessedMentionChangeText', text, inputCharacters, cursorPosition);
			if (this.checkToClose(text, inputCharacters, cursorPosition))
			{
				logger.log('Mention: close panel');
				this.finishMentioning();

				return;
			}

			this.lastQuerySymbolPosition = cursorPosition - 1;
			if (this.mentionSymbolPosition === this.lastQuerySymbolPosition)
			{
				if (DialogHelper.isChatId(this.dialogId))
				{
					const userIdList = this.getRecentUsers();

					this.drawItems(userIdList);

					return;
				}
				const userIdList = await this.provider.loadChatParticipants();

				this.drawParticipantsItems(userIdList);

				return;
			}

			this.curruntQuery = text.slice(this.mentionSymbolPosition + 1, cursorPosition);
			this.provider.doSearch(this.curruntQuery);
		}

		/**
		 * @private
		 * @param text
		 * @param inputCharacters
		 * @param cursorPosition
		 */
		async onInactiveMentionChangeText(text, inputCharacters, cursorPosition)
		{
			if (!this.checkToOpen(text, inputCharacters, cursorPosition))
			{
				return;
			}

			logger.warn('Mention: open mention panel, cursorPosition:', cursorPosition);

			this.mentionSymbolPosition = cursorPosition - 1;
			const userIdList = await this.loadUsersForInitial();

			this.drawUserFoInitial(userIdList);
		}

		/**
		 * @private
		 * @param text
		 * @param inputCharacters
		 * @param cursorPosition
		 * @return {boolean}
		 */
		checkToOpen(text, inputCharacters, cursorPosition)
		{
			if (!MENTION_SYMBOL.has(inputCharacters))
			{
				return false;
			}

			const mentionSymbolPosition = cursorPosition - 1;

			if (
				mentionSymbolPosition !== 0
				&& !MENTION_PREFIX.has(text[mentionSymbolPosition - 1])
			)
			{
				return false;
			}

			return true;
		}

		/**
		 * @private
		 * @param {string} text
		 * @param {string} inputCharacters
		 * @param cursorPosition
		 * @return {boolean}
		 */
		checkToClose(text, inputCharacters, cursorPosition)
		{
			if (CLOSE_MENTION_SYMBOLS.has(inputCharacters))
			{
				logger.warn('Mention close: symbol has been entered to close the mention');

				return true;
			}

			if (inputCharacters.length > 1)
			{
				logger.warn('Mention close: more than 1 character entered');

				return true;
			}

			if (!MENTION_SYMBOL.has(text[this.mentionSymbolPosition]))
			{
				logger.warn('Mention close: mention symbol has been shifted or deleted');

				return true;
			}

			return false;
		}

		/**
		 * @private
		 * @param {MentionItem} item
		 */
		onMentionItemSelected(item)
		{
			logger.log('Mention: item selected', item);

			let bbCodeText = '';

			if (DialogHelper.isDialogId(item.id))
			{
				const id = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](item.id).chatId;
				bbCodeText = this.wrapTextInBBCode(item.title, BBCode.chat, id);
			}
			else
			{
				bbCodeText = this.wrapTextInBBCode(item.title, BBCode.user, item.id);
			}

			const fromIndex = this.mentionSymbolPosition ?? this.view.textField.getCursorIndex();
			let toIndex = (this.lastQuerySymbolPosition ?? this.mentionSymbolPosition) + this.focusIndexPosition;

			if (toIndex < fromIndex)
			{
				toIndex = fromIndex;
			}

			logger.warn('Mention: replace text', fromIndex, toIndex, bbCodeText);

			this.view.textField.replaceText(fromIndex, toIndex, bbCodeText);

			this.finishMentioning();
		}

		/**
		 * @private
		 * @param {string|number} id
		 * @param {string} type
		 */
		onExternalMention(id, type, fromDialogId = null)
		{
			if (this.dialogId !== fromDialogId)
			{
				return;
			}

			logger.log('Mention.onExternalMention', id, type, this.isDialogShow);

			if (!this.isDialogShow)
			{
				this.externalMentionQueue.push({ id, type, fromDialogId });

				return;
			}

			let bbCodeText = '';
			if (type === BBCode.user)
			{
				const userModelState = serviceLocator.get('core').getStore().getters['usersModel/getById'](id);
				if (Type.isUndefined(userModelState))
				{
					return false;
				}

				const userName = Type.isStringFilled(userModelState.name)
					? userModelState.name : `${userModelState.firstName} ${userModelState.lastName}`;
				bbCodeText = this.wrapTextInBBCode(userName, BBCode.user, id);
			}
			else
			{
				const dialogModelState = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](id);
				bbCodeText = this.wrapTextInBBCode(dialogModelState.name, BBCode.chat, dialogModelState.dialogId);
			}

			const text = this.view.getInput();
			if (Type.isStringFilled(text) && !text.endsWith(' '))
			{
				bbCodeText = ` ${bbCodeText}`;
			}

			this.view.textField.showKeyboard?.();
			this.view.textField.replaceText(text.length, text.length, bbCodeText);
		}

		onDialogHidden()
		{
			this.isDialogShow = false;
		}

		onDialogShow()
		{
			this.isDialogShow = true;

			if (this.externalMentionQueue.length > 0)
			{
				this.externalMentionQueue.forEach((externalMention) => {
					this.onExternalMention(externalMention.id, externalMention.type, externalMention.fromDialogId);
				});

				this.externalMentionQueue = [];
			}
		}

		/**
		 * @private
		 * @param {string} text
		 * @param {string} bbCode
		 * @param {string || number || null} [param=null]
		 * @return {string}
		 */
		wrapTextInBBCode(text, bbCode, param = null)
		{
			if (param !== null)
			{
				return `[${bbCode}=${param}]${text}[/${bbCode}] `;
			}

			return `[${bbCode}]${text}[/${bbCode}] `;
		}

		/**
		 * @private
		 * @param {string} itemId
		 * @return {MentionItem}
		 */
		prepareItemForDrawing(itemId)
		{
			const chatTitleParams = ChatTitle.createFromDialogId(itemId);
			const avatarTitleParams = ChatAvatar.createFromDialogId(itemId);

			return {
				id: itemId.toString(),
				title: chatTitleParams.getTitle(),
				titleColor: chatTitleParams.getTitleColor(),
				description: chatTitleParams.getDescription(),
				/** @deprecated use to avatar {AvatarDetail} */
				imageUrl: avatarTitleParams.getAvatarUrl(),
				/** @deprecated use to avatar {AvatarDetail} */
				imageColor: avatarTitleParams.getColor(),
				/** @deprecated use to avatar {AvatarDetail} */
				isSuperEllipseIcon: avatarTitleParams.getIsSuperEllipseIcon(),
				avatar: avatarTitleParams.getMentionAvatarProps(),
				testId: itemId.toString(),
			};
		}

		/**
		 * @private
		 * @param {Array<string>} itemIds
		 */
		drawItems(itemIds)
		{
			const result = [];

			itemIds.forEach((itemId) => {
				const item = this.prepareItemForDrawing(itemId);
				const recentItem = serviceLocator.get('core').getStore().getters['recentModel/getById'](item.id)
					?? serviceLocator.get('core').getStore().getters['recentModel/searchModel/getById'](item.id)
				;

				item.displayedDate = DateFormatter.getRecentFormat(recentItem.dateMessage);

				result.push(item);
			});

			logger.log('Mention: draw items', result);

			if (this.isProcessed)
			{
				this.view.mentionPanel.setItems(result);
			}
			else
			{
				this.view.mentionPanel.open(result);

				this.isProcessed = true;
			}
		}

		/**
		 * @override
		 * @return {Array<string>}
		 */
		getRecentUsers()
		{
			return this.provider.loadRecentUsers();
		}

		drawParticipantsItems(userIdList)
		{
			const result = [];

			userIdList.forEach((itemId) => {
				const item = this.prepareItemForDrawing(itemId);

				result.push(item);
			});

			logger.log('Mention: draw items', result);

			this.view.mentionPanel.setItems(result);
			this.view.mentionPanel.hideLoader();
		}

		async loadUsersForInitial()
		{
			if (DialogHelper.isChatId(this.dialogId))
			{
				return this.getRecentUsers();
			}

			this.view.mentionPanel.open([]);
			this.showLoader();
			this.isProcessed = true;

			return this.provider.loadChatParticipants();
		}

		/**
		 * @param {Array<number>} userIdList
		 */
		drawUserFoInitial(userIdList)
		{
			if (DialogHelper.isChatId(this.dialogId))
			{
				return this.drawItems(userIdList);
			}

			return this.drawParticipantsItems(userIdList);
		}
	}

	module.exports = { MentionManager };
});
