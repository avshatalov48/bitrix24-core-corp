/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/controller/dialog/lib/reply-manager
 */
jn.define('im/messenger/controller/dialog/lib/reply-manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const {
		MessageType,
		DialogType,
	} = require('im/messenger/const');
	const {
		InputQuoteType,
	} = require('im/messenger/view/dialog');
	const { parser } = require('im/messenger/lib/parser');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { DialogConverter } = require('im/messenger/lib/converter');

	const QUOTE_DELIMITER = '-'.repeat(54);
	const SHORT_NAME_SYMBOLS_LIMIT = 12;
	const LONG_NAME_SYMBOLS_LIMIT = 8;
	const TEXT_SYMBOLS_LIMIT = 35;
	const NAME_SYMBOLS_LIMIT_ELLIPSIS = '...';

	/**
	 * @class ReplyManager
	 */
	class ReplyManager
	{
		constructor({ store, dialogView })
		{
			this.store = store;
			/** @type {DialogView} */
			this.dialogView = dialogView;

			this.quoteMessage = null;
			this.inputTextBuffer = null;
			this._isQuoteInProcess = false;
			this._isQuoteInBackground = false;
			this._isForwardInBackground = false;
			this._isEditInProcess = false;
			this._isForwardInProcess = false;
			/** @type {DraftManager} */
			this.draftManager = null;

			this.editMessage = null;
		}

		get isQuoteInProcess()
		{
			return this._isQuoteInProcess;
		}

		get isQuoteInBackground()
		{
			return this._isQuoteInBackground;
		}

		get isForwardInBackground()
		{
			return this._isForwardInBackground;
		}

		get isEditInProcess()
		{
			return this._isEditInProcess;
		}

		get isForwardInProcess()
		{
			return this._isForwardInProcess;
		}

		/**
		 * @param {DraftManager} draftManager
		 */
		setDraftManager(draftManager)
		{
			this.draftManager = draftManager;
		}

		setQuoteMessage(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);

			const quoteMessage = DialogConverter.createMessage(modelMessage);
			const isSystemMessage = modelMessage.authorId === 0;
			// const isAudioMessage = message.type === MessageType.audio;
			// const isEmptyText = !modelMessage.text || modelMessage.text === '';

			if (isSystemMessage)
			{
				quoteMessage.username = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLY_MANAGER_QUOTE_DEFAULT_TITLE');
			}

			// if (isAudioMessage && isEmptyText)
			// {
			// 	quoteMessage.message = {
			// 		type: 'text',
			// 		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLY_MANAGER_MESSAGE_FIELD_VOICE'),
			// 	};
			// }

			quoteMessage.message = [{
				type: 'text',
				text: parser.simplifyMessage(modelMessage),
			}];

			this.quoteMessage = quoteMessage;
		}

		getQuoteMessage()
		{
			return this.quoteMessage;
		}

		/**
		* @return {ForwardMessage}
		*/
		getForwardMessage()
		{
			return this.forwardMessage;
		}

		/**
		 * @return {ForwardMessageIds}
		 */
		getForwardMessageIds()
		{
			return this.forwardMessageIds;
		}

		getQuoteText(message = this.getQuoteMessage())
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			if (
				Type.isUndefined(modelMessage)
				|| (Type.isObject(modelMessage) && !('id' in modelMessage))
			)
			{
				return '';
			}

			let quoteTitle = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLY_MANAGER_QUOTE_DEFAULT_TITLE');
			const isSystemMessage = modelMessage.authorId === 0;
			if (!isSystemMessage && modelMessage.authorId)
			{
				const user = this.store.getters['usersModel/getById'](modelMessage.authorId);
				quoteTitle = user.name || user.firstName;
			}

			const quoteDate = DateFormatter.getQuoteFormat(modelMessage.date);
			const quoteText = parser.prepareQuote(modelMessage);

			let quoteContext = '';
			const dialog = this.store.getters['dialoguesModel/getByChatId'](modelMessage.chatId);
			if (dialog && (dialog.type === DialogType.user || dialog.type === DialogType.private))
			{
				quoteContext = `#${dialog.dialogId}:${MessengerParams.getUserId()}/${modelMessage.id}`;
			}
			else if (dialog && dialog.dialogId)
			{
				quoteContext = `#${dialog.dialogId}/${modelMessage.id}`;
			}
			else
			{
				quoteContext = '';
			}

			return (
				`${QUOTE_DELIMITER}\n`
				+ `${quoteTitle} [${quoteDate}] ${quoteContext}\n`
				+ `${quoteText}\n`
				+ `${QUOTE_DELIMITER}\n`
			);
		}

		setEditMessage(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			const editMessage = DialogConverter.createMessage(modelMessage);
			editMessage.username = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLY_MANAGER_MESSAGE_EDIT_FIELD');

			const isAudioMessage = message.type === MessageType.audio;
			const isEmptyText = !modelMessage.message || modelMessage.message === '';
			// if (isAudioMessage && isEmptyText)
			// {
			// 	message.message = {
			// 		type: 'text',
			// 		text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLY_MANAGER_MESSAGE_FIELD_VOICE'),
			// 	};
			// }
			editMessage.message = [{
				type: 'text',
				text: parser.simplifyMessage(modelMessage),
			}];

			this.editMessage = editMessage;
		}

		/**
		 *
		 * @param {Message} message
		 */
		setForwardMessage(message)
		{
			if (!Type.isObject(message))
			{
				const messageModel = this.store.getters['messagesModel/getById'](Number(message));

				// eslint-disable-next-line no-param-reassign
				message = DialogConverter.createMessage(messageModel);
			}

			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			const forwardMessage = {
				id: modelMessage.id,
			};

			forwardMessage.username = message.username;
			if (message.type === MessageType.systemText)
			{
				forwardMessage.username = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLY_MANAGER_FORWARD_DEFAULT_TITLE');
			}

			forwardMessage.message = [
				{
					type: 'text',
					text: parser.simplifyMessage(modelMessage),
				},
			];

			this.forwardMessage = forwardMessage;
			this.forwardMessageIds = [modelMessage.id];
		}

		/**
		 *
		 * @param {Array<string|number>} messageIds
		 */
		setForwardMessageFromIdList(messageIds)
		{
			const currentUserId = MessengerParams.getUserId();
			const authorIdsSet = new Set();
			const authorNames = [];
			const sortedMessageIds = [];
			const messagesModelCollection = this.store.getters['messagesModel/getListByIds'](messageIds);

			messagesModelCollection.forEach((messageModel) => {
				authorIdsSet.add(messageModel.authorId);
				sortedMessageIds.push(messageModel.id);
			});

			const isHaveCurrentUser = authorIdsSet.has(currentUserId);
			if (isHaveCurrentUser)
			{
				authorNames.push(Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLAY_MANAGER_FORWARDS_TEXT_CURRENT_USER'));
			}

			const filteredAuthorIds = [...authorIdsSet].filter((authorId) => authorId !== currentUserId && authorId !== 0);
			const userModels = this.store.getters['usersModel/getByIdList'](filteredAuthorIds);
			userModels.forEach((user) => {
				if (user)
				{
					authorNames.push(user.firstName || user.name);
				}
			});

			const usersName = this.truncateAuthorNames(authorNames);
			const text = this.getForwardNamesText(usersName, filteredAuthorIds, isHaveCurrentUser);

			const title = Loc.getMessagePlural(
				'IMMOBILE_MESSENGER_DIALOG_REPLAY_MANAGER_FORWARDS_TITLE',
				sortedMessageIds.length,
				{
					'#COUNT#': sortedMessageIds.length,
				},
			);

			this.forwardMessage = {
				id: sortedMessageIds[0],
				username: title,
				message: [
					{
						type: 'text',
						text,
					},
				],
			};

			this.forwardMessageIds = sortedMessageIds;
		}

		/**
		 * @desc truncate string with names depending on two conditions:
		 * 1 - there are more than 3 names, then slice according to the limit.
		 * 2 - there are less than 3 names, then the native slice
		 * @param {Array<string>} authorNames
		 * @return {string}
		 */
		truncateAuthorNames(authorNames) {
			if (authorNames.length === 1)
			{
				return authorNames[0];
			}

			// this length truncation will make native
			if (authorNames.length === 2)
			{
				return [authorNames[0], authorNames[1]].join(', ');
			}

			// this length truncation for text: 'and more'
			if (authorNames.length > 2)
			{
				const stringNamesMaxLength = [authorNames[0], authorNames[1]].join(', ')?.length;
				const limit = stringNamesMaxLength > TEXT_SYMBOLS_LIMIT
					? LONG_NAME_SYMBOLS_LIMIT
					: SHORT_NAME_SYMBOLS_LIMIT
				;

				const preparedNames = [authorNames[0], authorNames[1]].map((name) => {
					if (name && name.length > limit)
					{
						return `${name.slice(0, limit)}${NAME_SYMBOLS_LIMIT_ELLIPSIS}`;
					}

					return name;
				});

				return preparedNames.join(', ');
			}

			return authorNames[0];
		}

		/**
		 * @param {Array<string>} usersName
		 * @param {Array<number>} filteredAuthorIds
		 * @param {boolean} isHaveCurrentUser
		 * @return {string}
		 */
		getForwardNamesText(usersName, filteredAuthorIds, isHaveCurrentUser)
		{
			let text = '';
			if (isHaveCurrentUser && filteredAuthorIds.length > 1)
			{
				text = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLAY_MANAGER_FORWARDS_TEXT_MORE', {
					'#USERS_NAME#': usersName,
					'#USERS_COUNT#': filteredAuthorIds.length - 1,
				});
			}
			else if (!isHaveCurrentUser && filteredAuthorIds.length > 2)
			{
				text = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLAY_MANAGER_FORWARDS_TEXT_MORE', {
					'#USERS_NAME#': usersName,
					'#USERS_COUNT#': filteredAuthorIds.length - 2,
				});
			}
			else if (isHaveCurrentUser && filteredAuthorIds.length === 0)
			{
				const currentUserId = MessengerParams.getUserId();
				const currentUserModels = this.store.getters['usersModel/getById'](currentUserId);
				text = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLAY_MANAGER_FORWARDS_TEXT', {
					'#USER_NAME#': currentUserModels.firstName || currentUserModels.name,
				});
			}
			else
			{
				text = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_REPLAY_MANAGER_FORWARDS_TEXT', {
					'#USER_NAME#': usersName,
				});
			}

			return text;
		}

		getEditMessage()
		{
			return this.editMessage;
		}

		startQuotingMessage(message, openKeyboard = true)
		{
			this.setQuoteMessage(message);
			if (this.isEditInProcess)
			{
				this._isQuoteInBackground = true;

				return;
			}

			if (this._isForwardInProcess)
			{
				this._isForwardInProcess = false;
				this.dialogView.enableAlwaysSendButtonMode(false);
			}

			// this.inputTextBuffer will be filled if we replied to the message, but did not send it,
			// but opened the message editing on top.
			if (this.inputTextBuffer !== null)
			{
				this.dialogView.setInput(this.inputTextBuffer);
				this.inputTextBuffer = null;
			}

			if (this.isQuoteInProcess)
			{
				this.finishQuotingMessage().then(() => {
					this._startQuotingMessage(openKeyboard);
				});

				return;
			}

			this._startQuotingMessage(openKeyboard);
		}

		_startQuotingMessage(openKeyboard = true)
		{
			this._isQuoteInProcess = true;
			this._isQuoteInBackground = false;

			const quoteMessage = this.getQuoteMessage();

			this.dialogView.setInputQuote(quoteMessage, InputQuoteType.reply, openKeyboard);
			if (this.draftManager)
			{
				this.draftManager.setQuotMessageInStore(quoteMessage, InputQuoteType.reply, this.dialogView.getInput());
			}
		}

		startEditingMessage(message)
		{
			// TODO: remove setTimeout 0 after fixing dialogView.getInput method. Works only on the UI thread.
			const inputText = this.dialogView.getInput();
			if (!this.isEditInProcess && inputText !== '')
			{
				this.inputTextBuffer = inputText;
			}

			if (this._isForwardInProcess)
			{
				this._isForwardInProcess = false;
				this.dialogView.enableAlwaysSendButtonMode(false);
			}

			if (this._isQuoteInProcess)
			{
				this._isQuoteInBackground = true;
			}

			this.setEditMessage(message);
			this._isEditInProcess = true;

			const modelMessage = clone(this.store.getters['messagesModel/getById'](Number(message.id)));
			const editMessage = this.getEditMessage();

			this.dialogView.setInputQuote(editMessage, InputQuoteType.edit);

			const editMessageText = modelMessage.text || '';
			this.dialogView.setInput(editMessageText);
			if (this.draftManager)
			{
				this.draftManager.setQuotMessageInStore(editMessage, InputQuoteType.edit, editMessageText);
			}
		}

		/**
		 *
		 * @param {Array<number || string || Message>} messageIds
		 */
		startForwardingMessages(messageIds)
		{
			if (this.isEditInProcess)
			{
				this._isForwardInBackground = true;

				return;
			}
			this._isForwardInProcess = true;

			if (messageIds.length === 1)
			{
				this.setForwardMessage(messageIds[0]);
			}
			else
			{
				this.setForwardMessageFromIdList(messageIds);
			}

			const message = this.getForwardMessage();
			const title = message.username;
			const text = message.message[0]?.text;
			this.dialogView.setInputQuote(message, InputQuoteType.forward, false, title, text);
			this.dialogView.enableAlwaysSendButtonMode(true);
		}

		finishQuotingMessage()
		{
			this._isQuoteInProcess = false;
			this.draftManager.cancelReply();

			return this.dialogView.removeInputQuote();
		}

		finishEditingMessage()
		{
			this._isEditInProcess = false;
			this.dialogView.clearInput();
			this.draftManager.saveDraft('');
			if (this.inputTextBuffer !== null)
			{
				this.dialogView.setInput(this.inputTextBuffer);
				this.draftManager.saveDraft(this.inputTextBuffer);
			}

			this.dialogView.removeInputQuote().then(() => {
				if (this.isQuoteInBackground)
				{
					this.startQuotingMessage(this.getQuoteMessage());

					return;
				}

				if (this.isForwardInBackground)
				{
					this.startForwardingMessages(this.getForwardMessage());

					return;
				}
			});
		}

		finishForwardingMessage()
		{
			this._isForwardInProcess = false;
			if (this.isQuoteInBackground)
			{
				this._isQuoteInBackground = false;
			}
			this.dialogView.clearInput();
			this.draftManager.saveDraft('');
			this.dialogView.removeInputQuote();
			this.dialogView.enableAlwaysSendButtonMode(false);
		}

		initializeEditingMessage(message, initWithForward)
		{
			this.editMessage = message;

			this._isEditInProcess = true;
			this.dialogView.setInputQuote(message, InputQuoteType.edit, false);
		}

		initializeQuotingMessage(message, initWithForward)
		{
			this.quoteMessage = message;
			if (initWithForward)
			{
				this._isQuoteInBackground = true;

				return;
			}
			this._isQuoteInProcess = true;
			this.dialogView.setInputQuote(message, InputQuoteType.reply, false);
		}

		/**
		 * @desc Check is having reply id in the message
		 * @param {MessagesModelState} message
		 * @return {boolean}
		 */
		isHasQuote(message)
		{
			return message.params.replyId;
		}
	}

	module.exports = {
		ReplyManager,
	};
});
