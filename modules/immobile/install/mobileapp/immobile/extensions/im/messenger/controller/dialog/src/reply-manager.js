/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/controller/dialog/reply-manager
 */
jn.define('im/messenger/controller/dialog/reply-manager', (require, exports, module) => {

	const { Loc } = require('loc');
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

	const QUOTE_DELIMITER = '-'.repeat(54);

	/**
	 * @class ReplyManager
	 */
	class ReplyManager
	{
		constructor({ store, dialogView })
		{
			this.store = store;
			this.dialogView = dialogView;

			this.quoteMessage = null;
			this.inputTextBuffer = null;
			this._isQuoteInProcess = false;
			this._isQuoteInBackground = false;
			this._isEditInProcess = false;

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

		get isEditInProcess()
		{
			return this._isEditInProcess;
		}

		setQuoteMessage(message)
		{
			const modelMessage = this.store.getters['messagesModel/getMessageById'](message.id);

			const isSystemMessage = modelMessage.authorId === 0;
			const isAudioMessage = message.type === MessageType.audio;
			const isEmptyText = !modelMessage.text || modelMessage.text === '';

			if (isSystemMessage)
			{
				message.username = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_QUOTE_DEFAULT_TITLE');
			}

			if (isAudioMessage && isEmptyText)
			{
				message.message = {
					type: 'text',
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_FIELD_VOICE'),
				};
			}

			this.quoteMessage = message;
		}

		getQuoteMessage()
		{
			return this.quoteMessage;
		}

		getQuoteText()
		{
			const modelMessage = this.store.getters['messagesModel/getMessageById'](this.getQuoteMessage().id);

			let quoteTitle = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_QUOTE_DEFAULT_TITLE');
			const isSystemMessage = modelMessage.authorId === 0;
			if (!isSystemMessage && modelMessage.authorId)
			{
				const user = this.store.getters['usersModel/getUserById'](modelMessage.authorId);
				quoteTitle = user.name;
			}

			const quoteDate = DateFormatter.getQuoteFormat(modelMessage.date);
			const quoteText = parser.prepareQuote(modelMessage);

			let quoteContext;
			const dialog = this.store.getters['dialoguesModel/getByChatId'](modelMessage.chatId);
			if (dialog && dialog.type === DialogType.user)
			{
				quoteContext = `#${dialog.dialogId}:${MessengerParams.getUserId()}/${modelMessage.id}`;
			}
			else
			{
				quoteContext = `#${dialog.dialogId}/${modelMessage.id}`;
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
			const modelMessage = this.store.getters['messagesModel/getMessageById'](message.id);
			message.username = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_EDIT_FIELD');

			const isAudioMessage = message.type === MessageType.audio;
			const isEmptyText = !modelMessage.message || modelMessage.message === '';
			if (isAudioMessage && isEmptyText)
			{
				message.message = {
					type: 'text',
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_FIELD_VOICE'),
				};
			}

			this.editMessage = message;
		}

		getEditMessage()
		{
			return this.editMessage;
		}

		startQuotingMessage(message)
		{
			this.setQuoteMessage(message);
			if (this.isEditInProcess)
			{
				this._isQuoteInBackground = true;

				return;
			}

			//this.inputTextBuffer will be filled if we replied to the message, but did not send it,
			// but opened the message editing on top.
			if (this.inputTextBuffer !== null)
			{
				this.dialogView.setInput(this.inputTextBuffer);
				this.inputTextBuffer = null;
			}

			if (this.isQuoteInProcess)
			{
				this.finishQuotingMessage().then(() => {
					this._startQuotingMessage();
				});

				return;
			}

			this._startQuotingMessage();
		}

		_startQuotingMessage()
		{
			this._isQuoteInProcess = true;
			this._isQuoteInBackground = false;

			const modelMessage = clone(this.store.getters['messagesModel/getMessageById'](this.getQuoteMessage().id));
			const quoteMessage = clone(this.getQuoteMessage());
			quoteMessage.message = [{
				type: 'text',
				text: parser.simplifyMessage(modelMessage),
			}];

			this.dialogView.setInputQuote(quoteMessage, InputQuoteType.reply);
		}

		startEditingMessage(message)
		{
			//TODO: remove setTimeout 0 after fixing dialogView.getInput method. Works only on the UI thread.
			setTimeout(() => {
				const inputText = this.dialogView.getInput();
				if (!this.isEditInProcess && inputText !== '')
				{
					this.inputTextBuffer = inputText;
				}

				if (this._isQuoteInProcess)
				{
					this._isQuoteInBackground = true;
				}

				this.setEditMessage(message);
				this._isEditInProcess = true;

				const modelMessage = clone(this.store.getters['messagesModel/getMessageById'](Number(message.id)));
				const editMessage = clone(this.getEditMessage());
				editMessage.message = [{
					type: 'text',
					text: parser.simplifyMessage(modelMessage),
				}];
				this.dialogView.setInputQuote(editMessage, InputQuoteType.edit);

				const editMessageText = modelMessage.text || '';
				this.dialogView.setInput(editMessageText);
			}, 0);
		}

		finishQuotingMessage()
		{
			this._isQuoteInProcess = false;

			return this.dialogView.removeInputQuote();
		}

		finishEditingMessage()
		{
			this._isEditInProcess = false;
			this.dialogView.clearInput();
			if (this.inputTextBuffer !== null)
			{
				this.dialogView.setInput(this.inputTextBuffer);
			}

			this.dialogView.removeInputQuote().then(() => {
				if (this.isQuoteInBackground)
				{
					this.startQuotingMessage(this.getQuoteMessage());
				}
			});
		}
	}

	module.exports = {
		ReplyManager,
	};
});
